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

pimcore.registerNS("pimcore.document.editables.relations");
pimcore.document.editables.relations = Class.create(pimcore.document.editable, {

    initialize: function (id, name, config, data, inherited) {
        this.id = id;
        this.name = name;

        this.config = this.parseConfig(config);

        var modelName = 'DocumentsMultihrefEntry';
        if (!Ext.ClassManager.isCreated(modelName)) {
            Ext.define(modelName, {
                extend: 'Ext.data.Model',
                idProperty: 'rowId',
                fields: [
                    'id',
                    'path',
                    'type',
                    'subtype'
                ]
            });
        }

        this.store = new Ext.data.ArrayStore({
            data: data,
            model: modelName
        });
    },

    render: function () {
        this.setupWrapper();

        var tbar = [
            Ext.create('Ext.toolbar.Spacer', {
                width: 24,
                height: 24,
                cls: "pimcore_icon_droptarget"
            }),
            Ext.create('Ext.toolbar.TextItem', {
                text: "<b>" + (this.config.title ? this.config.title : "") + "</b>"
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
            }
        ];

        if (this.canInlineUpload()) {
            tbar.push({
                xtype: "button",
                cls: "pimcore_inline_upload",
                iconCls: "pimcore_icon_upload",
                handler: this.uploadDialog.bind(this)
            });
        }

        var elementConfig = {
            store: this.store,
            bodyStyle: "color:#000",
            selModel: Ext.create('Ext.selection.RowModel', {}),

            columns: {
                defaults: {
                    sortable: false
                },
                items: [
                    {text: 'ID', dataIndex: 'id', width: 50},
                    {text: t("path"), dataIndex: 'path', flex: 200},
                    {text: t("type"), dataIndex: 'type', width: 100},
                    {text: t("subtype"), dataIndex: 'subtype', width: 100},
                    {
                        xtype: 'actioncolumn',
                        menuText: t('up'),
                        width: 30,
                        items: [
                            {
                                tooltip: t('up'),
                                icon: "/bundles/pimcoreadmin/img/flat-color-icons/up.svg",
                                handler: function (grid, rowIndex) {
                                    if (rowIndex > 0) {
                                        var rec = grid.getStore().getAt(rowIndex);
                                        grid.getStore().removeAt(rowIndex);
                                        grid.getStore().insert(rowIndex - 1, [rec]);

                                        if (this.config.reload) {
                                            this.reloadDocument();
                                        }
                                    }
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('down'),
                        width: 30,
                        items: [
                            {
                                tooltip: t('down'),
                                icon: "/bundles/pimcoreadmin/img/flat-color-icons/down.svg",
                                handler: function (grid, rowIndex) {
                                    if (rowIndex < (grid.getStore().getCount() - 1)) {
                                        var rec = grid.getStore().getAt(rowIndex);
                                        grid.getStore().removeAt(rowIndex);
                                        grid.getStore().insert(rowIndex + 1, [rec]);

                                        if (this.config.reload) {
                                            this.reloadDocument();
                                        }
                                    }
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('open'),
                        width: 30,
                        items: [{
                            tooltip: t('open'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
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
                        menuText: t('remove'),
                        width: 30,
                        items: [{
                            tooltip: t('remove'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);

                                if (this.config.reload) {
                                    this.reloadDocument();
                                }
                            }.bind(this)
                        }]
                    }
                ]
            },
            tbar: {
                items: tbar
            }
        };

        // height specifics
        if (typeof this.config.height != "undefined") {
            elementConfig.height = this.config.height;
        } else {
            elementConfig.autoHeight = true;
        }

        // width specifics
        if (typeof this.config.width != "undefined") {
            elementConfig.width = this.config.width;
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

        this.element.render(this.id);
    },

    canInlineUpload: function() {
        if(this.config["disableInlineUpload"] === true) {
            return false;
        }

        // no assets allowed, disable inline upload
        if(this.config["types"] && this.config["types"].length && this.config["types"].indexOf("asset") === -1) {
            return false;
        }

        return true;
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.config["uploadPath"], "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if (data["id"]) {
                    this.store.add({
                        id: data["id"],
                        path: data["fullpath"],
                        type: "asset",
                        subtype: data["type"]
                    });

                    if (this.config.reload) {
                        this.reloadDocument();
                    }
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    onNodeOver: function (target, dd, e, data) {
        var returnValue = Ext.dd.DropZone.prototype.dropAllowed;
        data.records.forEach(function (record) {
            record = this.getCustomPimcoreDropData(record);
            if (!this.dndAllowed(record)) {
                returnValue = Ext.dd.DropZone.prototype.dropNotAllowed;
            }
        }.bind(this));

        return returnValue;
    },

    onNodeDrop: function (target, dd, e, data) {

        var elementsToAdd = [];

        data.records.forEach(function (record) {
            if (!this.dndAllowed(this.getCustomPimcoreDropData(record))) {
                return false;
            }

            var data = record.data;

            var initData = {
                id: data.id,
                path: data.path,
                type: data.elementType
            };

            if (initData.type === "object") {
                if (data.className) {
                    initData.subtype = data.className;
                }
                else {
                    initData.subtype = "folder";
                }
            }

            if (initData.type === "document" || initData.type === "asset") {
                initData.subtype = data.type;
            }

            // check for existing element
            if (!this.elementAlreadyExists(initData.id, initData.type)) {
                elementsToAdd.push(initData);
            }
        }.bind(this));

        if(elementsToAdd.length) {
            this.store.add(elementsToAdd);

            if (this.config.reload) {
                this.reloadDocument();
            }

            return true;
        }

        return false;

    },

    dndAllowed: function (data) {

        var i;
        var found;

        var checkSubType = false;
        var checkClass = false;
        var type;

        //only is legacy
        if (this.config.only && !this.config.types) {
            this.config.types = [this.config.only];
        }

        //type check   (asset,document,object)
        if (this.config.types) {
            found = false;
            for (i = 0; i < this.config.types.length; i++) {
                type = this.config.types[i];
                if (type == data.data.elementType) {
                    found = true;

                    if (this.config.subtypes && this.config.subtypes[type] && this.config.subtypes[type].length) {
                        checkSubType = true;
                    }
                    if (data.data.elementType == "object" && this.config.classes) {
                        checkClass = true;
                    }
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }

        //subtype check  (folder,page,snippet ... )
        if (checkSubType) {

            found = false;
            var subTypes = this.config.subtypes[type];
            for (i = 0; i < subTypes.length; i++) {
                if (subTypes[i] == data.data.type) {
                    found = true;
                    if (data.data.type == "folder" && checkClass) {
                        checkClass = false;
                    }
                    break;
                }

            }
            if (!found) {
                return false;
            }
        }

        //object class check
        if (checkClass) {
            found = false;
            for (i = 0; i < this.config.classes.length; i++) {
                if (this.config.classes[i] == data.data.className) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }

        return true;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

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

        if (pimcore.elementservice.showLocateInTreeButton("document")) {
            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: function (item) {
                    item.parentMenu.destroy();
                    pimcore.treenodelocator.showInTree(record.data.id, record.data.type);
                }.bind(this)
            }));
        }

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

        pimcore.helpers.itemselector(true, this.addDataFromSelector.bind(this), {
                type: this.config.types,
                subtype: this.config.subtypes,
                specific: {
                    classes: this.config["classes"]
                }
            },
            {
                context: this.getContext()
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

                    this.store.add({
                        id: items[i].id,
                        path: items[i].fullpath,
                        type: items[i].type,
                        subtype: subtype
                    });

                    if (this.config.reload) {
                        this.reloadDocument();
                    }
                }
            }
        }
    },

    empty: function () {
        this.store.removeAll();

        if (this.config.reload) {
            this.reloadDocument();
        }
    },

    removeElement: function (index, item) {
        this.store.removeAt(index);
        item.parentMenu.destroy();

        if (this.config.reload) {
            this.reloadDocument();
        }
    },

    getValue: function () {
        var tmData = [];

        var data = this.store.queryBy(function (record, id) {
            return true;
        });


        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }

        return tmData;
    },

    getType: function () {
        return "relations";
    }
});
