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

pimcore.registerNS("pimcore.layout.portlets.modificationStatistic");
pimcore.layout.portlets.modificationStatistic = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.modificationStatistic";
    },

    getName: function () {
        return t("modification_statistic");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_modification_statistic";
    },

    getLayout: function (portletId) {

        var store = new Ext.data.Store({
            autoDestroy: true,
            proxy: {
                type: 'ajax',
                url: '/admin/portal/portlet-modification-statistics',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
            }},
            fields: ['timestamp','datetext',"objects",'documents',"assets"]
        });

        store.load();


        var panel = new Ext.Panel({
            layout:'fit',
            height: 275,
            items: {
                xtype: 'cartesian',
                store: store,
                legend: {
                    docked: 'right'
                },
                interactions: 'itemhighlight',
                axes: [{
                    type: 'numeric',
                    fields: ['documents', 'assets', 'objects' ],
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
                        axis:' left',
                        title: t('documents'),
                        xField: 'datetext',
                        yField: 'documents',
                        style: {
                            lineWidth: 2,
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
                            renderer: function(storeItem, item) {
                                var title = item.series.getTitle();
                                this.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }
                    },
                    {
                        type:'line',
                        axis:' left',
                        title: t('assets'),
                        xField: 'datetext',
                        yField: 'assets',
                        style: {
                            lineWidth: 2,
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
                            style: 'background: #00bfff',
                            renderer: function(storeItem, item) {
                                var title = item.series.getTitle();
                                this.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }
                    },
                    {
                        type:'line',
                        axis:' left',
                        title: t('objects'),
                        xField: 'datetext',
                        yField: 'objects',
                        style: {
                            lineWidth: 2,
                            color: 0xff6600
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
                            style: 'background: #ff6600',
                            renderer: function(storeItem, item) {
                                var title = item.series.getTitle();
                                this.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }

                    },

                ]
            }
        });


        this.layout = Ext.create('Portal.view.Portlet', Object.extend(this.getDefaultConfig(), {
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
