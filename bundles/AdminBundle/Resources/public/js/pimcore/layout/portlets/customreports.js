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

        this.layout = Ext.create('Portal.view.Portlet', Object.assign(defaultConf, {
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
                    url: Routing.generate('pimcore_admin_reports_customreport_portletreportlist'),
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
            title: t('settings'),
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
            url: Routing.generate('pimcore_admin_portal_updateportletconfig'),
            method: 'PUT',
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
                url: Routing.generate('pimcore_admin_reports_customreport_get'),
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
            this.columnLabels[colConfig["name"]] = colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"]);
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
                    url: Routing.generate('pimcore_admin_reports_customreport_chart'),
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
            }
            if (data.pieColumn) {
                chartFields.push({
                    name: data.pieColumn,
                    type: "int"
                });
            }

            var chartStore = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('pimcore_admin_reports_customreport_chart'),
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
        } else {
            this.panel = new Ext.Panel({
                layout: "fit",
                border: false,
                items: []
            });


            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_reports_customreport_get'),
                params: {
                    name: this.config
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    var grid = this.initGrid(data);

                    var items = [grid];

                    var subPanel = new Ext.Panel({
                        layout: "border",
                        border: false,
                        items: items
                    });

                    this.panel.add(subPanel);
                    this.panel.updateLayout();
                }.bind(this)
            });
            chart = this.panel;
        }

        return chart;
    },

    prepareGridConfig: function(data) {
        this.drillDownFilters = {};
        this.drillDownStores = [];

        this.storeFields = [];
        this.gridColumns = [];

        this.drillDownFilterDefinitions = [];
        this.columnLabels = {};
        this.gridfilters = {};

        var gridColConfig = {};

        for(var f=0; f<data.columnConfiguration.length; f++) {

            var colConfig = data.columnConfiguration[f];
            this.storeFields.push(colConfig["name"]);

            if (colConfig["displayType"] == "hide") {
                continue;
            }

            this.columnLabels[colConfig["name"]] = colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"]);

            gridColConfig = {
                header: colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"]),
                hidden: !colConfig["display"],
                sortable: colConfig["order"],
                dataIndex: colConfig["name"]
            };

            if(colConfig["width"]) {
                gridColConfig["width"] = intval(colConfig["width"]);
            }

            if(colConfig["filter"]) {
                gridColConfig["filter"] = colConfig["filter"];
                this.gridfilters[colConfig["name"]] = colConfig["filter"];
            }

            if (colConfig["displayType"] == "date") {
                gridColConfig["renderer"] = function (key, value, metaData, record) {
                    if (value) {
                        var timestamp = intval(value) * 1000;
                        var date = new Date(timestamp);

                        return Ext.Date.format(date, "Y-m-d H:i");
                    }
                    return "";
                }.bind(this, colConfig["name"]);
            }


            if(colConfig["filter_drilldown"] == 'only_filter' || colConfig["filter_drilldown"] == 'filter_and_show') {
                this.drillDownFilterDefinitions.push(colConfig);
            }

            if(colConfig["filter_drilldown"] != 'only_filter') {
                this.gridColumns.push(gridColConfig);
            }

            if (colConfig["columnAction"]) {
                this.gridColumns.push({
                    header: t("open"),
                    xtype: 'actioncolumn',
                    width: 40,
                    items: [
                        {
                            tooltip: t("open") + " " + (colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"])),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                            handler: function (colConfig, grid, rowIndex) {
                                var data = grid.getStore().getAt(rowIndex).getData();
                                var columnName = colConfig["name"];
                                var id = data[columnName];
                                var action = colConfig["columnAction"]
                                if (action == "openDocument") {
                                    pimcore.helpers.openElement(id, "document");
                                } else if (action == "openAsset") {
                                    pimcore.helpers.openElement(id, "asset");
                                } else if (action == "openObject") {
                                    pimcore.helpers.openElement(id, "object");
                                }
                            }.bind(this, colConfig)
                        }
                    ]
                });
            }
        }

    },

    initGrid: function (data) {
        this.prepareGridConfig(data);
        return this.createGrid();
    },

    createGrid: function() {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url = Routing.generate('pimcore_admin_reports_customreport_data');

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url, this.storeFields, itemsPerPage
        );
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var proxy = this.store.getProxy();
        proxy.extraParams.name = this.config;

        this.store.addListener('load', function() {
            var filterData = this.store.getFilters().items;

            for(var j = 0; j < this.drillDownStores.length; j++) {
                if(this.drillDownStores[j].notReload) {
                    //to prevent reopening of combo box
                    this.drillDownStores[j].notReload = false;
                } else {
                    this.drillDownStores[j].load({
                        params: {
                            filter: proxy.encodeFilters(filterData)
                        }
                    });
                }
            }

        }.bind(this));

        this.grid = new Ext.grid.GridPanel({
            region: "center",
            store: this.store,
            bbar: this.pagingtoolbar,
            columns: this.gridColumns,
            columnLines: true,
            plugins: ['pimcore.gridfilters'],
            stripeRows: true,
            trackMouseOver: true,
            viewConfig: {
                forceFit: false
            }
        });

        return this.grid;
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
