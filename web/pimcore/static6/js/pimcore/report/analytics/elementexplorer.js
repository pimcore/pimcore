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
            border: false,
            layout: "border",
            items: [this.getFilterPanel(),this.getContentPanel()]
        });
        
        panel.on("afterrender", function (panel) {
            this.loadMask = new Ext.LoadMask(
                {
                    target: panel,
                    msg: t("please_wait")
                });
            //this.loadMask.show();
            
            
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
            proxy: {
                type: 'ajax',
                url: '/admin/reports/analytics/data-explorer',
                extraParams: {
                    type: type,
                    id: id,
                    path: path
                },
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ['dimension','metric']
        });
        this.store.load();
        
        var panel = new Ext.Panel({
            scrollable: "y",
            region: "center",
            items: [{
                xtype: 'cartesian',
                store: this.store,
                height: 350,
                axes: [{
                    type: 'numeric',
                    position: 'left',
                    fields: 'metric'
                }, {
                    type: 'category',
                    position: 'bottom',
                    fields: 'dimension'
                }],
                interactions: 'itemhighlight',
                series: [
                    {
                        type: 'bar',
                        xField: 'dimension',
                        yField: 'metric',
                        style: {
                            minGapWidth: 20,
                            fillStyle: "#018410"
                        }
                    }
                ]
            },{
                xtype: "grid",
                store: this.store,
                columns: [
                    {dataIndex: 'dimension',id: "dimension", header: t("dimension"), flex: 1},
                    {dataIndex: 'metric',header: t("metric")}
                ],
                stripeRows: true
            }]
        });
        
        return panel;  
    },
    
    getFilterPanel: function () {

        if (!this.filterPanel) {


            var today = new Date();
            var fromDate = new Date(today.getTime() - (86400000 * 31));


            this.filterPanel = new Ext.FormPanel({
                autoHeight: true,
                labelAlign: "right",
                region: "north",
                bodyStyle: 'padding:7px 0 0 5px',
                items: [
                    {
                        layout: 'hbox',
                        items: [{
                            xtype: "datefield",
                            fieldLabel: t('from'),
                            name: 'dateFrom',
                            value: fromDate,
                            cls: "pimcore_analytics_filter_form_item"
                        },{
                            xtype: "datefield",
                            fieldLabel: t('to'),
                            name: 'dateTo',
                            value: today,
                            cls: "pimcore_analytics_filter_form_item"
                        },{
                            xtype:'combo',
                            fieldLabel: t('dimension'),
                            displayField: 'name',
                            valueField: 'id',
                            store: new Ext.data.JsonStore({
                                autoDestroy: true,
                                autoLoad: true,
                                proxy: {
                                    type: 'ajax',
                                    url: "/admin/reports/analytics/get-dimensions",
                                    reader: {
                                        type: 'json',
                                        rootProperty: "data",
                                        idProperty: "id"
                                    }
                                },
                                fields: ["name", "id"],
                                forceSelection: true
                            }),
                            forceSelection: true,
                            triggerAction: 'all',
                            name: 'dimension',
                            value: "ga:date",
                            cls: "pimcore_analytics_filter_form_item"
                        },{
                            xtype:'combo',
                            fieldLabel: t('metric'),
                            displayField: 'name',
                            valueField: 'id',
                            store: new Ext.data.JsonStore({
                                autoDestroy: true,
                                autoLoad: true,
                                proxy: {
                                    type: 'ajax',
                                    url: "/admin/reports/analytics/get-metrics",
                                    reader: {
                                        type: 'json',
                                        rootProperty: "data",
                                        idProperty: "id"
                                    }
                                },
                                fields: ["name", "id"],
                                lazyInit: false,
                                forceSelection: true
                            }),
                            forceSelection: true,
                            triggerAction: 'all',
                            name: 'metric',
                            value: "ga:pageviews",
                            cls: "pimcore_analytics_filter_form_item"
                        }]
                    }, {
                        layout: 'hbox',
                        items: [{
                            xtype: "numberfield",
                            value: 10,
                            name: "limit",
                            fieldLabel: t('results'),
                            cls: "pimcore_analytics_filter_form_item"
                        },{
                            fieldLabel: t("sort"),
                            xtype: "combo",
                            name: "sort",
                            value: "desc",
                            store: [
                                ["desc", t("descending")],
                                ["asc",t("ascending")]
                            ],
                            mode: "local",
                            triggerAction: "all",
                            cls: "pimcore_analytics_filter_form_item"
                        },{
                            xtype: "combo",
                            store: pimcore.globalmanager.get("sites"),
                            valueField: "id",
                            displayField: "domain",
                            triggerAction: "all",
                            name: "site",
                            fieldLabel: t("site"),
                            cls: "pimcore_analytics_filter_form_item"
                        },{
                            xtype: "button",
                            text: t("apply"),
                            iconCls: "pimcore_icon_save",
                            cls: "pimcore_analytics_filter_form_item",
                            handler: function () {
                                this.store.load({
                                    params: this.filterPanel.getForm().getFieldValues()
                                });
                            }.bind(this)
                        }]
                    }
                ]
            });
        }

        return this.filterPanel;
    }
});

// add to report broker
pimcore.report.broker.addGroup("analytics", "google_analytics", "pimcore_icon_analytics");
pimcore.report.broker.addReport(pimcore.report.analytics.elementexplorer, "analytics");
