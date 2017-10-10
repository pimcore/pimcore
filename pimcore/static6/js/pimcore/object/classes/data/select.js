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
        localizedfield: true,
        classificationstore : true,
        block: true
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

        this.valueStore = new Ext.data.Store({

            fields: ["key", "value"],
            proxy:{
                type: 'memory'
            },
            data: this.datax.options
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.valueGrid = Ext.create('Ext.grid.Panel', {
            //enableDragDrop: true,
            viewConfig: {
                plugins: [
                    {
                        ptype: 'gridviewdragdrop',
                        dragroup: 'objectclassselect'
                    },
                ]
            },
            tbar: [{
                xtype: "tbtext",
                text: t("selection_options")
            }, "-", {
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    var u = {
                        key: "",
                        value: ""
                    };

                    var selectedRow = this.selectionModel.getSelected();
                    var idx;
                    if (selectedRow) {
                        idx = this.valueStore.indexOf(selectedRow) + 1;
                    } else {
                        idx = this.valueStore.getCount();
                    }
                    this.valueStore.insert(idx, u);
                    this.selectionModel.select(idx);
                }.bind(this)
            },
            {
                xtype: "button",
                iconCls: "pimcore_icon_edit",
                handler: this.showoptioneditor.bind(this)

            }],
            disabled: this.isInCustomLayoutEditor(),
            style: "margin-top: 10px",
            store: this.valueStore,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            clicksToEdit: 1,
            columnLines: true,
            columns: [
                {header: t("display_name"), sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({}),
                    width: 200},
                {header: t("value"), sortable: true, dataIndex: 'value', editor: new Ext.form.TextField({}),
                    width: 200},
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
                                    grid.getStore().insert(--rowIndex, [rec]);
                                    var sm = this.valueGrid.getSelectionModel();
                                    this.selectionModel.select(rowIndex);
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
                                    grid.getStore().insert(++rowIndex, [rec]);
                                    this.selectionModel.select(rowIndex);
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
                            tooltip: t('remove'),
                            icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }
                    ]
                }
            ],
            autoHeight: true,
            plugins: [
                this.cellEditing ]
        });


        this.selectionModel = this.valueGrid.getSelectionModel();
        this.valueGrid.on("afterrender", function () {

            var dropTargetEl = this.valueGrid.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'objectclassselect',
                getTargetFromEvent: function(e) {
                    return this.valueGrid.getEl().dom;
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {
                    try {
                        if (data["grid"] && data["grid"] == this.valueGrid) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }
                    } catch (e) {
                        console.log(e);
                    }
                    return Ext.dd.DropZone.prototype.dropNotAllowed;

                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {
                    try {
                        if (data["grid"] && data["grid"] == this.valueGrid) {
                            var rowIndex = this.valueGrid.getView().findRowIndex(e.target);
                            if (rowIndex !== false) {
                                var store = this.valueGrid.getStore();
                                var rec = store.getAt(data.rowIndex);
                                store.removeAt(data.rowIndex);
                                store.insert(rowIndex, [rec]);
                            }
                        }
                    } catch (e) {
                        console.log(e);
                    }
                    return false;
                }.bind(this)
            });
        }.bind(this));


        $super();

        this.specificPanel.removeAll();
        var items  = [];
        items.push({
            xtype: "numberfield",
            fieldLabel: t("width"),
            name: "width",
            value: this.datax.width
        });

        if (!this.isInCustomLayoutEditor() && !this.isInClassificationStoreEditor()) {
            items.push({
                xtype: "numberfield",
                fieldLabel: t("columnlength"),
                name: "columnLength",
                value: this.datax.columnLength
            });
        }


        items.push({
            xtype: "textfield",
            fieldLabel: t("default_value"),
            name: "defaultValue",
            value: this.datax.defaultValue
        });

        items.push(this.valueGrid);

        this.specificPanel.add(items);

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
    },

    showoptioneditor: function() {
        var editor = new pimcore.object.helpers.optionEditor(this.valueStore);
        editor.edit();
    }
});
