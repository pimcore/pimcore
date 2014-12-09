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

pimcore.registerNS("pimcore.object.classes.data.select");
pimcore.object.classes.data.select = Class.create(pimcore.object.classes.data.data, {

    type: "select",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "select";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("select");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_select";
    },

    getLayout: function ($super) {

        if(typeof this.datax.options != "object") {
            this.datax.options = [];
        }

        this.valueStore = new Ext.data.JsonStore({
            fields: ["key", "value"],
            data: this.datax.options
        });

        this.valueGrid = new Ext.grid.EditorGridPanel({
            enableDragDrop: true,
            ddGroup: 'objectclassselect',
            tbar: [{
                xtype: "tbtext",
                text: t("selection_options")
            }, "-", {
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    var u = new this.valueStore.recordType({
                        key: "",
                        value: ""
                    });

                    var selectedRow = this.selectionModel.getSelected();
                    var idx;
                    if (selectedRow) {
                        idx = this.valueStore.indexOf(selectedRow) + 1;
                    } else {
                        idx = this.valueStore.getCount();
                    }
                    this.valueStore.insert(idx, u);
                    this.selectionModel.selectRow(idx);
                }.bind(this)
            }],
            disabled: this.isInCustomLayoutEditor(),
            style: "margin-top: 10px",
            store: this.valueStore,
            selModel:new Ext.grid.RowSelectionModel({singleSelect:true}),
            columnLines: true,
            columns: [
                {header: t("display_name"), sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({}),
                    width: 200},
                {header: t("value"), sortable: true, dataIndex: 'value', editor: new Ext.form.TextField({}),
                    width: 200},
                {
                    xtype:'actioncolumn',
                    width:30,
                    items:[
                        {
                            tooltip:t('up'),
                            icon:"/pimcore/static/img/icon/arrow_up.png",
                            handler:function (grid, rowIndex) {
                                if (rowIndex > 0) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(--rowIndex, [rec]);
                                    var sm = this.valueGrid.getSelectionModel();
                                    this.selectionModel.selectRow(rowIndex);
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
                            icon:"/pimcore/static/img/icon/arrow_down.png",
                            handler:function (grid, rowIndex) {
                                if (rowIndex < (grid.getStore().getCount() - 1)) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(++rowIndex, [rec]);
                                    this.selectionModel.selectRow(rowIndex);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype: 'actioncolumn',
                    width: 30,
                    items: [
                        {
                            tooltip: t('remove'),
                            icon: "/pimcore/static/img/icon/cross.png",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }
                    ]
                }
            ],
            autoHeight: true
        });


        this.selectionModel = this.valueGrid.getSelectionModel();;
        this.valueGrid.on("afterrender", function () {

            var dropTargetEl = this.valueGrid.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'objectclassselect',
                getTargetFromEvent: function(e) {
                    return this.valueGrid.getEl().dom;
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {
                    if(data["grid"] && data["grid"] == this.valueGrid) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {
                    if(data["grid"] && data["grid"] == this.valueGrid) {
                        var rowIndex = this.valueGrid.getView().findRowIndex(e.target);
                        if(rowIndex !== false) {
                            var store = this.valueGrid.getStore();
                            var rec = store.getAt(data.rowIndex);
                            store.removeAt(data.rowIndex);
                            store.insert(rowIndex, [rec]);
                        }
                    }
                    return false;
                }.bind(this)
            });
        }.bind(this));


        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            this.valueGrid
        ]);

        return this.layout;
    },

    applyData: function ($super) {

        $super();

        var options = [];

        this.valueStore.commitChanges();
        this.valueStore.each(function (rec) {
            options.push({
                key: rec.get("key"),
                value: rec.get("value")
            });
        });

        this.datax.options = options;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    options: source.datax.options,
                    width: source.datax.width
                });
        }
    }
});
