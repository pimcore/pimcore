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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.plugin.deployment.packages");
pimcore.plugin.deployment.packages = Class.create({

    tabKey : 'pimcore_plugin_deployment_packages',

    initialize: function() {
        this.getLayout();
        this.logs = [];

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.layout);
        tabPanel.activate(this.tabKey);

        this.layout.on("destroy", function () {
            pimcore.globalmanager.remove(this.tabKey);
        }.bind(this));
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate(this.tabKey);
    },

    getLayout: function () {

        if (this.layout == null) {
            var tbar = [];

            this.layout = new Ext.Panel({
                title: t('deployment_packages'),
                id: this.tabKey,
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_menu_extension",
                items: [],
                closable:true
            });
            this.layout.on("afterrender", this.getPanel.bind(this));
        }

        return this.layout;
    },

    getPanel: function () {
        var itemsPerPage = 20;
        this.store = new Ext.data.JsonStore({
            id: 'pimcore_deployment_package_store',
            url: '/plugin/deployment/packages/list',
            restful: false,
            root: "data",
            fields: ["id","type","subtType","version","creationDate","delete","download"]
        });
        this.store.load();

        var columns = [
            {
                id: 'id',
                width: 10,
                header: t('id'),
                dataIndex: 'id'
            },
            {
                id: 'creationDate',
                header: t('creationdate'),
                dataIndex: 'creationDate',
                renderer: function(d) {
                    var date = new Date(d * 1000);
                    return date.format("Y-m-d H:i:s");
                },
                width: 25
            },
            {
                id: 'version',
                header: t('deployment_package_version'),
                dataIndex: 'version',
                width: 10
            },
            {
                id: 'type',
                header: t('deployment_package_type'),
                dataIndex: 'type',
                width: 30
            },
            {
                id: 'subType',
                header: t('deployment_package_sub_type'),
                dataIndex: 'subType',
                width: 30
            },
            {
                header: t('download'),
                xtype: 'actioncolumn',
                width: 10,
                items: [{
                    icon: "/pimcore/static/img/icon/disk_download.png",
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                        pimcore.helpers.download("/plugin/deployment/packages/download?id=" + rec.get("id"))
                    }.bind(this)
                }]
            }
        ];

        var remove = {
            header: t('delete'),
            xtype: 'actioncolumn',
            width: 70,
            items: [{
                icon: "/pimcore/static/img/icon/cross.png",
                handler: function (grid, rowIndex) {
                    var rec = grid.getStore().getAt(rowIndex);

                    var modal = new Ext.Window({
                        layout:'fit',
                        width:500,
                        height:200,
                        closeAction:'close',
                        modal: true,
                        items: [{
                            xtype: "panel",
                            border: false,
                            bodyStyle: "padding:20px;font-size:14px;",
                            html: t("deployment_package_delete_warning")
                        }],
                        buttons: [{
                            text: t("delete_confirm"),
                            iconCls: "pimcore_icon_apply",
                            handler: function(){
                                Ext.Ajax.request({
                                    url: "/plugin/deployment/packages/delete",
                                    params: {
                                        id: rec.get("id")
                                    },
                                    success: function (transport) {
                                        var res = Ext.decode(transport.responseText);
                                        if(res.success){
                                            grid.getStore().reload();
                                            modal.close();
                                        }else{
                                            Ext.Msg.alert(t('error'), res.error);
                                        }
                                    }.bind(this)
                                });
                            }
                        }]
                    });
                    modal.show();
                }.bind(this)
            }]
        };
        columns.push(remove);

        this.grid = new Ext.grid.GridPanel({
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: columns
            }),
            viewConfig: {
                forceFit: true
            },
            frame: false,
            iconCls: 'icon-grid'
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
                [100, "100"],
                [999999, t("all")]
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

        this.editor = new Ext.Panel({
            layout: "border",
            items: [new Ext.Panel({
                autoScroll: true,
                items: [this.grid],
                region: "center",
                layout: "fit",
                bbar: this.pagingtoolbar
            })]
        });

        this.layout.removeAll();
        // this.layout.add(this.grid);
        this.layout.add(this.editor);
        this.layout.doLayout();

    },

    reload: function(){
        this.store.reload();
    }
});
