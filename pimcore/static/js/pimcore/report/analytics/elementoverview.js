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

pimcore.registerNS("pimcore.report.analytics.elementoverview");
pimcore.report.analytics.elementoverview = Class.create(pimcore.report.abstract, {

    matchType: function (type) {
        var types = ["document_page","document_snippet","global","object_concrete"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types) && pimcore.settings.google_analytics_enabled) {
            
            // check for object_concrete||document_snippet, they are only available in advanced mode
            if((type == "object_concrete" || type == "document_snippet") && !pimcore.settings.google_analytics_advanced) {
                return false;
            }
            return true;
        }
        return false;
    },

    getName: function () {
        return "overview";
    },

    getIconCls: function () {
        return "pimcore_icon_analytics_overview";
    },

    getPanel: function () {
    
        this.loadCounter = 0;
        this.initStores();

        var panel = new Ext.Panel({
            title: t("visitor_overview"),
            layout: "border",
            height: 680,
            border: false,
            items: [this.getFilterPanel(),this.getContentPanel()]
        });
        
        panel.on("afterrender", function (panel) {
            this.loadMask = new Ext.LoadMask(panel.getEl(), {msg: t("please_wait")});
            this.loadMask.enable();
            
            this.sourceStore.load();
            this.summaryStore.load();
            this.chartStore.load({
                params: {
                    metric: "pageviews"
                }
            });
            
        }.bind(this));
        
        return panel;
    },
    
    getContentPanel: function () {
  
        var summary = new Ext.grid.GridPanel({
            store: this.summaryStore,
            flex: 1,
            height: 250,
            autoScroll: true,
            viewConfig: {
                headersDisabled: true
            },
            columns: [
                {dataIndex: 'chart', sortable: false, renderer: function (d) {
                    return '<img src="' + d + '" />';
                }},
                {dataIndex: 'value', sortable: false, renderer: function (d) {
                    return '<span class="pimcore_analytics_gridvalue">' + d + '</span>';
                }},
                {dataIndex: 'label', id: "label", sortable: false, renderer: function (d) {
                    return '<span class="pimcore_analytics_gridlabel">' + t(d) + '</span>';
                }}
            ],
            stripeRows: true,
            autoExpandColumn: 'label'
        });

        summary.on("rowclick", function (grid, rowIndex, event) {
            var data = grid.getStore().getAt(rowIndex);     
            
            var values = this.filterPanel.getForm().getFieldValues();
            values.metric = data.data.metric;
                   
            this.chartStore.load({
                params: values
            });
        }.bind(this));       
        
        
        var panel = new Ext.Panel({
            region: "center",
            autoScroll: true,
            items: [{
                height: 350,
                items: [{
                    xtype: 'linechart',
                    store: this.chartStore,
                    height: 350,
                    xField: 'datetext',
                    series: [
                        {
                            type: 'line',
                            yField: 'data',
                            style: {
                                color:0x01841c
                            }
                        }
                    ]
                }]
             },{
                autoScroll: true,
                items: [{
                    layout:'hbox',
                    layoutConfig: {
                        padding: 10,
                        align: "stretch"
                    },
                    border: true,
                    height: 300,
                    items: [summary,{
                        xtype: 'piechart',
                        store: this.sourceStore,
                        flex: 1,
                        autoScroll: true,
                        dataField: 'pageviews',
                        categoryField: 'source',
                        extraStyle: {
                            legend: {
                                display: 'bottom',
                                padding: 5
                            }
                        }         
                    }]
                 }]
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
                labelWidth: 40,
                height: 40,
                layout: 'form',
                bodyStyle: 'padding:7px 0 0 5px',
                items: [{
                        xtype: "datefield",
                        fieldLabel: t('from'),
                        name: 'dateFrom',
                        value: fromDate,
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "datefield",
                        fieldLabel: t('to'),
                        name: 'dateTo',
                        value: today,
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
                            
                            var values = this.filterPanel.getForm().getFieldValues();
                            
                            this.sourceStore.load({
                                params: values
                            });
                            this.summaryStore.load({
                                params: values
                            });
                            
                            values.metric = "pageviews"
                            this.chartStore.load({
                                params: values
                            });
                        }.bind(this)
                    }
                ]
            });
        }

        return this.filterPanel;
    },  
    
    initStores: function () {
        
        var path = "";
        var id = "";
        var type = "";
        if (this.type == "document_page" || this.type == "document_snippet") {
            id = this.reference.id;
            path = this.reference.data.path + this.reference.data.key;
            type = "document";
        }
        if (this.type == "object_concrete") {
            id = this.reference.id;
            path = this.reference.data.o_path + this.reference.data.o_key;
            type = "object";
        }
        
        this.chartStore = new Ext.data.JsonStore({
            url: "/admin/reports/analytics/chartmetricdata",
            baseParams: {
                type: type,
                id: id,
                path: path,
                dataField: "data"
            },
            root: 'data',
            fields: ["timestamp","datetext","data"],
            listeners: {
                load: this.storeFinished.bind(this),
                beforeload: this.storeStart.bind(this)
            }
        });
        
        this.summaryStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/analytics/summary',
            root: 'data',
            fields: ['chart','value',"label","metric"],
            baseParams: {
                type: type,
                id: id,
                path: path
            },
            listeners: {
                load: this.storeFinished.bind(this),
                beforeload: this.storeStart.bind(this)
            }
        });

        this.sourceStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/analytics/source',
            root: 'data',
            fields: ['source','pageviews'],
            baseParams: {
                type: type,
                id: id,
                path: path
            },
            listeners: {
                load: this.storeFinished.bind(this),
                beforeload: this.storeStart.bind(this)
            }
        });
    },
    
    storeFinished: function () {
        this.loadCounter--;
        if(this.loadCounter < 1) {
            this.loadMask.hide();
        }
    },
    
    storeStart: function () {
        if(this.loadCounter < 1) {
            this.loadMask.show();
        }
        this.loadCounter++;
    }
});

// add to report broker
pimcore.report.broker.addGroup("analytics", "google_analytics", "pimcore_icon_report_analytics_group");
pimcore.report.broker.addReport(pimcore.report.analytics.elementoverview, "analytics");
