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

pimcore.registerNS("pimcore.settings.httpErrorLog");
pimcore.settings.httpErrorLog = Class.create({

    initialize: function(id) {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_http_error_log");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_http_error_log",
                title: t("http_errors"),
                iconCls: "pimcore_icon_httperrorlog",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_http_error_log");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("http_error_log");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getGrid: function () {

        this.store = new Ext.data.JsonStore({
            url: '/admin/misc/http-error-log',
            restful: false,
            root: "items",
            remoteSort: true,
            fields: ["id","path", "code", "date","amount"],
            baseParams: {
                limit: 20,
                filter: "",
                group: 1
            }
        });
        this.store.load();

        var typesColumns = [
            {header: "ID", width: 50, sortable: true, hidden: true, dataIndex: 'id'},
            {header: "Code", width: 60, sortable: true, dataIndex: 'code'},
            {header: t("path"), id: "path", width: 400, sortable: true, dataIndex: 'path'},
            {header: t("amount"), width: 60, sortable: true, dataIndex: 'amount'},
            {header: t("date"), id: "extension_description", width: 200, sortable: true, dataIndex: 'date',
                                                                    renderer: function(d) {
                var date = new Date(d * 1000);
                return date.format("Y-m-d H:i:s");
            }},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('open'),
                    icon: "/pimcore/static/img/icon/world_go.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        window.open(data.get("path"));
                    }.bind(this)
                }]
            }
        ];


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var val = input.getValue();
                        this.store.baseParams.filter = val ? val : "";
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 20,
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

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            autoExpandColumn: "path",
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            columnLines: true,
            stripeRows: true,
            listeners: {
                "rowdblclick": function (grid, rowIndex, ev) {
                    var data = grid.getStore().getAt(rowIndex);
                    var win = new Ext.Window({
                        closable: true,
                        width: 810,
                        autoDestroy: true,
                        height: 430,
                        modal: true,
                        bodyStyle: "background:#fff;",
                        html: '<iframe src="/admin/misc/http-error-log-detail?id=' + data.get("id")
                                            + '" frameborder="0" width="100%" height="390"></iframe>'
                    });
                    win.show();
                }
            },
            viewConfig: {
                forceFit: true
            },
            tbar: [{
                text: t("refresh"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            }, "-",{
                text: t("group_by_path"),
                pressed: true,
                iconCls: "pimcore_icon_groupby",
                enableToggle: true,
                handler: function (button) {
                    this.store.baseParams.group = button.pressed ? 1 : 0;
                    this.store.load();
                }.bind(this)
            }, "-",{
                text: t('flush'),
                handler: function () {
                    Ext.Ajax.request({
                        url: "/admin/misc/http-error-log-flush",
                        success: function () {
                            this.store.reload();
                            this.grid.getView().refresh();
                        }.bind(this)
                    });
                }.bind(this),
                iconCls: "pimcore_icon_flush_recyclebin"
            }, "-", {
                text: t("errors_from_the_last_14_days"),
                xtype: "tbtext"
            }, '-',"->",{
              text: t("filter") + "/" + t("search"),
              xtype: "tbtext",
              style: "margin: 0 10px 0 0;"
            },
            this.filterField]
        });

        return this.grid;
    },

    reload: function () {
        this.store.reload();
    }
});