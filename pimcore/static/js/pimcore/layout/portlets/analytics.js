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

pimcore.registerNS("pimcore.layout.portlets.analytics");
pimcore.layout.portlets.analytics = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.analytics";
    },

    getName: function () {
        return t("google_analytics");
    },

    getIcon: function () {
        return "pimcore_icon_analytics";
    },

    getLayout: function (portletId) {
        var site = 0;
        try {
            site = this.getConfig();
        }
        catch(e) {

        }

        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/analytics/chartmetricdata?metric[]=visits&metric[]=pageviews&site=' + site,
            root: 'data',
            fields: ['timestamp','datetext',"pageviews",'visits']
        });

        store.load();

        var tbar = false;

        if (pimcore.globalmanager.get("sites").totalLength > 0) {

            tbar = [
                "->",
                {
                    xtype:"tbtext",
                    text:t('select_site')
                },
                {
                    xtype:"combo",
                    autoSelect: true,
                    valueField: "id",
                    displayField: "site",
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        url: '/admin/portal/portlet-analytics-sites',
                        root: 'data',
                        baseParams: {
                            key: this.portal.key,
                            id: portletId
                        },
                        fields: ['id','site']
                    }),
                    triggerAction: "all",
                    listeners:{
                        select: function (el) {
                            store.load({
                                params: {
                                    site : el.getValue()
                                }
                            });
                            Ext.Ajax.request({
                                url: "/admin/portal/update-portlet-config",
                                params: {
                                    key: this.portal.key,
                                    id: portletId,
                                    config:  el.getValue()
                                }
                            });
                        }.bind(this)
                   }
                }
            ];
        }

        var panel = new Ext.Panel({
            layout:'fit',
            height: 275,
            tbar: tbar,
            items: {
                xtype: 'linechart',
                store: store,
                xField: 'datetext',
                series: [
                    {
                        type: 'line',
                        displayName: t('pageviews'),
                        yField: 'pageviews',
                        style: {
                            color:0x01841c
                        }
                    },
                    {
                        type:'line',
                        displayName: t("visits"),
                        yField: 'visits',
                        style: {
                            color: 0x15428B
                        }
                    }
                ]
            }
        });

        this.layout = new Ext.ux.Portlet(Object.extend(this.getDefaultConfig(), {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: [panel]
        }));

        this.layout.portletId = portletId;
        return this.layout;
    }
});
