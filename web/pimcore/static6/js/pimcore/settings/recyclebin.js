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

pimcore.registerNS("pimcore.settings.recyclebin");
pimcore.settings.recyclebin = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_recyclebin");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_recyclebin",
                title: t("recyclebin"),
                border: false,
                iconCls: "pimcore_icon_recyclebin",
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_recyclebin");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("recyclebin");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getGrid: function () {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        this.store = pimcore.helpers.grid.buildDefaultStore(
            '/admin/recyclebin/list?',
            [
                {name: 'id'},
                {name: 'type'},
                {name: 'subtype'},
                {name: 'path'},
                {name: 'amount'},
                {name: 'deletedby'},
                {name: 'date'}
            ],
            itemsPerPage
        );

        this.store.addListener('load', function () {
            if(this.store.getCount() > 0) {
                Ext.getCmp("pimcore_recyclebin_button_flush").enable();
            }
        }.bind(this));


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filterFullText = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var typesColumns = [
            {header: t("type"), width: 50, sortable: true, dataIndex: 'subtype', renderer: function(d) {
                return '<img src="/pimcore/static6/img/flat-color-icons/' + d + '.svg" style="height: 16px" />';
            }},
            {header: t("path"), flex: 200, sortable: true, dataIndex: 'path', filter: 'string'},
            {header: t("amount"), flex: 60, sortable: true, dataIndex: 'amount'},
            {header: t("deletedby"), flex:80,sortable: true, dataIndex: 'deletedby', filter: 'string'},
            {header: t("date"), flex: 140, sortable: true, dataIndex: 'date',
                renderer: function(d) {
                    var date = new Date(d * 1000);
                    return Ext.Date.format(date, "Y-m-d H:i:s");
                },
                filter: 'date'

            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            }
        ];

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [
                {
                    text: t('restore'),
                    handler: this.onRestore.bind(this),
                    iconCls: "pimcore_icon_restore",
                    id: "pimcore_recyclebin_button_restore",
                    disabled: true
                },'-',{
                    text: t('delete'),
                    handler: this.onDelete.bind(this),
                    iconCls: "pimcore_icon_delete",
                    id: "pimcore_recyclebin_button_delete",
                    disabled: true
                },"-",{
                    text: t('flush_recyclebin'),
                    handler: this.onFlush.bind(this),
                    iconCls: "pimcore_icon_flush_recyclebin",
                    id: "pimcore_recyclebin_button_flush",
                    disabled: true
                },
                '->',{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            bbar: this.pagingtoolbar,
            stripeRows: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: ['pimcore.gridfilters'],
            columns : typesColumns,
            tbar: toolbar,
            listeners: {
                "rowclick": function () {
                    var rec = this.grid.getSelectionModel().getSelected();
                    if (!rec) {
                        Ext.getCmp("pimcore_recyclebin_button_restore").disable();
                        Ext.getCmp("pimcore_recyclebin_button_delete").disable();
                    } else {
                        Ext.getCmp("pimcore_recyclebin_button_restore").enable();
                        Ext.getCmp("pimcore_recyclebin_button_delete").enable();
                    }
                }.bind(this)
            },
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },


    onFlush: function (btn, ev) {
        Ext.Ajax.request({
            url: "/admin/recyclebin/flush",
            success: function () {
                this.store.reload();
                this.grid.getView().refresh();
            }.bind(this)
        });
    },

    onDelete: function () {
        var selections = this.grid.getSelectionModel().getSelected();
        if (!selections || selections.getCount() == 0 ) {
            return false;
        }
        var rec = selections.getAt(0);

        this.grid.store.remove(rec);

        Ext.getCmp("pimcore_recyclebin_button_restore").disable();
        Ext.getCmp("pimcore_recyclebin_button_delete").disable();
    },

    onRestore: function () {

        pimcore.helpers.loadingShow();

        var selections = this.grid.getSelectionModel().getSelected();
        if (!selections || selections.getCount() == 0 ) {
            return false;
        }
        var rec = selections.getAt(0);

        Ext.Ajax.request({
            url: "/admin/recyclebin/restore",
            params: {
                id: rec.data.id
            },
            success: function () {
                this.store.reload();
                this.grid.getView().refresh();

                // refresh all trees
                try {
                    if(pimcore.globalmanager.get("layout_document_tree").tree.rendered) {
                        var tree = pimcore.globalmanager.get("layout_document_tree").tree;
                        tree.getStore().load({
                            node: tree.getRootNode()
                        });
                    }
                    if(pimcore.globalmanager.get("layout_asset_tree").tree.rendered) {
                        var tree = pimcore.globalmanager.get("layout_asset_tree").tree;
                        tree.getStore().load({
                            node: tree.getRootNode()
                        });

                    }
                    if(pimcore.globalmanager.get("layout_object_tree").tree.rendered) {
                        var tree = pimcore.globalmanager.get("layout_object_tree").tree;
                        tree.getStore().load({
                            node: tree.getRootNode()
                        });
                    }
                }
                catch (e) {
                    console.log(e);
                }

                pimcore.helpers.loadingHide();
            }.bind(this)
        });

        Ext.getCmp("pimcore_recyclebin_button_restore").disable();
        Ext.getCmp("pimcore_recyclebin_button_delete").disable();
    }
});
