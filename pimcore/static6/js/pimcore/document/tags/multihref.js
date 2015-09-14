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

pimcore.registerNS("pimcore.document.tags.multihref");
pimcore.document.tags.multihref = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;

        this.options = this.parseOptions(options);
        this.data = data;

        this.setupWrapper();

        this.store = new Ext.data.ArrayStore({
            data: this.data,
            fields: [
                "id",
                "path",
                "type",
                "subtype"
            ]
        });

        var elementConfig = {
            store: this.store,
            bodyStyle: "color:#000",
            selModel: Ext.create('Ext.selection.RowModel', {}),
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
                        width:30,
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
                        width:30,
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
                        width: 30,
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
                        width: 30,
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
            tbar: {
                items: [
                    Ext.create('Ext.toolbar.Spacer', {
                        width: 20,
                        height: 16,
                        cls: "pimcore_icon_droptarget"
                    }),
                    Ext.create('Ext.toolbar.TextItem', {
                        text: "<b>" + (this.options.title ? this.options.title : "") + "</b>"
                    }),
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
                    {
                        xtype: "button",
                        cls: "pimcore_inline_upload",
                        iconCls: "pimcore_icon_upload_single",
                        handler: this.uploadDialog.bind(this)
                    }
                ]
            }
        };

        // height specifics
        if(typeof this.options.height != "undefined") {
            elementConfig.height = this.options.height;
        } else {
            elementConfig.autoHeight = true;
        }

        // width specifics
        if(typeof this.options.width != "undefined") {
            elementConfig.width = this.options.width;
        }

        this.element = new Ext.grid.GridPanel(elementConfig);

        this.element.on("rowcontextmenu", this.onRowContextmenu.bind(this));
        //this.element.reference = this;

        this.element.on("render", function (el) {
            // register at global DnD manager
            dndManager.addDropTarget(this.element.getEl(),
                this.onNodeOver.bind(this),
                this.onNodeDrop.bind(this));

        }.bind(this));

        this.element.render(id);
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.options["uploadPath"], "path", function (res) {
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

    onNodeOver: function(target, dd, e, data) {
        return Ext.dd.DropZone.prototype.dropAllowed;
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];
        var data = record.data;

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
        return false;

    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        var menu = new Ext.menu.Menu();

        menu.add(new Ext.menu.Item({
            text: t('remove'),
            iconCls: "pimcore_icon_delete",
            handler: this.removeElement.bind(this, rowIndex)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (record, item) {

                item.parentMenu.destroy();

                var subtype = record.data.subtype;
                if (record.data.type == "object" && record.data.subtype != "folder") {
                    subtype = "object";
                }
                pimcore.helpers.openElement(record.data.id, record.data.type, subtype);
            }.bind(this, record)
        }));

        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    openSearchEditor: function () {

        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};


        pimcore.helpers.itemselector(true, this.addDataFromSelector.bind(this), {});

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
        this.store.removeAt(index);
        item.parentMenu.destroy();
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

    getType: function () {
        return "multihref";
    }
});