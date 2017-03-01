/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.layout.portlets.customreports");
pimcore.layout.portlets.customreports = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.customreports";
    },


    getName: function () {
        return t("portlet_customreport");
    },

    getIcon: function () {
        return "pimcore_icon_reports";
    },

    getLayout: function (portletId) {

        var defaultConf = this.getDefaultConfig();

        defaultConf.tools = [
            {
                type:'search',
                handler: this.openReport.bind(this)
            },
            {
                type:'gear',
                handler: this.editSettings.bind(this)
            },
            {
                type:'close',
                handler: this.remove.bind(this)
            }
        ];

        this.layout = Ext.create('Portal.view.Portlet', Object.extend(defaultConf, {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: []
        }));

        this.updateChart();

        this.layout.portletId = portletId;
        return this.layout;
    },

    editSettings: function () {
        this.configSelectionCombo = new Ext.form.ComboBox({
            xtype:"combo",
            width: 500,
            id: "pimcore_portlet_selected_custom_report",
            autoSelect: true,
            valueField: "id",
            displayField: "text",
            value: this.config,
            fieldLabel: t("portlet_customreport"),
            store: new Ext.data.Store({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/reports/custom-report/tree',
                    extraParams: {
                        portlet: 1
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                listeners: {
                    load: function() {
                        this.configSelectionCombo.setValue(this.config);
                    }.bind(this)
                },
                fields: ['id','text']
            }),
            triggerAction: "all"
        });

        var win = new Ext.Window({
            width: 600,
            height: 150,
            modal: true,
            title: t('portlet_customreport_settings'),
            closeAction: "destroy",
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px",
                    items: [
                        this.configSelectionCombo,
                        {
                            xtype: "button",
                            text: t("save"),
                            handler: function () {
                                this.updateSettings();
                                win.close();
                            }.bind(this)
                        }
                    ]
                }
            ]
        });

        win.show();
    },

    updateSettings: function() {
        this.config = Ext.getCmp("pimcore_portlet_selected_custom_report").getValue();
        Ext.Ajax.request({
            url: "/admin/portal/update-portlet-config",
            params: {
                key: this.portal.key,
                id: this.layout.portletId,
                config: Ext.getCmp("pimcore_portlet_selected_custom_report").getValue()
            },
            success: function () {
                this.updateChart();
            }.bind(this)
        });
    },

    updateChart: function() {
        if(this.config) {
            Ext.Ajax.request({
                url: "/admin/reports/custom-report/get",
                params: {
                    name: this.config
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    var chart = this.getChart(data);
                    this.layout.removeAll();
                    if(chart) {
                        this.layout.add(chart);
                    }

                    this.layout.setTitle(t("portlet_customreport") + ": " + data.niceName);
                    if(data.iconClass) {
                        this.layout.setIconCls(data.iconClass);
                    } else {
                        this.layout.setIconCls(this.getIcon());
                    }

                    this.reportConfig = data;

                    this.layout.updateLayout();
                }.bind(this)
            });
        }
    },

    chartColors: [
        0x01841c,
        0x3D32FF,
        0xFF1000,
        0xFFEE00,
        0x00FF21,
        0x7F92FF,
        0xFFD800
    ],

    getChart: function(data) {
        var chart = null;

        this.columnLabels = {};
        var colConfig;

        for(var f=0; f<data.columnConfiguration.length; f++) {
            colConfig = data.columnConfiguration[f];
            this.columnLabels[colConfig["name"]] = colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]);
        }


        if(data.chartType == 'line' || data.chartType == 'bar') {
            var storeFields = [];
            storeFields.push(data.xAxis);
            for(var i = 0; i < data.yAxis.length; i++) {
                storeFields.push(data.yAxis[i]);
            }

            var chartStore = new Ext.data.Store({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/reports/custom-report/chart?",
                    extraParams: {
                        name: this.config
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                fields: storeFields
            });
            chartStore.load();

            var series = [];
            for(var i = 0; i < data.yAxis.length; i++) {
                var yAxis = data.yAxis[i];
                series.push({
                    displayName: this.columnLabels[data.yAxis[i]],
                    type: (data.chartType == 'line' ? 'line' : 'bar'),
                    xField: data.xAxis,
                    yField: yAxis,
                    marker: {
                        radius: 4
                    },
                    highlight: true,
                    tooltip: {
                        trackMouse: true,
                        renderer: function (tooltip, record, item) {
                            tooltip.setHtml(record.get(data.xAxis) + ': ' + record.get(yAxis));
                        }
                    }
                });
            }

            chart = Ext.create('Ext.chart.CartesianChart', {
                store: chartStore,
                width: '100%',
                height: 350,
                insetPadding: 5,
                innerPadding: 10,
                legend: {
                    docked: 'bottom'
                },
                interactions: [
                    'itemhighlight',
                    {
                        type: 'panzoom',
                        zoomOnPanGesture: true
                    }
                ],
                axes: [
                    {
                        type: 'numeric',
                        fields: data.yAxis,
                        position: 'left',
                        grid: true
                    },{
                        type: 'category',
                        fields: data.xAxis,
                        position: 'bottom'
                    }
                ],
                series: series
            });

        } else if(data.chartType == 'pie') {
            var chartFields = [];
            if (data.pieLabelColumn) {
                chartFields.push(data.pieLabelColumn);
            };
            if (data.pieColumn) {
                chartFields.push({
                    name: data.pieColumn,
                    type: "int"
                });
            }

            var chartStore = pimcore.helpers.grid.buildDefaultStore(
                '/admin/reports/custom-report/chart?',
                chartFields,
                400000000
            );
            var proxy = chartStore.getProxy();
            proxy.extraParams.name = this.config;

            var chart = Ext.create('Ext.chart.PolarChart', {
                xtype: "polar",
                store: chartStore,
                theme: 'default-gradients',
                width: '100%',
                height: 350,
                innerPadding: 10,
                legend: {
                    docked: 'right'
                },
                interactions: ['rotate'],
                series: [{
                    type: 'pie',
                    xField: data.pieColumn,
                    highlight: true,
                    tooltip: {
                        trackMouse: true,
                        renderer: function (tooltip, record, item) {
                            var count = chartStore.getCount();
                            var value = record.get(data.pieColumn);


                            var sum = chartStore.sum(data.pieColumn);
                            var percentage = sum > 0 ? " (" + Math.round((value * 100 / sum)) + ' %)' : "";
                            tooltip.setHtml(record.get(data.pieLabelColumn) + ': ' + value + percentage);
                        }.bind(this)
                    }
                }]
            });

            //this is needed to display correct data in legend when no label is defined
            //label cannot be defined, because there is a bug when reloading chartstore with another amount of data entries
            var series = chart.getSeries()[0];
            series.provideLegendInfo = function (target) {
                var me = this,
                    store = me.getStore();

                if (store) {
                    var items = store.getData().items,
                        labelField = data.pieLabelColumn,
                        xField = me.getXField(),
                        hidden = me.getHidden(),
                        i, style, fill;

                    for (i = 0; i < items.length; i++) {
                        style = me.getStyleByIndex(i);
                        fill = style.fillStyle;
                        if (Ext.isObject(fill)) {
                            fill = fill.stops && fill.stops[0].color;
                        }
                        target.push({
                            name: labelField ? String(items[i].get(labelField)) : xField + ' ' + i,
                            mark: fill || style.strokeStyle || 'black',
                            disabled: hidden[i],
                            series: me.getId(),
                            index: i
                        });
                    }
                }
            };
        }

        return chart;
    },

    openReport: function() {
        var toolbar = pimcore.globalmanager.get("layout_toolbar");

        var reportClass = this.reportConfig.reportClass ? this.reportConfig.reportClass : "pimcore.report.custom.report";
        toolbar.showReports(reportClass, {
            name: this.reportConfig.name,
            text: this.reportConfig.niceName,
            niceName: this.reportConfig.niceName,
            iconCls: this.reportConfig.iconClass
        });

    }

});
