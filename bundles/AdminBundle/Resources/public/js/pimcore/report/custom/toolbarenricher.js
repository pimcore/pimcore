pimcore.registerNS("pimcore.report.custom.toolbarenricher");
pimcore.report.custom.toolbarenricher = Class.create(pimcore.plugin.admin, {

    getClassName: function() {
        return "pimcore.report.custom.toolbarenricher";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params,broker){

        var user = pimcore.globalmanager.get("user");
        if(user.isAllowed("reports")){

            // get available reports
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_reports_customreport_getreportconfig'),
                success: function (response) {
                    var res = Ext.decode(response.responseText);
                    var report;

                    var groupToolbarMenuEntries = {};

                    if(res.success && res.reports && res.reports.length > 0) {
                        for (var i = 0; i < res.reports.length; i++) {
                            report = res.reports[i];

                            // set some defaults
                            if(!report["group"]) {
                                report["group"] = "custom_reports"
                            }

                            if(!report["niceName"]) {
                                report["niceName"] = report["name"]
                            }

                            if(!report["iconClass"]) {
                                report["iconClass"] = "pimcore_nav_icon_custom_report_default";
                            }

                            if(!report["groupIconClass"]) {
                                report["groupIconClass"] = "pimcore_nav_icon_custom_report_group_default";
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
                                        var parentMenuEntry = toolbar["marketingMenu"];

                                        if(report["group"] && report["group"] != 'custom_reports') {

                                            if(!groupToolbarMenuEntries[report["group"]]) {
                                                groupToolbarMenuEntries[report["group"]] = new Ext.menu.Item({
                                                    text: t(report["group"]),
                                                    iconCls: report["groupIconClass"],
                                                    menu: []
                                                });

                                                toolbar["marketingMenu"].add(groupToolbarMenuEntries[report["group"]]);
                                            }
                                            parentMenuEntry = groupToolbarMenuEntries[report["group"]].getMenu();
                                        }

                                        parentMenuEntry.add({
                                            text: t(report["niceName"]),
                                            iconCls: report["iconClass"],
                                            handler: function (report, reportClass) {
                                                toolbar.showReports(reportClass, {
                                                    name: report["name"],
                                                    text: t(report["niceName"]),
                                                    niceName: report["niceName"],
                                                    iconCls: report["iconClass"]
                                                });
                                            }.bind(this, report, reportClass)
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
    new pimcore.report.custom.toolbarenricher();
})();

