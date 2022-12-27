pimcore.registerNS("pimcore.customReports");


pimcore.customReports = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");
        var customReportsMenu = [];

        if (user.isAllowed("reports_config") && perspectiveCfg.inToolbar("settings.customReports")) {
            customReportsMenu.push({
                text: t("custom_reports"),
                iconCls: "pimcore_nav_icon_reports",
                itemId: 'pimcore_menu_marketing_custom_reports',
                handler: this.showCustomReports
            });
        }

        if (user.isAllowed("reports") && perspectiveCfg.inToolbar("marketing.reports")) {
            customReportsMenu.push({
                text: t("reports"),
                iconCls: "pimcore_nav_icon_reports",
                itemId: 'pimcore_menu_marketing_reports',
                handler: this.showReports.bind(this, null)
            });
        }

        if (customReportsMenu.length > 0) {
            const toolbar = pimcore.globalmanager.get('layout_toolbar');

            toolbar.marketingMenu.add(customReportsMenu);
        }
    },

    showCustomReports: function () {
        try {
            pimcore.globalmanager.get("custom_reports_settings").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("custom_reports_settings", new pimcore.report.custom.settings());
        }
    },

    showReports: function (reportClass, reportConfig) {
        try {
            pimcore.globalmanager.get("reports").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("reports", new pimcore.report.panel());
        }

        // this is for generated/configured reports like the SQL Report
        try {
            if(reportClass) {
                pimcore.globalmanager.get("reports").openReportViaToolbar(reportClass, reportConfig);
            }
        } catch (e) {
            console.log(e);
        }
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
    }
})

const customReports = new pimcore.customReports();