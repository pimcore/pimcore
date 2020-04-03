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

pimcore.registerNS("pimcore.settings.httpErrorLog");
pimcore.settings.httpErrorLog = Class.create({

    initialize: function(id) {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_http_error_log");
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
            tabPanel.setActiveItem("pimcore_http_error_log");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("http_error_log");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getGrid: function () {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url = Routing.generate('pimcore_admin_misc_httperrorlog');

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            ["uri", "code", "date","count"],
            itemsPerPage
        );

        var proxy = this.store.getProxy();
        proxy.extraParams["group"] = 1;
        proxy.getReader().setRootProperty('items');

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var typesColumns = [
            {text: "Code", width: 60, sortable: true, dataIndex: 'code'},
            {text: t("path"), width: 400, sortable: true, dataIndex: 'uri'},
            {text: t("amount"), width: 60, sortable: true, dataIndex: 'count'},
            {text: t("date"), width: 200, sortable: true, dataIndex: 'date',
                                                                    renderer: function(d) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            }},
            {
                xtype: 'actioncolumn',
                menuText: t('open'),
                width: 30,
                items: [{
                    tooltip: t('open'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        window.open(data.get("uri"));
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
                        var input = filterField;
                        var val = input.getValue();
                        this.store.getProxy().extraParams.filter = val ? val : "";
                        this.store.load();
                    }
                }.bind(this)
            }
        });


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
                "rowdblclick": function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    var path = Routing.generate('pimcore_admin_misc_httperrorlogdetail', {
                        uri: data.get("uri"),
                    });
                    var win = new Ext.Window({
                        closable: true,
                        width: 810,
                        autoDestroy: true,
                        height: 430,
                        modal: true,
                        html: '<iframe src="' + path + '" frameborder="0" width="100%" height="390"></iframe>'
                    });
                    win.show();
                }
            },
            viewConfig: {
                forceFit: true
            },
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [{
                    text: t("refresh"),
                    iconCls: "pimcore_icon_reload",
                    handler: this.reload.bind(this)
                }, "-",{
                    text: t("group_by_path"),
                    pressed: true,
                    iconCls: "pimcore_icon_groupby",
                    enableToggle: true,
                    handler: function (button) {
                        this.store.getProxy().extraParams.group = button.pressed ? 1 : 0;
                        this.store.load();
                    }.bind(this)
                }, "-",{
                    text: t('flush'),
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_misc_httperrorlogflush'),
                            method: "DELETE",
                            success: function () {
                                var proxy = this.store.getProxy();
                                proxy.extraParams.filter = this.filterField.getValue();
                                this.store.load();
                            }.bind(this)
                        });
                    }.bind(this),
                    iconCls: "pimcore_icon_flush_recyclebin"
                }, "-", {
                    text: t("errors_from_the_last_7_days"),
                    xtype: "tbtext"
                }, '-',"->",{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField]
            }
        });

        return this.grid;
    },

    reload: function () {
        this.store.reload();
    }
});
