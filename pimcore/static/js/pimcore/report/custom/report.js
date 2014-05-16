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

    initGrid: function (data) {
        this.drillDownFilters = {};
        this.drillDownStores = [];

        var storeFields = [];
        var gridColums = [];
        var colConfig;
        var gridColConfig = {};
        var filters = [];
        var drillDownFilterDefinitions = [];
        this.columnLabels = {};

        for(var f=0; f<data.columnConfiguration.length; f++) {
            colConfig = data.columnConfiguration[f];
            storeFields.push(colConfig["name"]);

            this.columnLabels[colConfig["name"]] = colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]);

            gridColConfig = {
                header: colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]),
                hidden: !colConfig["display"],
                sortable: colConfig["order"],
                dataIndex: colConfig["name"],
                filterable: false
            };

            if(colConfig["width"]) {
                gridColConfig["width"] = intval(colConfig["width"]);
            }

            if(colConfig["filter"]) {
                filters.push({
                    dataIndex: colConfig["name"],
                    type: colConfig["filter"]
                });

                gridColConfig["filterable"] = true;
            }


            if(colConfig["filter_drilldown"] == 'only_filter' || colConfig["filter_drilldown"] == 'filter_and_show') {
                drillDownFilterDefinitions.push(colConfig);
            }

            if(colConfig["filter_drilldown"] != 'only_filter') {
                gridColums.push(gridColConfig);
            }

        }

        this.gridFilters = new Ext.ux.grid.GridFilters({
            filters: filters,
            encode: true,
            local: false
        });

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/custom-report/data",
            root: 'data',
            remoteSort: true,
            baseParams: {
                name: this.config["name"]
            },
            listeners: {
                load: function() {
                    var filterData = this.gridFilters.getFilterData();

                    if(this.chartStore) {
                        this.chartStore.load({
                            params: {
                                name: this.config["name"],
                                filter: this.gridFilters.buildQuery(filterData).filter
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
                                    filter: this.gridFilters.buildQuery(filterData).filter
                                }
                            });
                        }
                    }

                }.bind(this)
            },
            fields: storeFields
        });
        this.store.load();

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 40,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 50,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        var topBar = this.buildTopBar(drillDownFilterDefinitions);

        topBar.push("->");
        topBar.push({
            xtype: "button",
            text: t("export_csv"),
            iconCls: "pimcore_icon_export",
            handler: function () {
                var query = "";
                var filterData = this.gridFilters.getFilterData();
                if(filterData.length > 0) {
                    query = "filter=" + encodeURIComponent(this.gridFilters.buildQuery(filterData).filter);
                } else {
                    query = "filter=";
                }

                query += "&name=" + this.config.name;

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
            }.bind(this)
        });


        this.grid = new Ext.grid.GridPanel({
            region: "center",
            store: this.store,
            bbar: this.pagingtoolbar,
            columns: gridColums,
            columnLines: true,
            plugins: [this.gridFilters],
            stripeRows: true,
            trackMouseOver: true,
            viewConfig: {
                forceFit: false
            },
            tbar: topBar
        });

        return this.grid;
    },

    buildTopBar: function(drillDownFilterDefinitions) {
        var drillDownFilterComboboxes = [];

        for(var i = 0; i < drillDownFilterDefinitions.length; i++) {
            drillDownFilterComboboxes.push({
                xtype: 'label',
                text: drillDownFilterDefinitions[i]["label"] ? ts(drillDownFilterDefinitions[i]["label"])
                                                    : ts(drillDownFilterDefinitions[i]["name"]),
                style: 'padding-right: 5px'
            });

            var drillDownStore = new Ext.data.JsonStore({
                url: '/admin/reports/custom-report/drill-down-options',
                root: 'data',
                baseParams: {
                    name: this.config["name"],
                    field: drillDownFilterDefinitions[i]["name"]
                },
                fields: ['value']
            });
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

                        this.store.setBaseParam('drillDownFilters[' + fieldname + ']', value);
                        if(this.chartStore) {
                            this.chartStore.setBaseParam('drillDownFilters[' + fieldname + ']', value);
                        }
                        for(var j = 0; j < this.drillDownStores.length; j++) {
                            if(this.drillDownStores[j] != combo.getStore()) {
                                this.drillDownStores[j].setBaseParam('drillDownFilters[' + fieldname + ']', value);
                            } else {
                                this.drillDownStores[j].notReload = true;
                            }
                        }

                        this.store.reload();
                    }.bind(this, drillDownFilterDefinitions[i]["name"])
                },
                valueField: 'value',
                displayField: 'value'
            });
            if(i < drillDownFilterDefinitions.length-1) {
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

    getChart: function(data) {
        var chartPanel = null;

        if(data.chartType == 'line' || data.chartType == 'bar') {
            var storeFields = [];
            storeFields.push(data.xAxis);
            for(var i = 0; i < data.yAxis.length; i++) {
                storeFields.push(data.yAxis[i]);
            }

            this.chartStore = new Ext.data.JsonStore({
                autoDestroy: true,
                url: "/admin/reports/custom-report/chart",
                root: 'data',
                baseParams: {
                    name: this.config["name"]
                },
                fields: storeFields
            });

            var series = [];
            for(var i = 0; i < data.yAxis.length; i++) {
                series.push({
                    displayName: this.columnLabels[data.yAxis[i]],
                    type: (data.chartType == 'line' ? 'line' : 'column'),
                    yField: data.yAxis[i],
                    style: {
                        color: this.chartColors[i]
                    }
                });
            }

            chartPanel = new Ext.Panel({
                id:"cartID",
                region: "north",
                height: 350,
                border: false,
                items: [{
                    xtype: (data.chartType == 'line' ? 'linechart' : 'columnchart'),
                    store: this.chartStore,
                    xField: data.xAxis,
                    chartStyle: {
                        padding: 10,
                        legend: {
                            display: 'bottom'
                        }
                    },
                    series: series
                }]
            });
        } else if(data.chartType == 'pie') {
            this.chartStore = new Ext.data.JsonStore({
                autoDestroy: true,
                url: "/admin/reports/custom-report/chart",
                root: 'data',
                baseParams: {
                    name: this.config["name"]
                },
                fields: [data.pieLabelColumn, data.pieColumn]
            });
            this.chartStore.load();

            chartPanel = new Ext.Panel({
                region: "north",
                height: 350,
                border: false,
                items: [{
                    store: this.chartStore,
                    xtype: 'piechart',
                    dataField: data.pieColumn,
                    categoryField: data.pieLabelColumn,
                    chartStyle: {
                        padding: 10,
                        legend: {
                            display: 'right'
                        }
                    }
                }]
            });
        }

        return chartPanel;
    },

    getPanel: function () {

        if(!this.panel) {
            this.panel = new Ext.Panel({
                id: "panel",
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
                        var chartPanel = this.getChart(data);
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
                    this.panel.doLayout();
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

                            pimcore.report.broker.addGroup(report["group"], report["group"], report["groupIconClass"]);
                            pimcore.report.broker.addReport(pimcore.report.custom.report, report["group"], {
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
                                                toolbar.showReports(pimcore.report.custom.report, {
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

