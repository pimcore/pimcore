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

pimcore.registerNS("pimcore.report.events.explorer");
pimcore.report.events.explorer = Class.create(pimcore.report.abstract, {

    matchType: function (type) {        
        var types = ["global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "data_explorer";
    },

    getIconCls: function () {
        return "pimcore_icon_analytics_explorer";
    },

    getPanel: function () {

        var panel = new Ext.Panel({
            title: t("data_explorer"),
            layout: "border",
            height: 680,
            border: false,
            items: [this.getFilterPanel(),this.getContentPanel()]
        });
        
        panel.on("afterrender", function (panel) {
            this.loadMask = new Ext.LoadMask(panel.getEl(), {msg: t("please_wait")});
            this.loadMask.enable();
            
            
        }.bind(this));
        
        return panel;
    },
    
    getContentPanel: function () {

        /*this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/analytics/data-explorer',
            root: 'data',
            fields: ['dimension','metric'],
            baseParams: {
                type: type,
                id: id,
                path: path
            },
            listeners: {
                load: function () {
                    
                }.bind(this)
            }
        });
        */
        //this.store.load();
        
        var panel = new Ext.Panel({
            region: "center",
            autoScroll: true,
            items: [/*{
                height: 350,
                items: [{
                    xtype: 'columnchart',
                    store: this.store,
                    height: 350,
                    xField: 'dimension',
                    series: [
                        {
                            type: 'column',
                            yField: 'metric',
                            style: {
                                color:0x01841c
                            }
                        }
                    ]
                }]
             },{
                xtype: "grid",
                store: this.store,
                autoHeight: true,
                autoScroll: true,
                columns: [
                    {dataIndex: 'dimension',id: "dimension", header: t("dimension")},
                    {dataIndex: 'metric',header: t("metric")}
                ],
                stripeRows: true,
                autoExpandColumn: 'dimension'
            }*/]
        });
        
        return panel;  
    },
    
    getFilterPanel: function () {

        if (!this.filterPanel) {


            var today = new Date();
            var fromDate = new Date(today.getTime() - (86400000 * 31));


            this.filterPanel = new Ext.FormPanel({
                region: 'north',
                autoHeight: true,
                labelAlign: "right",
                layout: 'form',
                bodyStyle: 'padding:7px 0 0 5px',
                items: [{
                        xtype: "datefield",
                        fieldLabel: t('from'),
                        name: 'dateFrom',
                        width: 150,
                        value: fromDate,
                        itemCls: "pimcore_analytics_filter_form_item",
                        id: "pimcore_report_events_datefrom"
                    },{
                        xtype: "datefield",
                        fieldLabel: t('to'),
                        name: 'dateTo',
                        width: 150,
                        value: today,
                        itemCls: "pimcore_analytics_filter_form_item",
                        id: "pimcore_report_events_dateto"
                    },{
                        xtype:'combo',
                        fieldLabel: t('category'),
                        displayField: 'name',
                        valueField: 'name',
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            autoLoad: true,
                            url: "/admin/reports/events/get-available-categories",
                            root: "data",
                            fields: ["name"],
                            forceSelection: true,
                            listeners: {
                                "beforeload": function (store, options) {
                                    store.setBaseParam("datefrom", Ext.getCmp("pimcore_report_events_datefrom").getValue());
                                    store.setBaseParam("dateto", Ext.getCmp("pimcore_report_events_dateto").getValue());
                                }
                            }
                        }),
                        width: 150,
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'category',
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype:'combo',
                        fieldLabel: t('action'),
                        displayField: 'name',
                        valueField: 'name',
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            autoLoad: true,
                            url: "/admin/reports/events/get-available-actions",
                            root: "data",
                            fields: ["name"],
                            forceSelection: true,
                            listeners: {
                                "beforeload": function (store, options) {
                                    store.setBaseParam("datefrom", Ext.getCmp("pimcore_report_events_datefrom").getValue());
                                    store.setBaseParam("dateto", Ext.getCmp("pimcore_report_events_dateto").getValue());
                                }
                            }
                        }),
                        width: 150,
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'action',
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype:'combo',
                        fieldLabel: t('label'),
                        displayField: 'name',
                        valueField: 'name',
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            autoLoad: true,
                            url: "/admin/reports/events/get-available-labels",
                            root: "data",
                            fields: ["name"],
                            forceSelection: true,
                            listeners: {
                                "beforeload": function (store, options) {
                                    store.setBaseParam("datefrom", Ext.getCmp("pimcore_report_events_datefrom").getValue());
                                    store.setBaseParam("dateto", Ext.getCmp("pimcore_report_events_dateto").getValue());
                                }
                            }
                        }),
                        width: 150,
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'label',
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype:'combo',
                        fieldLabel: t('value'),
                        displayField: 'name',
                        valueField: 'name',
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            autoLoad: true,
                            url: "/admin/reports/events/get-available-values",
                            root: "data",
                            fields: ["name"],
                            forceSelection: true,
                            listeners: {
                                "beforeload": function (store, options) {
                                    store.setBaseParam("datefrom", Ext.getCmp("pimcore_report_events_datefrom").getValue());
                                    store.setBaseParam("dateto", Ext.getCmp("pimcore_report_events_dateto").getValue());
                                }
                            }
                        }),
                        width: 150,
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'value',
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "spinnerfield",
                        value: 10,
                        width: 150,
                        name: "limit",
                        fieldLabel: t('results'),
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "button",
                        text: t("apply"),
                        iconCls: "pimcore_icon_analytics_apply",
                        itemCls: "pimcore_analytics_filter_form_item",
                        handler: function () {
                            this.store.load({
                                params: this.filterPanel.getForm().getFieldValues()
                            })
                        }.bind(this)
                    }
                ]
            });
        }

        return this.filterPanel;
    }
});

// add to report broker
pimcore.report.broker.addGroup("events", "events", "pimcore_icon_report_analytics_group");
pimcore.report.broker.addReport(pimcore.report.events.explorer, "events");
