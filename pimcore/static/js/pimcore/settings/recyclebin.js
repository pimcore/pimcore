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

pimcore.registerNS("pimcore.settings.recyclebin");
pimcore.settings.recyclebin = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_recyclebin");
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
            tabPanel.activate("pimcore_recyclebin");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("recyclebin");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getGrid: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/recyclebin/list'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data',
            idProperty: "id"
        }, [
            {name: 'id'},
            {name: 'type'},
            {name: 'subtype'},
            {name: 'path'},
            {name: 'amount'},
            {name: 'deletedby'},
            {name: 'date'}
        ]);
        var writer = new Ext.data.JsonWriter();

        var itemsPerPage = 20;

        this.store = new Ext.data.Store({
            id: 'recyclebin_store',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            remoteSort: true,
            baseParams: {
                limit: itemsPerPage,
                filter: ""
            },  
            listeners: {
                write : function(store, action, result, response, rs) {
                },
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
                        this.store.baseParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
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
        this.pagingtoolbar.add(new Ext.form.ComboBox({
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
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        var typesColumns = [
            {header: t("type"), width: 50, sortable: true, dataIndex: 'subtype', renderer: function(d) {
                return '<img src="/pimcore/static/img/icon/' + d + '.png" />';
            }},
            {header: t("path"), id: "recyclebin_path_col", width: 200, sortable: true, dataIndex: 'path'},
            {header: t("amount"), width: 60, sortable: true, dataIndex: 'amount'},
            {header: t("deletedby"), width:80,sortable: true, dataIndex: 'deletedby'},
            {header: t("date"), width: 140, sortable: true, dataIndex: 'date', renderer: function(d) {
                var date = new Date(d * 1000);
                return date.format("Y-m-d H:i:s");
            }},
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
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            columns : typesColumns,
            autoExpandColumn: "recyclebin_path_col",
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
        var rec = this.grid.getSelectionModel().getSelected();
        if (!rec) {
            return false;
        }
        this.grid.store.remove(rec);

        Ext.getCmp("pimcore_recyclebin_button_restore").disable();
        Ext.getCmp("pimcore_recyclebin_button_delete").disable();
    },
    
    onRestore: function () {
        
        pimcore.helpers.loadingShow();
        
        var rec = this.grid.getSelectionModel().getSelected();
        if (!rec) {
            return false;
        }

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
