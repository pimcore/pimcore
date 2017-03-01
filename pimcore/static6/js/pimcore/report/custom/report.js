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

pimcore.registerNS("pimcore.report.custom.report");
pimcore.report.custom.report = Class.create(pimcore.report.abstract, {

    drillDownFilters: {},
    drillDownStores: [],

    matchType: function (type) {
        var types = ["global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "overview";
    },

    getIconCls: function () {
        return "pimcore_icon_sql";
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

            this.columnLabels[colConfig["name"]] = colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]);

            gridColConfig = {
                header: colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]),
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
            };


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
                            tooltip: t("open") + " " + (colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"])),
                            icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
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

    createGrid: function() {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url = '/admin/reports/custom-report/data?';
        this.store = pimcore.helpers.grid.buildDefaultStore(
            url, this.storeFields, itemsPerPage
        );
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var proxy = this.store.getProxy();
        proxy.extraParams.name = this.config["name"];

        this.store.addListener('load', function() {
            var filterData = this.store.getFilters().items;

            if(this.chartStore) {
                this.chartStore.load({
                    params: {
                        name: this.config["name"],
                        filter: proxy.encodeFilters(filterData)
                    }
                });
            }

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

        var topBar = this.buildTopBar(this.drillDownFilterDefinitions);

        //export button
        var exportBtnHandler = function (btn) {
            var query = "";
            var filterData = this.store.getFilters().items;

            if(filterData.length > 0) {
                query = "filter=" + encodeURIComponent(proxy.encodeFilters(filterData));
            } else {
                query = "filter=";
            }

            query += "&name=" + this.config.name;

            if (btn.getItemId() === 'exportWithHeaders') {
                query += '&headers=1';
            }

            if(this.drillDownFilters) {
                var fieldnames = Object.getOwnPropertyNames(this.drillDownFilters);
                for(var j = 0; j < fieldnames.length; j++) {
                    if(this.drillDownFilters[fieldnames[j]] !== null) {
                        query += "&" + 'drillDownFilters[' + fieldnames[j] + ']='
                            + this.drillDownFilters[fieldnames[j]];
                    }
                }
            }

            var downloadUrl = "/admin/reports/custom-report/download-csv?" + query;
            pimcore.helpers.download(downloadUrl);
        };

        topBar.push("->");

        topBar.push({
            xtype: 'splitbutton',
            text: t("export_csv"),
            iconCls: "pimcore_icon_export",
            handler: exportBtnHandler.bind(this),
            menu:[{
                text: t("export_csv_include_headers"),
                itemId: 'exportWithHeaders',
                iconCls: "pimcore_icon_export",
                handler: exportBtnHandler.bind(this)
            }]
        });

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
            },
            tbar: topBar
        });

        return this.grid;
    },

    initGrid: function (data) {
        this.prepareGridConfig(data);
        return this.createGrid();
    },

    buildTopBar: function(drillDownFilterDefinitions) {
        var drillDownFilterComboboxes = [];

        for(var i = 0; i < this.drillDownFilterDefinitions.length; i++) {
            drillDownFilterComboboxes.push({
                xtype: 'label',
                text: this.drillDownFilterDefinitions[i]["label"] ? ts(this.drillDownFilterDefinitions[i]["label"])
                                                    : ts(this.drillDownFilterDefinitions[i]["name"]),
                style: 'padding-right: 5px'
            });

            var drillDownStore = pimcore.helpers.grid.buildDefaultStore(
                '/admin/reports/custom-report/drill-down-options?',
                ['value'],
                400
            );
            var proxy = drillDownStore.getProxy();
            proxy.extraParams.name = this.config["name"];
            proxy.extraParams.field = this.drillDownFilterDefinitions[i]["name"];

            this.drillDownStores.push(drillDownStore);

            drillDownFilterComboboxes.push({
                xtype: 'combo',
                forceSelection: true,
                triggerAction: 'all',
                store: drillDownStore,
                listeners: {
                    select: function(fieldname, combo, record, index) {
                        var value = combo.getValue();
                        this.drillDownFilters[fieldname] = value;

                        var proxy = this.store.getProxy();
                        proxy.extraParams['drillDownFilters[' + fieldname + ']'] = value;
                        if(this.chartStore) {
                            var chartProxy = this.chartStore.getProxy();
                            chartProxy.extraParams['drillDownFilters[' + fieldname + ']'] = value;
                        }
                        for(var j = 0; j < this.drillDownStores.length; j++) {
                            if(this.drillDownStores[j] != combo.getStore()) {
                                var drillDownProxy = this.drillDownStores[j].getProxy();
                                drillDownProxy.extraParams['drillDownFilters[' + fieldname + ']'] = value;
                            } else {
                                this.drillDownStores[j].notReload = true;
                            }
                        }

                        this.store.reload();
                    }.bind(this, this.drillDownFilterDefinitions[i]["name"])
                },
                valueField: 'value',
                displayField: 'value'
            });
            if(i < this.drillDownFilterDefinitions.length-1) {
                drillDownFilterComboboxes.push('-');
            }
        }
        return drillDownFilterComboboxes;
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

    getChart: function(initData) {

        if(initData) {
            this.chartInitData = initData;
        }
        var data = this.chartInitData;

        if(data.chartType == 'line' || data.chartType == 'bar') {

            var storeFields = [];
            storeFields.push(data.xAxis);
            for(var i = 0; i < data.yAxis.length; i++) {
                storeFields.push(data.yAxis[i]);
            }

            this.chartStore = pimcore.helpers.grid.buildDefaultStore(
                '/admin/reports/custom-report/chart?',
                storeFields,
                400000000
            );
            var proxy = this.chartStore.getProxy();
            proxy.extraParams.name = this.config["name"];

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


            var chart = Ext.create('Ext.chart.CartesianChart', {
                store: this.chartStore,
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

            this.chartStore = pimcore.helpers.grid.buildDefaultStore(
                '/admin/reports/custom-report/chart?',
                chartFields,
                400000000
            );
            var proxy = this.chartStore.getProxy();
            proxy.extraParams.name = this.config["name"];

            var chart = Ext.create('Ext.chart.PolarChart', {
                xtype: "polar",
                store: this.chartStore,
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
                            var count = this.chartStore.getCount();
                            var value = record.get(data.pieColumn);


                            var sum = this.chartStore.sum(data.pieColumn);
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

    getChartPanel: function(data) {
        this.chartPanel = new Ext.Panel({
            region: "north",
            height: 350,
            border: false,
            items: [this.getChart(data)]
        });

        return this.chartPanel;
    },

    getPanel: function () {

        if(!this.panel) {
            this.panel = new Ext.Panel({
                title: this.config["niceName"],
                layout: "fit",
                border: false,
                items: []
            });


            Ext.Ajax.request({
                url: "/admin/reports/custom-report/get",
                params: {
                    name: this.config.name
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    var grid = this.initGrid(data);

                    var items = [];
                    if(data.chartType) {
                        var chartPanel = this.getChartPanel(data);
                        if(chartPanel) {
                            items.push(chartPanel);
                        }
                    }

                    items.push(grid);

                    var subPanel = new Ext.Panel({
                        layout: "border",
                        border: false,
                        items: items
                    });

                    this.panel.add(subPanel);
                    this.panel.updateLayout();
                }.bind(this)
            });
        }

        return this.panel;
    }


});




pimcore.registerNS("pimcore.report.custom.reportplugin");
pimcore.report.custom.reportplugin = Class.create(pimcore.plugin.admin, {

    getClassName: function() {
        return "pimcore.report.custom.reportplugin";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params,broker){

        var user = pimcore.globalmanager.get("user");
        if(user.isAllowed("reports")){

            // get available reports
            Ext.Ajax.request({
                url: "/admin/reports/custom-report/get-report-config",
                success: function (response) {
                    var res = Ext.decode(response.responseText);
                    var report;

                    if(res.success && res.reports && res.reports.length > 0) {
                        for (var i=0; i<res.reports.length; i++) {
                            report = res.reports[i];

                            // set some defaults
                            if(!report["group"]) {
                                report["group"] = "custom_reports"
                            }

                            if(!report["niceName"]) {
                                report["niceName"] = report["name"]
                            }

                            if(!report["iconClass"]) {
                                report["iconClass"] = "pimcore_icon_sql";
                            }

                            if(!report["groupIconClass"]) {
                                report["groupIconClass"] = "pimcore_icon_sql";
                            }

                            var reportClass = report.reportClass ? report.reportClass : "pimcore.report.custom.report";
                            pimcore.report.broker.addGroup(report["group"], report["group"], report["groupIconClass"]);
                            pimcore.report.broker.addReport(reportClass, report["group"], {
                                name: report["name"],
                                text: report["niceName"],
                                niceName: report["niceName"],
                                iconCls: report["iconClass"]
                            });

                            // add the report directly into the reports menu in "extras" -> main menu
                            if(report["menuShortcut"]) {
                                try {
                                    var toolbar = pimcore.globalmanager.get("layout_toolbar");
                                    if(toolbar["marketingMenu"]) {
                                        toolbar["marketingMenu"].add({
                                            text: report["niceName"],
                                            iconCls: report["iconClass"],
                                            handler: function (report) {
                                                toolbar.showReports(reportClass, {
                                                    name: report["name"],
                                                    text: report["niceName"],
                                                    niceName: report["niceName"],
                                                    iconCls: report["iconClass"]
                                                });
                                            }.bind(this, report)
                                        });
                                    }
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});

(function() {
    new pimcore.report.custom.reportplugin();
})();

