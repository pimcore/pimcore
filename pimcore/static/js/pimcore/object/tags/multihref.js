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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.multihref");
pimcore.object.tags.multihref = Class.create(pimcore.object.tags.abstract, {

    type: "multihref",
    dataChanged:false,

    initialize: function (data, layoutConf) {
        this.data = [];
        this.layoutConf = layoutConf;

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
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }

            if (value.length > 0) {
                return value.join(",");
            }
        }.bind(this, field.key)};
    },

    getLayoutEdit: function() {

        var autoHeight = false;
        if (intval(this.layoutConf.height) < 15) {
            autoHeight = true;
        }
        var cls = 'object_field';

        this.grid = new Ext.grid.GridPanel({
            plugins: [new Ext.ux.dd.GridDragDropRowOrder({})],
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: [
                    {header: 'ID', dataIndex: 'id', width: 50},
                    {id: "path", header: t("path"), dataIndex: 'path', width: 200},
                    {header: t("type"), dataIndex: 'type', width: 100},
                    {header: t("subtype"), dataIndex: 'subtype', width: 100},
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('open'),
                            icon: "/pimcore/static/img/icon/pencil_go.png",
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
                        width: 30,
                        items: [{
                            tooltip: t('remove'),
                            icon: "/pimcore/static/img/icon/cross.png",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }]
                    }
                ]
            }),
            cls: cls,
            autoExpandColumn: 'path',
            tbar: [
                {
                    xtype: "tbspacer",
                    width: 20,
                    height: 16,
                    cls: "pimcore_icon_droptarget"
                },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.layoutConf.title + "</b>"
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
            ],
            width: this.layoutConf.width,
            height: this.layoutConf.height,
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_multihref"
        });

        this.grid.on("rowcontextmenu", this.onRowContextmenu);
        this.grid.reference = this;

        this.grid.on("afterrender", function () {

            var dropTargetEl = this.grid.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'element',
                getTargetFromEvent: function(e) {
                    return this.grid.getEl().dom;
                    //return e.getTarget(this.grid.getView().rowSelector);
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {


                    if (this.dndAllowed(data)) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                    else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }


                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {

                    // check if data is a treenode, if not allow drop because of the reordering
                    if (!this.sourceIsTreeNode(data)) {
                        return true;
                    }

                    if (this.dndAllowed(data)) {
                        var initData = {
                            id: data.node.attributes.id,
                            path: data.node.attributes.path,
                            type: data.node.attributes.elementType
                        };

                        if (initData.type == "object") {
                            if (data.node.attributes.className) {
                                initData.subtype = data.node.attributes.className;
                            }
                            else {
                                initData.subtype = "folder";
                            }
                        }

                        if (initData.type == "document" || initData.type == "asset") {
                            initData.subtype = data.node.attributes.type;
                        }

                        // check for existing element
                        if (!this.elementAlreadyExists(initData.id, initData.type)) {
                            this.store.add(new this.store.recordType(initData, this.store.getCount() + 1));
                            return true;
                        }
                        return false;
                    } else {
                        return false;
                    }
                }.bind(this)
            });
        }.bind(this));

        return this.grid;

    },



    getLayoutShow: function () {

        this.grid = new Ext.grid.GridPanel({
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: [
                    {header: 'ID', dataIndex: 'id', width: 50},
                    {id: "path", header: t("path"), dataIndex: 'path', width: 200},
                    {header: t("type"), dataIndex: 'type', width: 100},
                    {header: t("subtype"), dataIndex: 'subtype', width: 100},
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('open'),
                            icon: "/pimcore/static/img/icon/pencil_go.png",
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
                ]
            }),
            width: 450,
            height: 150,
            cls: "multihref_field",
            autoExpandColumn: 'path',
            title: this.layoutConf.title
        });

        return this.grid;
    },

    onRowContextmenu: function (grid, rowIndex, event) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);

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

        event.stopEvent();
        menu.showAt(event.getXY());
    },

    openSearchEditor: function () {

        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};

        if (this.layoutConf.objectsAllowed) {
            allowedTypes.push("object");
            if (this.layoutConf.classes != null && this.layoutConf.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object = ["object"];
                for (i = 0; i < this.layoutConf.classes.length; i++) {
                    allowedSpecific.classes.push(this.layoutConf.classes[i].classes);
                }
            } else {
                allowedSubtypes.object = ["object","folder","variant"];
            }
        }
        if (this.layoutConf.assetsAllowed) {
            allowedTypes.push("asset");
            if (this.layoutConf.assetTypes != null && this.layoutConf.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < this.layoutConf.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(this.layoutConf.assetTypes[i].assetTypes);
                }
            }
        }
        if (this.layoutConf.documentsAllowed) {
            allowedTypes.push("document");
            if (this.layoutConf.documentTypes != null && this.layoutConf.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < this.layoutConf.documentTypes.length; i++) {
                    allowedSubtypes.document.push(this.layoutConf.documentTypes[i].documentTypes);
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

                    this.store.add(new this.store.recordType({
                        id: items[i].id,
                        path: items[i].fullpath,
                        type: items[i].type,
                        subtype: subtype
                    }, this.store.getCount() + 1));
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
        if(this.layoutConf.lazyLoading && !this.dataChanged){
            return false;
        }

        var data = this.store.queryBy(function(record, id) {
            return true;
        });


        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }

        return tmData;
    },

    getName: function () {
        return this.layoutConf.name;
    },

    sourceIsTreeNode: function (source) {
        try {
            if (source.node) {
                return true;
            }
        } catch (e) {
            return false;
        }
        return false;
    },

    dndAllowed: function(data) {

        // check if data is a treenode, if not allow drop because of the reordering
        if (!this.sourceIsTreeNode(data)) {
            return true;
        }

        var type = data.node.attributes.elementType;
        var isAllowed = false;
        if (type == "object" && this.layoutConf.objectsAllowed) {

            var classname = data.node.attributes.className;
            var isAllowed = false;
            if (this.layoutConf.classes != null && this.layoutConf.classes.length > 0) {
                for (i = 0; i < this.layoutConf.classes.length; i++) {
                    if (this.layoutConf.classes[i].classes == classname) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no classes configured - allow all
                isAllowed = true;
            }


        } else if (type == "asset" && this.layoutConf.assetsAllowed) {
            var subType = data.node.attributes.type;
            var isAllowed = false;
            if (this.layoutConf.assetTypes != null && this.layoutConf.assetTypes.length > 0) {
                for (i = 0; i < this.layoutConf.assetTypes.length; i++) {
                    if (this.layoutConf.assetTypes[i].assetTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no asset types configured - allow all
                isAllowed = true;
            }

        } else if (type == "document" && this.layoutConf.documentsAllowed) {
            var subType = data.node.attributes.type;
            var isAllowed = false;
            if (this.layoutConf.documentTypes != null && this.layoutConf.documentTypes.length > 0) {
                for (i = 0; i < this.layoutConf.documentTypes.length; i++) {
                    if (this.layoutConf.documentTypes[i].documentTypes == subType) {
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
        return this.dataChanged;
    } 
});