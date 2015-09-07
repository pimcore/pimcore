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

pimcore.registerNS("pimcore.report.panel");
pimcore.report.panel = Class.create({


    initialize: function(type) {
        this.type = type;

        if (!this.type) {
            this.type = "global";
        }

        if (typeof arguments[1] == "object") {
            this.reference = arguments[1];
        }

        if (this.type == "global") {
            this.getLayout();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_reports");
    },

    getLayout: function () {
        
        var user = pimcore.globalmanager.get("user");
        
        if(!user.isAllowed("reports")) {
            return;
        }
        
        if (this.layout == null) {

            if (this.getReportCount() < 1 && this.type != "global") {
                return;
            }

            this.tree = Ext.create('Ext.tree.Panel', {
                region: "west",
                title: t("select_a_report"),
                width: 200,
                enableDD: false,
                autoScroll: true,
                collapsible: true,
                rootVisible: false,
                root: {
                    id: 0
                },
                bodyStyle: "padding: 5px;",
                listeners: {
                    //"click": function () {
                    //    this.expand();
                    //},

                    "itemclick": this.openReport.bind(this)
                }
            });

            var rootNode = this.tree.getRootNode();

            // add report groups
            var groupNode;
            var group;
            var reportClass, reportConfig;
            var reportCount;

            for (var i = 0; i < pimcore.report.broker.groups.length; i++) {

                group = pimcore.report.broker.groups[i];
                var groupNodeConfig = {
                    text: group.name,
                    iconCls: group.iconCls,
                    leaf: false

                };
                groupNode = rootNode.createNode(groupNodeConfig);

                reportCount = 0;

                // add reports to group
                if (typeof pimcore.report.broker.reports[group.id] == "object") {
                    for (var r = 0; r < pimcore.report.broker.reports[group.id].length; r++) {
                        reportClass = pimcore.report.broker.reports[group.id][r]["class"];
                        reportConfig = pimcore.report.broker.reports[group.id][r]["config"];
                        if(!reportConfig) {
                            reportConfig = {};
                        }

                        if (reportClass.prototype.matchType(this.type)) {
                            var childConfig = {
                                text: reportConfig["text"] ? ts(reportConfig["text"]) : t(reportClass.prototype.getName()),
                                iconCls: reportConfig["iconCls"] ? reportConfig["iconCls"] : reportClass.prototype.getIconCls(),
                                leaf: true,
                                xdata: {
                                    reportClass: reportClass,
                                    reportConfig: reportConfig
                                }
                            };
                            groupNode.appendChild(childConfig);
                            reportCount++;
                        }
                    }
                    if (reportCount > 0) {
                        this.tree.getRootNode().appendChild(groupNode);
                    }
                }
            }

            this.tree.expandAll();
            this.tree.updateLayout();

            this.reportContainer = new Ext.Panel({
                region: "center",
                layout: "fit"
            });


            var layoutConfig = {
                title: t('reports_and_marketing'),
                border: false,
                layout: "border",
                items: [this.tree,this.reportContainer],
                iconCls: "pimcore_icon_reports"
            };

            // register an id for the standalone version
            if (this.type == "global") {
                layoutConfig.id = "pimcore_reports";
                layoutConfig.closable = true;
            }

            this.layout = new Ext.Panel(layoutConfig);

            // add panel to tabbar in standalone mode
            if (this.type == "global") {
                var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                tabPanel.add(this.layout);
                tabPanel.setActiveItem("pimcore_reports");

                this.layout.on("destroy", function () {
                    pimcore.globalmanager.remove("reports");
                }.bind(this));

                pimcore.layout.refresh();
            }
        }

        return this.layout;
    },

    openReport: function (tree, record, item, index, e, eOpts ) {
        record.expand();

        var data = record.data;
        if (data.leaf) {
            var reportConfig = data.xdata.reportConfig;
            var reportClass = data.xdata.reportClass;
            var report = new reportClass(this, this.type, this.reference, reportConfig);
        }
    },

    addReport: function (report) {
        this.reportContainer.removeAll();
        this.reportContainer.add(report);
        this.reportContainer.updateLayout();
    },

    getReportCount: function () {
        var group;
        var report;
        var reportCount = 0;

        for (var i = 0; i < pimcore.report.broker.groups.length; i++) {

            group = pimcore.report.broker.groups[i];

            // add reports to group
            if (typeof pimcore.report.broker.reports[group.id] == "object") {
                for (var r = 0; r < pimcore.report.broker.reports[group.id].length; r++) {
                    report = pimcore.report.broker.reports[group.id][r]["class"];
                    if (report.prototype.matchType(this.type)) {
                        reportCount++;
                    }
                }
            }
        }
        return reportCount;
    }
});