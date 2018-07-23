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


pimcore.registerNS("pimcore.object.classes.data.indexFieldSelection");
pimcore.object.classes.data.indexFieldSelection = Class.create(pimcore.object.classes.data.data, {

    type: "indexFieldSelection",
    allowIndex: true,

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
        this.type = "indexFieldSelection";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("indexFieldSelection");
    },

    getGroup: function () {
        return "ecommerce";
    },

    getIconClass: function () {
        return "pimcore_icon_indexFieldSelection";
    },

    getLayout: function ($super) {

        $super();
        this.specificPanel.removeAll();

        var filterGroups = Ext.create('Ext.ux.form.MultiSelect', {
            triggerAction: "all",
            fieldLabel: t("filtergroups"),
            editable: false,
            name: "filterGroups",
            store: new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/ecommerceframework/index/get-filter-groups',
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'key'
                    }
                },
                listeners: {
                    load: function(store) {
                        filterGroups.setValue(this.datax.filterGroups);
                    }.bind(this)
                },
                fields: ['data']
            }),
            valueField: 'data',
            displayField: 'data',
            itemCls: "object_field",
            width: 500
        });

        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "checkbox",
                fieldLabel: t("considerTenants"),
                name: "considerTenants",
                checked: this.datax.considerTenants
            },
            filterGroups,
            {
                xtype: "combo",
                triggerAction: "all",
                fieldLabel: t("preSelectMode"),
                editable: false,
                name: "multiPreSelect",
                mode: 'local',
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'key',
                        'value'
                    ],
                    data: [
                        ['none', t('none')],
                        ['remote_single', t('remote_single')],
                        ['remote_multi', t('remote_multi')],
                        ['local_single', t('local_single')],
                        ['local_multi', t('local_multi')]
                    ]
                }),
                valueField: 'key',
                displayField: 'value',
                width: 500,
                listeners: {
                    select: function(combo, rec) {
                        if(rec.data.key == "local_single" || rec.data.key == "local_multi") {
                            this.valueGrid.setVisible(true);
                        } else {
                            this.valueGrid.setVisible(false);
                        }

                    }.bind(this)
                },
                value: this.datax.multiPreSelect
            },
            this.getPredefinedListGrid()

        ]);

        return this.layout;
    },

    getPredefinedListGrid: function() {
        if(typeof this.datax.predefinedPreSelectOptions != "object") {
            console.log("dd");
            this.datax.predefinedPreSelectOptions = [];
        }

        this.valueStore = new Ext.data.JsonStore({
            fields: ["key", "value"],
            data: this.datax.predefinedPreSelectOptions
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.valueGrid = Ext.create('Ext.grid.Panel', {
            tbar: [{
                xtype: "tbtext",
                text: t("predefined_pre_select_options")
            }, "-", {
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    this.valueStore.insert(0, {
                        key: "",
                        value: ""
                    });
                }.bind(this)
            }],
            style: "margin-top: 10px",
            store: this.valueStore,
            hidden: (this.datax.multiPreSelect != "local_single" && this.datax.multiPreSelect != "local_multi"),
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            columnLines: true,
            columns: [
                {header: t("display_name"), sortable: false, dataIndex: 'key', editor: new Ext.form.TextField({}),
                    width: 200},
                {header: t("value"), sortable: false, dataIndex: 'value', editor: new Ext.form.TextField({}),
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
                            tooltip: t('remove'),
                            icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }
                    ]
                }
            ],
            autoHeight: true
        });

        return this.valueGrid;
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

        this.datax.predefinedPreSelectOptions = options;
    }
});
