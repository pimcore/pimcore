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

pimcore.registerNS("pimcore.report.sql.report");
pimcore.report.sql.report = Class.create(pimcore.report.abstract, {

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

        var storeFields = [];
        var gridColums = [];
        var colConfig;
        var grodColConfig = {};
        var filters = [];

        for(var f=0; f<data.columnConfiguration.length; f++) {
            colConfig = data.columnConfiguration[f];
            storeFields.push(colConfig["name"]);

            grodColConfig = {
                header: colConfig["label"] ? ts(colConfig["label"]) : ts(colConfig["name"]),
                hidden: !colConfig["display"],
                sortable: colConfig["order"],
                dataIndex: colConfig["name"],
                filterable: false
            };

            if(colConfig["width"]) {
                grodColConfig["width"] = intval(colConfig["width"]);
            }

            if(colConfig["filter"]) {
                filters.push({
                    dataIndex: colConfig["name"],
                    type: colConfig["filter"]
                });

                grodColConfig["filterable"] = true;
            }

            gridColums.push(grodColConfig);
        }

        this.gridFilters = new Ext.ux.grid.GridFilters({
            filters: filters,
            encode: true,
            local: false
        });

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/sql/data",
            root: 'data',
            remoteSort: true,
            baseParams: {
                name: this.config["name"]
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

        this.grid = new Ext.grid.GridPanel({
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
            tbar: ["->",{
                xtype: "button",
                text: t("export_csv"),
                iconCls: "pimcore_icon_export",
                handler: function () {
                    var query = "";
                    var filterData = this.gridFilters.getFilterData();
                    if(filterData.length > 0) {
                        query = "filter=" + encodeURIComponent(this.gridFilters.buildQuery(filterData).filter);
                    } else {
                        query = "filter="
                    }

                    query += "&name=" + this.config.name;

                    var downloadUrl = "/admin/reports/sql/download-csv?" + query;
                    pimcore.helpers.download(downloadUrl);
                }.bind(this)
            }]
        });

        this.panel.add(this.grid);
        this.panel.doLayout();
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
                url: "/admin/reports/sql/get",
                params: {
                    name: this.config.name
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    this.initGrid(data);
                }.bind(this)
            });
        }

        return this.panel;
    }
});




pimcore.registerNS("pimcore.report.sql.reportplugin");
pimcore.report.sql.reportplugin = Class.create(pimcore.plugin.admin, {

    getClassName: function() {
        return "pimcore.report.sql.reportplugin";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params,broker){

        // get available reports
        Ext.Ajax.request({
            url: "/admin/reports/sql/get-report-config",
            method: "get",
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var report;

                if(res.success && res.reports && res.reports.length > 0) {
                    for (var i=0; i<res.reports.length; i++) {
                        report = res.reports[i];

                        // set some defaults
                        if(!report["group"]) {
                            report["group"] = "sql_reports"
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
                        pimcore.report.broker.addReport(pimcore.report.sql.report, report["group"], {
                            name: report["name"],
                            text: report["niceName"],
                            niceName: report["niceName"],
                            iconCls: report["iconClass"]
                        });

                        // add the report directly into the reports menu in "extras" -> main menu
                        if(report["menuShortcut"]) {
                            try {
                                var reportMenu = Ext.getCmp("pimcore_mainmenu_extras_reports");
                                if(reportMenu) {
                                    reportMenu.menu.add({
                                        text: report["niceName"],
                                        iconCls: report["iconClass"],
                                        handler: function (report) {
                                            var toolbar = pimcore.globalmanager.get("layout_toolbar");
                                            toolbar.showReports(pimcore.report.sql.report, {
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
});

(function() {
    new pimcore.report.sql.reportplugin();
})();

