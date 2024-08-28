/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.customreports.panel");
/**
 * @private
 */
pimcore.bundle.customreports.panel = Class.create({


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
                title: t("reports"),
                width: 250,
                enableDD: false,
                split: true,
                autoScroll: true,
                collapsible: true,
                rootVisible: false,
                root: {
                    id: 0
                },
                listeners: {
                    "itemclick": this.openReport.bind(this)
                }
            });

            var rootNode = this.tree.getRootNode();

            // add report groups
            var groupNode;
            var group;
            var reportClass, reportConfig;
            var reportCount;

            for (var i = 0; i < pimcore.bundle.customreports.broker.groups.length; i++) {

                group = pimcore.bundle.customreports.broker.groups[i];

                var groupIconCls = group.iconCls ? group.iconCls : '';
                groupIconCls = groupIconCls + ' ' + groupIconCls.replace(/^pimcore_nav_icon_/, 'pimcore_icon_');

                var groupNodeConfig = {
                    text: group.name,
                    iconCls: groupIconCls,
                    leaf: false

                };
                groupNode = rootNode.createNode(groupNodeConfig);

                reportCount = 0;

                // add reports to group
                if (typeof pimcore.bundle.customreports.broker.reports[group.id] == "object") {
                    for (var r = 0; r < pimcore.bundle.customreports.broker.reports[group.id].length; r++) {
                        reportClass = pimcore.bundle.customreports.broker.reports[group.id][r]["class"];
                        try {
                            reportClass = stringToFunction(reportClass);
                            reportConfig = pimcore.bundle.customreports.broker.reports[group.id][r]["config"];
                            if (!reportConfig) {
                                reportConfig = {};
                            }

                            if (reportClass.prototype.matchType(this.type)) {

                                var iconCls = reportConfig["iconCls"] ? reportConfig["iconCls"] : reportClass.prototype.getIconCls();
                                iconCls = iconCls + ' ' + iconCls.replace(/^pimcore_nav_icon_/, 'pimcore_icon_');

                                var childConfig = {
                                    text: reportConfig["text"] ? t(reportConfig["text"]) : t(reportClass.prototype.getName()),
                                    iconCls: iconCls,
                                    leaf: true,
                                    xdata: {
                                        reportClass: reportClass,
                                        reportConfig: reportConfig
                                    }
                                };
                                groupNode.appendChild(childConfig);
                                reportCount++;
                            }
                        } catch (e) {
                            console.log(e);
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
                tabConfig: {
                    tooltip: t('reports')
                },
                border: false,
                layout: "border",
                items: [this.tree,this.reportContainer],
                iconCls: "pimcore_material_icon_reports pimcore_material_icon"
            };

            // register an id for the standalone version
            if (this.type == "global") {
                layoutConfig.id = "pimcore_reports";
                layoutConfig.closable = true;
                layoutConfig["title"] = t('reports');
                layoutConfig["iconCls"] = 'pimcore_icon_reports';
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

    openReportViaToolbar: function (reportClass, reportConfig) {

        try {
            reportClass = stringToFunction(reportClass);

            var report = new reportClass(this, this.type, null, reportConfig);

            var store = this.tree.getStore();
            var record = store.findRecord('text', reportConfig.name);
            if (record) {
                var selModel = this.tree.getSelectionModel();
                selModel.select(record);
            }
        } catch (e) {
            console.log(e);
        }
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
        var reportCount = 0;
        var reportClass;

        for (var i = 0; i < pimcore.bundle.customreports.broker.groups.length; i++) {

            group = pimcore.bundle.customreports.broker.groups[i];
            // add reports to group
            if (typeof pimcore.bundle.customreports.broker.reports[group.id] == "object") {
                for (var r = 0; r < pimcore.bundle.customreports.broker.reports[group.id].length; r++) {
                    reportClass = pimcore.bundle.customreports.broker.reports[group.id][r]["class"];
                    try {
                        reportClass = stringToFunction(reportClass);
                        if (reportClass.prototype.matchType(this.type)) {
                            reportCount++;
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            }
        }
        return reportCount;
    }
});
