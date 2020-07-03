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

        var store = new Ext.data.Store({
            autoDestroy: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_reports_analytics_chartmetricdata', {metric: ['visits', 'pageviews'], site: site}),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ['timestamp','datetext',"pageviews",'visits']
        });

        store.load();

        var tbar = false;

        if (pimcore.globalmanager.get("sites").getTotalCount() > 0) {

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
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: Routing.generate('pimcore_admin_portal_portletanalyticssites'),
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        extraParams: {
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
                                url: Routing.generate('pimcore_admin_portal_updateportletconfig'),
                                method: 'PUT',
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
                xtype: 'cartesian',
                store: store,
                xField: 'datetext',
                interactions: ['itemhighlight'],
                axes: [{
                    type: 'numeric',
                    fields: ['pageviews', 'visits' ],
                    position: 'left',
                    grid: true,
                    minimum: 0
                }
                    , {
                        type: 'category',
                        fields: 'datetext',
                        position: 'bottom',
                        grid: true,
                        label: {
                            rotate: {
                                degrees: -45
                            }
                        }
                    }
                ],
                series: [
                    {
                        type: 'line',
                        title: t('pageviews'),
                        xField: 'datetext',
                        yField: 'pageviews',
                        style: {
                            color:0x01841c
                        },
                        marker: {
                            radius: 4
                        },
                        highlight: {
                            fillStyle: '#000',
                            radius: 5,
                            lineWidth: 2,
                            strokeStyle: '#fff'
                        },
                        tooltip: {
                            trackMouse: true,
                            style: 'background: #01841c',
                            renderer: function(tooltip, storeItem, item) {
                                var title = item.series.getTitle();
                                tooltip.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }
                    },
                    {
                        type:'line',
                        title: t("visits"),
                        xField: 'datetext',
                        yField: 'visits',
                        style: {
                            color: 0x15428B
                        },
                        marker: {
                            radius: 4
                        },
                        highlight: {
                            fillStyle: '#000',
                            radius: 5,
                            lineWidth: 2,
                            strokeStyle: '#fff'
                        },
                        tooltip: {
                            trackMouse: true,
                            style: 'background: #0184ff',
                            renderer: function(tooltip, storeItem, item) {
                                var title = item.series.getTitle();
                                tooltip.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }
                    }
                ]
            }
        });

        this.layout = Ext.create('Portal.view.Portlet', Object.assign(this.getDefaultConfig(), {
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
