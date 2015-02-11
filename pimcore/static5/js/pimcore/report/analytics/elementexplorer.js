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

pimcore.registerNS("pimcore.report.analytics.elementexplorer");
pimcore.report.analytics.elementexplorer = Class.create(pimcore.report.abstract, {

    matchType: function (type) {        
        var types = ["document_page","global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)
                                                    && pimcore.settings.google_analytics_enabled) {
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
        
        var path = "";
        var id = "";
        var type = "";
        if (this.type == "document_page") {
            id = this.reference.id;
            path = this.reference.data.path + this.reference.data.key;
            type = "document";
        }
        
        this.store = new Ext.data.JsonStore({
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
        this.store.load();
        
        var panel = new Ext.Panel({
            region: "center",
            autoScroll: true,
            items: [{
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
            }]
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
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "datefield",
                        fieldLabel: t('to'),
                        name: 'dateTo',
                        width: 150,
                        value: today,
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype:'combo',
                        fieldLabel: t('dimension'),
                        displayField: 'name',
                        valueField: 'id',
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            autoLoad: true,
                            url: "/admin/reports/analytics/get-dimensions",
                            root: "data",
                            idProperty: "id",
                            fields: ["name", "id"],
                            forceSelection: true
                        }),
                        width: 150,
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'dimension',
                        value: "ga:date",
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype:'combo',
                        fieldLabel: t('metric'),
                        displayField: 'name',
                        valueField: 'id',
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            autoLoad: true,
                            url: "/admin/reports/analytics/get-metrics",
                            root: "data",
                            idProperty: "id",
                            fields: ["name", "id"],
                            lazyInit: false,
                            forceSelection: true
                        }),
                        width: 150,
                        forceSelection: true,
                        triggerAction: 'all',
                        name: 'metric',
                        value: "ga:pageviews",
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "spinnerfield",
                        value: 10,
                        width: 150,
                        name: "limit",
                        fieldLabel: t('results'),
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        fieldLabel: t("sort"),
                        xtype: "combo",
                        width: 150,
                        name: "sort",
                        value: "desc",
                        store: [
                            ["desc", t("descending")],
                            ["asc",t("ascending")]
                        ],
                        mode: "local",
                        triggerAction: "all",
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "combo",
                        store: pimcore.globalmanager.get("sites"),
                        valueField: "id",
                        displayField: "domain",
                        triggerAction: "all",
                        name: "site",
                        fieldLabel: t("site"),
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "button",
                        text: t("apply"),
                        iconCls: "pimcore_icon_analytics_apply",
                        itemCls: "pimcore_analytics_filter_form_item",
                        handler: function () {
                            this.store.load({
                                params: this.filterPanel.getForm().getFieldValues()
                            });
                        }.bind(this)
                    }
                ]
            });
        }

        return this.filterPanel;
    }
});

// add to report broker
pimcore.report.broker.addGroup("analytics", "google_analytics", "pimcore_icon_report_analytics_group");
pimcore.report.broker.addReport(pimcore.report.analytics.elementexplorer, "analytics");
