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

pimcore.registerNS("pimcore.settings.bouncemailinbox");
pimcore.settings.bouncemailinbox = Class.create({

    initialize: function(id) {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_bouncemailinbox");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_bouncemailinbox",
                title: t("bounce_mail_inbox"),
                iconCls: "pimcore_icon_bouncemail",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_bouncemailinbox");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("bouncemailinbox");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getGrid: function () {

        var itemsPerPage = 20;
        this.store = pimcore.helpers.grid.buildDefaultStore(
            '/admin/email/bounce-mail-inbox-list?',
            ["id","subject", "to", "from","date"],
            itemsPerPage
        );

        var typesColumns = [
            {header: "ID", flex: 50, sortable: false, hidden: true, dataIndex: 'id'},
            {header: t("subject"), flex: 400, sortable: false, dataIndex: 'subject'},
            {header: t("to"), flex: 100, sortable: false, dataIndex: 'to'},
            {header: t("from"), flex: 100, sortable: false, dataIndex: 'from'},
            {header: t("date"), flex: 100, sortable: false, dataIndex: 'date'},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('open'),
                    icon: "/pimcore/static6/img/icon/arrow_right.png",
                    handler: function (grid, rowIndex) {
                        this.showMessage(grid.getStore().getAt(rowIndex).get("id"));
                    }.bind(this)
                }]
            }
        ];

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [{
                text: t("refresh"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            }]
        });


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            columnLines: true,
            stripeRows: true,
            listeners: {
                "rowdblclick": function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    this.showMessage(data.get("id"));
                }.bind(this)
            },
            viewConfig: {
                forceFit: true
            },
            tbar: toolbar
        });

        return this.grid;
    },

    showMessage: function (id) {
        var win = new Ext.Window({
            closable: true,
            width: 810,
            autoDestroy: true,
            height: 430,
            modal: true,
            bodyStyle: "background:#fff;",
            html: '<iframe src="/admin/email/bounce-mail-inbox-detail?id=' + id
                                + '" frameborder="0" width="100%" height="390"></iframe>'
        });
        win.show();
    },

    reload: function () {
        this.store.reload();
    }
});
