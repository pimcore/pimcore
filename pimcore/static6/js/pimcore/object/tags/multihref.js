/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.multihref");
pimcore.object.tags.multihref = Class.create(pimcore.object.tags.abstract, {

    type: "multihref",
    dataChanged:false,

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
                "type",
                "subtype"
            ]
        });
    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key,
                renderer: function (key, value, metaData, record) {
                                this.applyPermissionStyle(key, value, metaData, record);

                                if(record.data.inheritedFields[key]
                                                        && record.data.inheritedFields[key].inherited == true) {
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

    getLayoutEdit: function() {

        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }
        var cls = 'object_field';

        var toolbarItems = [
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
            }
        ];

        if (this.fieldConfig.assetsAllowed) {
            toolbarItems.push({
                xtype: "button",
                cls: "pimcore_inline_upload",
                iconCls: "pimcore_icon_upload_single",
                handler: this.uploadDialog.bind(this)
            });
        }

        this.component = new Ext.grid.GridPanel({
            store: this.store,
            border: true,
            style: "margin-bottom: 10px",

            selModel: Ext.create('Ext.selection.RowModel', {}),
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop',
                    dragroup: 'element'
                }
                //},
                //listeners: {
                //    drop: function(node, data, dropRec, dropPosition) {
                //        var dropOn = dropRec ? ' ' + dropPosition + ' ' + dropRec.get('name') : ' on empty view';
                //        //Ext.example.msg('Drag from left to right', 'Dropped ' + data.records[0].get('name') + dropOn);
                //    }
                //}
            },
            columns: {
                defaults: {
                    sortable: false
                },
                items: [
                    {header: 'ID', dataIndex: 'id', width: 50},
                    {header: t("path"), dataIndex: 'path', flex: 200},
                    {header: t("type"), dataIndex: 'type', width: 100},
                    {header: t("subtype"), dataIndex: 'subtype', width: 100},
                    {
                        xtype:'actioncolumn',
                        width:40,
                        items:[
                            {
                                tooltip:t('up'),
                                icon:"/pimcore/static6/img/icon/arrow_up.png",
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
                                icon:"/pimcore/static6/img/icon/arrow_down.png",
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
                        items: [{
                            tooltip: t('open'),
                            icon: "/pimcore/static6/img/icon/pencil_go.png",
                            handler: function (grid, rowIndex) {
                                var data = grid.getStore().getAt(rowIndex);
                                var subtype = data.data.subtype;
                                if (data.data.type == "object" && data.data.subtype != "folder") {
                                    subtype = "object";
                                }
                                pimcore.helpers.openElement(data.data.id, data.data.type, subtype);
                            }.bind(this)
                        }]
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 40,
                        items: [{
                            tooltip: t('remove'),
                            icon: "/pimcore/static6/img/icon/cross.png",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }]
                    }
                ]
            },
            componentCls: cls,
            tbar: {
                items: toolbarItems,
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_multihref"
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
                        }
                        else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {
                    try {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(dd);

                        if (this.dndAllowed(data, fromTree)) {
                            if (data["grid"] && data["grid"] == this.component) {
                                var rowIndex = this.component.getView().findRowIndex(e.target);
                                if (rowIndex !== false) {
                                    var rec = this.store.getAt(data.rowIndex);
                                    this.store.removeAt(data.rowIndex);
                                    this.store.insert(rowIndex, [rec]);
                                }
                            } else {
                                var initData = {
                                    id: data.id,
                                    path: data.path,
                                    type: data.elementType
                                };

                                if (initData.type == "object") {
                                    if (data.className) {
                                        initData.subtype = data.className;
                                    }
                                    else {
                                        initData.subtype = "folder";
                                    }
                                }

                                if (initData.type == "document" || initData.type == "asset") {
                                    initData.subtype = data.type;
                                }

                                // check for existing element
                                if (!this.elementAlreadyExists(initData.id, initData.type)) {
                                    this.store.add(initData);
                                    return true;
                                }
                            }
                            return false;
                        } else {
                            return false;
                        }
                    } catch (e) {
                        console.log(e);
                    }
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

        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            columns: [
                {header: 'ID', dataIndex: 'id', width: 50, sortable: false},
                {header: t("path"), dataIndex: 'path', width: 200, sortable: false},
                {header: t("type"), dataIndex: 'type', width: 100, sortable: false},
                {header: t("subtype"), dataIndex: 'subtype', width: 100, sortable: false},
                {
                    xtype: 'actioncolumn',
                    width: 40,
                    items: [{
                        tooltip: t('open'),
                        icon: "/pimcore/static6/img/icon/pencil_go.png",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            var subtype = data.data.subtype;
                            if (data.data.type == "object" && data.data.subtype != "folder") {
                                subtype = "object";
                            }
                            pimcore.helpers.openElement(data.data.id, data.data.type, subtype);
                        }.bind(this)
                    }]
                }
            ],
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            cls: "multihref_field",
            autoExpandColumn: 'path',
            border: true,
            style: "margin-bottom: 10px",
            title: this.fieldConfig.title
        });

        return this.component;
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.fieldConfig.assetUploadPath, "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if(data["id"]) {
                    this.store.add({
                        id: data["id"],
                        path: data["fullpath"],
                        type: "asset",
                        subtype: data["type"]
                    });
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        var menu = new Ext.menu.Menu();
        var data = record;

        menu.add(new Ext.menu.Item({
            text: t('remove'),
            iconCls: "pimcore_icon_delete",
            handler: this.reference.removeElement.bind(this, rowIndex)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (data, item) {

                item.parentMenu.destroy();

                var subtype = data.data.subtype;
                if (data.data.type == "object" && data.data.subtype != "folder") {
                    subtype = "object";
                }
                pimcore.helpers.openElement(data.data.id, data.data.type, subtype);
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
    },

    openSearchEditor: function () {

        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};
        var i;

        if (this.fieldConfig.objectsAllowed) {
            allowedTypes.push("object");
            if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object = ["object"];
                for (i = 0; i < this.fieldConfig.classes.length; i++) {
                    allowedSpecific.classes.push(this.fieldConfig.classes[i].classes);
                }
            } else {
                allowedSubtypes.object = ["object","folder","variant"];
            }
        }
        if (this.fieldConfig.assetsAllowed) {
            allowedTypes.push("asset");
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(this.fieldConfig.assetTypes[i].assetTypes);
                }
            }
        }
        if (this.fieldConfig.documentsAllowed) {
            allowedTypes.push("document");
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    allowedSubtypes.document.push(this.fieldConfig.documentTypes[i].documentTypes);
                }
            }
        }

        pimcore.helpers.itemselector(true, this.addDataFromSelector.bind(this), {
            type: allowedTypes,
            subtype: allowedSubtypes,
            specific: allowedSpecific
        });

    },

    elementAlreadyExists: function (id, type) {

        // check max amount in field
        if(this.fieldConfig["maxItems"] && this.fieldConfig["maxItems"] >= 1) {
            if(this.store.getCount() >= this.fieldConfig.maxItems) {
                Ext.Msg.alert(t("error"),t("limit_reached"));
                return true;
            }
        }

        // check for existing element
        var result = this.store.queryBy(function (id, type, record, rid) {
            if (record.data.id == id && record.data.type == type) {
                return true;
            }
            return false;
        }.bind(this, id, type));

        if (result.length < 1) {
            return false;
        }
        return true;
    },

    addDataFromSelector: function (items) {
        if (items.length > 0) {
            for (var i = 0; i < items.length; i++) {
                if (!this.elementAlreadyExists(items[i].id, items[i].type)) {

                    var subtype = items[i].subtype;
                    if (items[i].type == "object") {
                        if (items[i].subtype == "object") {
                            if (items[i].classname) {
                                subtype = items[i].classname;
                            }
                        }
                    }

                    this.store.add({
                        id: items[i].id,
                        path: items[i].fullpath,
                        type: items[i].type,
                        subtype: subtype
                    });
                }
            }
        }
    },

    empty: function () {
        this.store.removeAll();
    },

    removeElement: function (index, item) {
        this.getStore().removeAt(index);
        item.parentMenu.destroy();
    },


    isInvalidMandatory: function () {

        var data = this.store.queryBy(function(record, id) {
            return true;
        });
        if(data.items.length < 1){
            return true;
        }
        return false;
    },

    getValue: function () {

        var tmData = [];

        var data = this.store.queryBy(function(record, id) {
            return true;
        });


        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }

        return tmData;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    dndAllowed: function(data, fromTree) {

        var i;

        // check if data is a treenode, if not check if the source is the same grid because of the reordering
        if (!fromTree) {
            if(data["grid"] && data["grid"] == this.component) {
                return true;
            }
            return false;
        }

        var type = data.elementType;
        var isAllowed = false;
        var subType;

        if (type == "object" && this.fieldConfig.objectsAllowed) {

            var classname = data.className;
            isAllowed = false;
            if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                for (i = 0; i < this.fieldConfig.classes.length; i++) {
                    if (this.fieldConfig.classes[i].classes == classname) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no classes configured - allow all
                isAllowed = true;
            }


        } else if (type == "asset" && this.fieldConfig.assetsAllowed) {
            subType = data.type;
            isAllowed = false;
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    if (this.fieldConfig.assetTypes[i].assetTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no asset types configured - allow all
                isAllowed = true;
            }

        } else if (type == "document" && this.fieldConfig.documentsAllowed) {
            subType = data.type;
            isAllowed = false;
            console.log(this.fieldConfig.documentTypes);
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    if (this.fieldConfig.documentTypes[i].documentTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no document types configured - allow all
                isAllowed = true;
            }
        }
        return isAllowed;

    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        return this.dataChanged;
    } 
});