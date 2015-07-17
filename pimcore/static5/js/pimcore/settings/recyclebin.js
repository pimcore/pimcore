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


        Ext.define('pimcore.model.recyclebin', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'id'},
                {name: 'type'},
                {name: 'subtype'},
                {name: 'path'},
                {name: 'amount'},
                {name: 'deletedby'},
                {name: 'date'}
            ],
            proxy: {
                type: 'ajax',
                url: '/admin/recyclebin/list',
                extraParams: {
                    limit: itemsPerPage,
                    filterFullText: ""
                },
                // Reader is now on the proxy, as the message was explaining
                reader: {
                    rootProperty: 'data',
                    type: 'json'
                    //totalProperty:'total',            // default
                    //successProperty:'success'         // default
                }
                //,                                     // default
                //writer: {
                //    type: 'json'
                //}
            }
        });

        var itemsPerPage = 20;

        this.store = new Ext.data.Store({
            model: 'pimcore.model.recyclebin',
            pageSize: itemsPerPage,
            remoteSort: true,
            listeners: {
                load: function () {
                    if(this.store.getCount() > 0) {
                        Ext.getCmp("pimcore_recyclebin_button_flush").enable();
                    }
                }.bind(this)
            }
        });
        this.store.load();


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

        this.pagingtoolbar = Ext.create('Ext.toolbar.Paging', {
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        var combo = Ext.create('Ext.form.field.ComboBox', {
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 50,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    var store = this.pagingtoolbar.getStore();
                    store.setPageSize(intval(rec.data.field1));
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });

        this.pagingtoolbar.add(combo);

        var typesColumns = [
            {header: t("type"), flex: 50, sortable: true, dataIndex: 'subtype', renderer: function(d) {
                return '<img src="/pimcore/static/img/icon/' + d + '.png" />';
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
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            }
        ];


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            bbar: this.pagingtoolbar,
            stripeRows: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: ['gridfilters'],
            columns : typesColumns,
            tbar: [
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
            ],
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
                        pimcore.globalmanager.get("layout_document_tree").tree.getRootNode().reload();
                    }
                    if(pimcore.globalmanager.get("layout_asset_tree").tree.rendered) {
                        pimcore.globalmanager.get("layout_asset_tree").tree.getRootNode().reload();
                    }
                    if(pimcore.globalmanager.get("layout_object_tree").tree.rendered) {
                        pimcore.globalmanager.get("layout_object_tree").tree.getRootNode().reload();
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
