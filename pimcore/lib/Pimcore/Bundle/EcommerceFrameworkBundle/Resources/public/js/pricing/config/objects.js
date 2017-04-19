/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


pimcore.registerNS("pimcore.bundle.EcommerceFramework.pricing.config.objects");
pimcore.bundle.EcommerceFramework.pricing.config.objects = Class.create(pimcore.object.tags.objects, {

    type: "objects",
    dataChanged: false,

    initialize: function (data, fieldConfig) {
        this.data = [];
        this.fieldConfig = fieldConfig;
        var classStore = pimcore.globalmanager.get("object_types_store");
        var typeOf = typeof(className);
        var className = classStore.getById(fieldConfig.allowedClassId);

        if (typeOf != "undefined") {
            classNameText = className.data.text;
        } else {
            classNameText = "";
        }


        if (data) {
            this.data = data;
        }

        var fields = [];
        var visibleFields = this.fieldConfig.visibleFields.split(",");

        fields.push("id");

        var i;

        for(i = 0; i < visibleFields.length; i++) {
            fields.push(visibleFields[i]);
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
                }.bind(this),
                update: function(store) {
                    this.dataChanged = true;
                }.bind(this)
            },
            fields: fields
        });
    },


    createLayout: function(readOnly) {
        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';
        var i;

        var visibleFields = this.fieldConfig.visibleFields.split(",");

        var columns = [];
        columns.push({header: 'ID', dataIndex: 'id', width: 50});

        for (i = 0; i < visibleFields.length; i++) {
            columns.push({header: ts(visibleFields[i]), dataIndex: visibleFields[i], width: 100, editor: null,
                                                                    renderer: renderer});
        }

        for (i = 0; i < this.fieldConfig.columns.length; i++) {
            var width = 100;
            if(this.fieldConfig.columns[i].width) {
                width = this.fieldConfig.columns[i].width;
            }

            var editor = null;
            var renderer = null;
            var listeners = null;

            if(this.fieldConfig.columns[i].type == "number" && !readOnly) {
                editor = new Ext.form.NumberField({});

            } else if(this.fieldConfig.columns[i].type == "text" && !readOnly) {
                editor = new Ext.form.TextField({});
            } else if(this.fieldConfig.columns[i].type == "select" && !readOnly) {
                var selectDataRaw = this.fieldConfig.columns[i].value.split(";");
                var selectData = [];
                for(var j = 0; j < selectDataRaw.length; j++) {
                    selectData.push([selectDataRaw[j], selectDataRaw[j]]);
                }

                editor = new Ext.form.ComboBox({
                    typeAhead: true,
                    forceSelection: true,
                    triggerAction: 'all',
                    lazyRender:true,
                    mode: 'local',

                    store: new Ext.data.ArrayStore({
                        fields: [
                            'value',
                            'label'
                        ],
                        data: selectData
                    }),
                    valueField: 'value',
                    displayField: 'label'
                });
            } else if(this.fieldConfig.columns[i].type == "bool") {
                if(!readOnly) {
                    columns.push(new Ext.grid.CheckColumn({
                        header: ts(this.fieldConfig.columns[i].label),
                        dataIndex: this.fieldConfig.columns[i].key,
                        width: width
                    }));
                    continue;
                }
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    metaData.css += ' x-grid3-check-col-td';
                    if(!value || value == "0") {
                        value = false;
                    }
                    return String.format('<div class="x-grid3-check-col{0}"'
                        + 'style="background-position:10px center;">&#160;</div>', value ? '-on' : '');
                };

            }

            columns.push({
                header: ts(this.fieldConfig.columns[i].label),
                dataIndex: this.fieldConfig.columns[i].key,
                editor: editor,
                renderer: renderer,
                listeners: listeners,
                sortable: true,
                width: width
            });
        }

        columns.push({
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
        });

        if(!readOnly) {
            columns.push({
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
            });
        }


        this.component = new Ext.grid.GridPanel({
            store: this.store,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            minHeight: 150,
            border: true,
            viewConfig: {
                forceFit: true
            },
            columns: [
                {dataIndex: 'id', header: 'ID', flex: 50},
                {dataIndex: "path", header: t("path"), flex: 200},
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
            ]
            ,
            cls: cls,
            //autoExpandColumn: 'path',
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
                        iconCls: "pimcore_icon_search",
                        handler: this.openSearchEditor.bind(this)
                    },
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_delete",
                        handler: this.empty.bind(this)
                    }
                ],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_objects"
        });

        this.component.on("rowcontextmenu", this.onRowContextmenu);
        this.component.reference = this;

        if(!readOnly) {
            this.component.on("afterrender", function () {

                var dropTargetEl = this.component.getEl();
                var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                    ddGroup    : 'element',
                    getTargetFromEvent: function(e) {
                        return this.component.getEl().dom;
                        //return e.getTarget(this.grid.getView().rowSelector);
                    }.bind(this),
                    onNodeOver: function (overHtmlNode, ddSource, e, data) {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(ddSource);

                        if (data.elementType == "object" && this.dndAllowed(data, fromTree)) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }

                    }.bind(this),
                    onNodeDrop : function(target, ddSource, e, data) {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(ddSource);

                        // check if data is a treenode, if not allow drop because of the reordering
                        if (!fromTree) {
                            return true;
                        }

                        if (data.elementType != "object") {
                            return false;
                        }

                        if (this.dndAllowed(data, fromTree)) {
                            var initData = {
                                id: data.id,
                                path: data.path,
                                type: data.className
                            };

                            if (!this.objectAlreadyExists(initData.id)) {
                                this.store.add(initData);
                                return true;
                            }
                        }
                        return false;
                    }.bind(this)
                });
            }.bind(this));
        }


        return this.component;
    },


    getLayoutEdit: function () {
        return this.createLayout(false);
    },

    getLayoutShow: function () {
        return this.createLayout(true);
    },


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
        });
    },

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
        }
    }
    ,


    dndAllowed: function(data, fromTree) {

        // check if data is a treenode, if not allow drop because of the reordering
        if (!fromTree) {
            return true;
        }

        // only allow objects not folders
        if (data.type == "folder") {
            return false;
        }

        //check if allowed classes config is set and if it applies
        if(this.fieldConfig.classes) {
            return (this.fieldConfig.classes.indexOf(data.className) > -1);
        }

        // allow all objects (temporary)
        return true;
    }
});