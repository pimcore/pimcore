/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.tags.objects");
pimcore.object.tags.objects = Class.create(pimcore.object.tags.abstract, {

    type: "objects",
    dataChanged:false,
    idProperty: "id",
    pathProperty: "path",

    initialize: function (data, fieldConfig) {
        this.data = [];
        this.fieldConfig = fieldConfig;
        if (data) {
            this.data = data;
        }

        this.store = new Ext.data.ArrayStore({
            data: this.data,
            listeners: {
                add:function() {
                    this.dataChanged = true;
                }.bind(this),
                remove: function() {
                    this.dataChanged = true;
                }.bind(this),
                clear: function () {
                    this.dataChanged = true;
                }.bind(this)
            },
            fields: [
                "id",
                "path",
                "type"
            ]
        });
    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer:
            function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                if (value && value.length > 0) {

                    // only show 10 relations in the grid
                    var maxAmount = 10;
                    if(value.length > maxAmount) {
                        value.splice(maxAmount, (value.length - maxAmount) );
                        value.push("...");
                    }

                    return value.join("<br />");
                }
            }.bind(this, field.key)};
    },    

    openParentSearchEditor: function(){
        pimcore.helpers.itemselector(false, function (selection) {
                    this.parentField.setValue(selection.fullpath);
                    this.parentIdField.setValue(selection.id);
                }.bind(this), {
                    type: ["object"],
                    subtype: {
                        object: ["object", "folder"]
                    },
                    specific: {
                        classes: null
                    }
                },
            {
                context: this.getContext()
            });
    },

    create: function(className) {

        this.window = new Ext.Window({
            width: 500,
            height: 200,
            modal: true,
            title: t('add_object'),
            layout: "fit"
        });

        this.nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            width: 300

        });

        this.parentIdField = new Ext.form.Hidden({});

        this.parentField = new Ext.form.TextField({
            name: 'parent',
            fieldLabel: t('parent_element'),
            width: 300,
            disabled:true
        });

        this.parentChooseButton = new Ext.Button({
            labelStyle: 'padding-left: 10px;',
            iconCls: 'pimcore_icon_search',
            handler: this.openParentSearchEditor.bind(this)
        });


        var panel = new Ext.Panel({
            bodyStyle: "padding: 10px;",
            items: [
                this.nameField,
                new Ext.form.FieldContainer({
                    layout: 'hbox',
                    items: [
                        this.parentField,
                        this.parentChooseButton
                    ]
                })

            ],
            buttons: [
                {
                    text: t("create"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.addCreateObject(className);
                    }.bind(this)
                }
            ]
        });

        this.window.add(panel);

        this.window.show();

    },

    addCreateObject: function(className) {

        var name = this.nameField.getValue();

        var parent = this.parentField.getValue();
        var parentId = this.parentIdField.getValue();
        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.find('text', className));
        var classId = record.getId();

        var invalid = false;
        if (!parent || !parentId) {
            this.parentField.markInvalid();
            invalid = true;
        }
        if (!name) {
            this.nameField.markInvalid();
            invalid = true;
        }

        if (!invalid) {
            Ext.Ajax.request({
                url: "/admin/object/add",
                params: {
                    className: className,
                    classId: classId,
                    parentId: parentId,
                    key: pimcore.helpers.getValidFilename(name, "object")
                },
                success: function(response) {
                    var data = Ext.decode(response.responseText);
                    if (data.success) {
                        this.store.add({
                            id: data.id,
                            path: parent + "/" + pimcore.helpers.getValidFilename(name, "object"),
                            type: className
                        });
                        pimcore.helpers.openElement(data.id, "object", "object");
                        this.window.close();
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error",data.message);
                    }

                }.bind(this)
            });
        }


    }
    ,

    getCreateControl: function () {

        var allowedClasses;
        var i;

        var classStore = pimcore.globalmanager.get("object_types_store");
        if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
            allowedClasses = [];
            for (i = 0; i < this.fieldConfig.classes.length; i++) {
                if(this.fieldConfig.classes[i].classes) {
                    allowedClasses.push(this.fieldConfig.classes[i].classes);
                }
            }
        } else if (this.fieldConfig.ownerClassName) {
            allowedClasses = [];
            allowedClasses.push(this.fieldConfig.ownerClassName);
        } else if (classStore.data && classStore.data.items && classStore.data.items.length > 0) {
            allowedClasses = [];
            for (i = 0; i < classStore.data.items.length; i++) {
                allowedClasses.push(classStore.data.items[i].data.text);
            }

        }

        var collectionMenu = [];

        if (allowedClasses && allowedClasses.length > 0) {
            for (i = 0; i < allowedClasses.length; i++) {
                collectionMenu.push({
                    text: ts(allowedClasses[i]),
                    handler: this.create.bind(this, allowedClasses[i]),
                    iconCls: "pimcore_icon_fieldcollection"
                });
            }
        }
        var items = [];

        if (collectionMenu.length == 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                handler: collectionMenu[0].handler
            });
        } else if (collectionMenu.length > 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                menu: collectionMenu
            });
        } else {
            items.push({
                xtype: "tbtext",
                text: t("no_collections_allowed")
            });
        }


        return items[0];
    }
    ,

    getLayoutEdit: function () {

        if (intval(this.fieldConfig.height) < 15) {
            this.fieldConfig.height = null;
        }

        var cls = 'object_field';

        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            border: true,
            style: "margin-bottom: 10px",
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop',
                    dragroup: 'element'
                },
                listeners: {
                    drop: function(node, data, dropRec, dropPosition) {
                        this.dataChanged = true;
                    }.bind(this),
                    refresh: function(gridview) {
                        this.requestNicePathData(this.store.data);
                    }.bind(this)
                }
            },
            selModel: Ext.create('Ext.selection.RowModel', {}),

                columns: [
                    {header: 'ID', dataIndex: 'id', width: 50}
                    ,
                    {header: t("reference"), dataIndex: 'path', flex: 200},
                    {header: t("type"), dataIndex: 'type', width: 100},
                    {
                        xtype:'actioncolumn',
                        width:40,
                        items:[
                            {
                                tooltip:t('up'),
                                icon:"/pimcore/static6/img/flat-color-icons/up.svg",
                                handler:function (grid, rowIndex) {
                                    if (rowIndex > 0) {
                                        var rec = grid.getStore().getAt(rowIndex);
                                        grid.getStore().removeAt(rowIndex);
                                        grid.getStore().insert(rowIndex - 1, [rec]);
                                    }
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype:'actioncolumn',
                        width:40,
                        items:[
                            {
                                tooltip:t('down'),
                                icon:"/pimcore/static6/img/flat-color-icons/down.svg",
                                handler:function (grid, rowIndex) {
                                    if (rowIndex < (grid.getStore().getCount() - 1)) {
                                        var rec = grid.getStore().getAt(rowIndex);
                                        grid.getStore().removeAt(rowIndex);
                                        grid.getStore().insert(rowIndex + 1, [rec]);
                                    }
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 40,
                        items: [
                            {
                                tooltip: t('open'),
                                icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
                                handler: function (grid, rowIndex) {
                                    var data = grid.getStore().getAt(rowIndex);
                                    pimcore.helpers.openObject(data.data.id, "object");
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 40,
                        items: [
                            {
                                tooltip: t('remove'),
                                icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                                handler: function (grid, rowIndex) {
                                    grid.getStore().removeAt(rowIndex);
                                }.bind(this)
                            }
                        ]
                    }
                ],

            componentCls: cls,
            autoExpandColumn: 'path',
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: {
                items: [
                    {
                        xtype: "tbspacer",
                        width: 20,
                        height: 16,
                        cls: "pimcore_icon_droptarget"
                    },
                    {
                        xtype: "tbtext",
                        text: "<b>" + this.fieldConfig.title + "</b>"
                    },
                    "->",
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_delete",
                        handler: this.empty.bind(this)
                    },
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_search",
                        handler: this.openSearchEditor.bind(this)
                    },
                    this.getCreateControl()
                ],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            bodyCssClass: "pimcore_object_tag_objects"
        });

        this.component.on("rowcontextmenu", this.onRowContextmenu);
        this.component.reference = this;

        this.component.on("afterrender", function () {

            var dropTargetEl = this.component.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'element',
                getTargetFromEvent: function(e) {
                    return this.component.getEl().dom;
                    //return e.getTarget(this.grid.getView().rowSelector);
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {
                    try {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(ddSource);

                        if (this.dndAllowed(data, fromTree)) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }
                    }  catch (e) {
                        console.log(e);
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }
                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {

                    try {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(dd);

                        var toBeRequested = new Ext.util.Collection();

                        if (this.dndAllowed(data, fromTree)) {
                            if(data["grid"] && data["grid"] == this.component) {
                                var rowIndex = this.component.getView().findRowIndex(e.target);
                                if(rowIndex !== false) {
                                    var rec = this.store.getAt(data.rowIndex);
                                    this.store.removeAt(data.rowIndex);
                                    toBeRequested.add(this.store.insert(rowIndex, [rec]));
                                    this.requestNicePathData(toBeRequested);
                                }
                            } else {
                                var initData = {
                                    id: data.id,
                                    path: data.path,
                                    type: data.className
                                };

                                if (!this.objectAlreadyExists(initData.id)) {
                                    toBeRequested.add(this.store.add(initData));
                                    this.requestNicePathData(toBeRequested);
                                    return true;
                                }
                            }
                        }
                    } catch (e) {
                        console.log(e);
                    }

                    return false;
                }.bind(this)
            });
        }.bind(this));


        return this.component;
    },

    isFromTree: function(ddSource) {
        var klass = Ext.getClass(ddSource);
        var className = klass.getName();
        var fromTree = className == "Ext.tree.ViewDragZone";
        return fromTree;
    },


    getLayoutShow: function () {

        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            columns: [
                {header: 'ID', dataIndex: 'id', width: 50, sortable: false},
                {header: t("reference"), dataIndex: 'path', width: 200, sortable: false},
                {header: t("type"), dataIndex: 'type', width: 100, sortable: false},
                {
                    xtype: 'actioncolumn',
                    width: 40,
                    sortable: false,
                    items: [
                        {
                            tooltip: t('open'),
                            icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
                            handler: function (grid, rowIndex) {
                                var data = grid.getStore().getAt(rowIndex);
                                pimcore.helpers.openObject(data.data.id, "object");
                            }.bind(this)
                        }
                    ]
                }
            ],
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            autoHeight:autoHeight,
            border: true,
            cls: "object_field",
            autoExpandColumn: 'path',
            style: "margin-bottom: 10px",
            title: this.fieldConfig.title,
            viewConfig: {
                listeners: {
                    refresh: function (gridview) {
                        this.requestNicePathData(this.store.data);
                    }.bind(this)
                }
            }
        });

        return this.component;
    }
    ,

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);

        menu.add(new Ext.menu.Item({
            text: t('remove'),
            iconCls: "pimcore_icon_delete",
            handler: this.reference.removeObject.bind(this, rowIndex)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (data, item) {
                item.parentMenu.destroy();
                pimcore.helpers.openObject(data.data.id, "object");
            }.bind(this, data)
        }));

        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this.reference)
        }));

        e.stopEvent();
        menu.showAt(e.getXY());
    }
    ,


    openSearchEditor: function () {
        var allowedClasses;
        if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
            allowedClasses = [];
            for (var i = 0; i < this.fieldConfig.classes.length; i++) {
                allowedClasses.push(this.fieldConfig.classes[i].classes);
            }
        }

        pimcore.helpers.itemselector(true, this.addDataFromSelector.bind(this), {
            type: ["object"],
            subtype: {
                object: ["object", "folder","variant"]
            },
            specific: { 
                classes: allowedClasses
            }
        },
            {
                context: Ext.apply({scope: "objectEditor"}, this.getContext())
            });
    }
    ,

    removeObject: function (index, item) {
        this.getStore().removeAt(index);
        item.parentMenu.destroy();
    }
    ,

    empty: function () {
        this.store.removeAll();
    }
    ,

    isInvalidMandatory: function () {

        var data = this.store.queryBy(function(record, id) {
            return true;
        });
        if (data.items.length < 1) {
            return true;
        }
        return false;

    }
    ,

    addDataFromSelector: function (items) {

        if (items.length > 0) {

            var toBeRequested = new Ext.util.Collection();

            for (var i = 0; i < items.length; i++) {
                if (!this.objectAlreadyExists(items[i].id)) {
                    toBeRequested.add(this.store.add({
                        id: items[i].id,
                        path: items[i].fullpath,
                        type: items[i].classname
                    }));
                }
            }

            this.requestNicePathData(toBeRequested);
        }
    }
    ,

    objectAlreadyExists: function (id) {

        // check max amount in field
        if(this.fieldConfig["maxItems"] && this.fieldConfig["maxItems"] >= 1) {
            if(this.store.getCount() >= this.fieldConfig.maxItems) {
                Ext.Msg.alert(t("error"),t("limit_reached"));
                return true;
            }
        }

        // check for existing object
        var result = this.store.query("id", new RegExp("^" + id + "$"));

        if (result.length < 1) {
            return false;
        }
        return true;
    }
    ,

    getValue: function () {

        var tmData = [];

        var data = this.store.queryBy(function(record, id) {
            return true;
        });


        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }
        return tmData;
    }
    ,

    getName: function () {
        return this.fieldConfig.name;
    }
    ,


    dndAllowed: function(data, fromTree) {

        // check if data is a treenode, if not allow drop because of the reordering
        if (!fromTree) {
            if(data["grid"] && data["grid"] == this.component) {
                return true;
            }
            return false;
        }

        // only allow objects not folders
        if (data.type == "folder" || data.elementType != "object") {
            return false;
        }

        var classname = data.className;
        var isAllowedClass = false;
        if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
            for (var i = 0; i < this.fieldConfig.classes.length; i++) {
                if (this.fieldConfig.classes[i].classes == classname) {
                    isAllowedClass = true;
                    break;
                }
            }

        } else {
            isAllowedClass = true;
        }
        return isAllowedClass;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        return this.dataChanged;
    },

    requestNicePathData: function(targets) {
        targets = this.normalizeTargetData(targets);

        pimcore.helpers.requestNicePathData(
            {
                type: "object",
                id: this.object.id
            },
            targets,
            {},
            this.fieldConfig,
            this.getContext(),
            pimcore.helpers.requestNicePathDataGridDecorator.bind(this, this.component.getView()),
            pimcore.helpers.getNicePathHandlerStore.bind(this, this.store, {
                idProperty: this.idProperty,
                pathProperty: this.pathProperty
            }, this.component.getView())
        );
    },

    normalizeTargetData: function(targets) {
        if (!targets) {
            return targets;
        }

        targets.each(function(record){
            var type = record.data.type;
            record.data.type = "object";
            record.data.subtype = type;
        }, this);

        return targets;
    }
});