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

pimcore.registerNS("pimcore.layout.portlets.feed");
pimcore.layout.portlets.feed = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.feed";
    },


    getName: function () {
        return t("feed");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_feed";
    },

    getLayout: function () {


        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/portal/portlet-feed',
            root: 'entries',
            fields: ['id','title',"description",'date',"link","content"]
        });

        this.store.load();

        var grid = new Ext.grid.GridPanel({
            store: this.store,
            columns: [
                {header: t('title'), id: "title", sortable: false, dataIndex: 'title'}
            ],
            stripeRows: true,
            autoExpandColumn: 'title'
        });

        grid.on("rowclick", this.openDetail.bind(this));

        var defaultConf = this.getDefaultConfig();

        defaultConf.tools = [
            {
                id:'gear',
                handler: this.editSettings.bind(this)
            },
            {
                id:'close',
                handler: this.remove.bind(this)
            }
        ];

        this.layout = new Ext.ux.Portlet(Object.extend(defaultConf, {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: [grid]
        }));

        return this.layout;
    },

    editSettings: function () {
        var win = new Ext.Window({
            width: 600,
            height: 100,
            modal: true,
            closeAction: "close",
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px",
                    items: [
                        {
                            xtype: "textfield",
                            name: "url",
                            id: "pimcore_portlet_feed_url",
                            fieldLabel: "Feed-URL",
                            width: 420
                        },
                        {
                            xtype: "button",
                            text: t("save"),
                            handler: function () {
                                Ext.Ajax.request({
                                    url: "/admin/portal/portlet-feed-save",
                                    params: {
                                        url: Ext.getCmp("pimcore_portlet_feed_url").getValue()
                                    },
                                    success: function () {
                                        this.store.reload();
                                    }.bind(this)
                                });
                                win.close();
                            }.bind(this)
                        }
                    ]
                }
            ]
        });

        win.show();
    },

    openDetail: function (grid, rowIndex, event) {
        var store = grid.getStore();
        var item = store.getAt(rowIndex);

        var content = '<h1>' + item.data.title + '</h1><br /><br />' + item.data.content + '<br /><br />';

        var win = new Ext.Window({
            width: 650,
            height: 500,
            modal: true,
            bodyStyle: "background: #fff; padding: 20px;",
            autoScroll: true,
            html: content,
            closeAction: "close",
            buttons: [
                {
                    text: t("view_original"),
                    handler: function () {
                        window.open(item.data.link);
                    }
                }
            ]
        });

        win.show();
    }
});
