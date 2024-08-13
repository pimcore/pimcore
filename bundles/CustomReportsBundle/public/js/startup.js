pimcore.registerNS("pimcore.bundle.customreports.startup");


pimcore.bundle.customreports.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function () {
        this.registerCustomReportsPanel();
    },

    registerCustomReportsPanel: function () {
        this.customReportsPanel = pimcore.globalmanager.get('customReportsPanelImplementationFactory');

        this.customReportsPanel.registerImplementation(pimcore.bundle.customreports.panel);
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if(menu.marketing) {
            if (user.isAllowed("reports") && perspectiveCfg.inToolbar("marketing.reports")) {
                menu.marketing.items.push({
                    text: t("reports"),
                    priority: 5,
                    iconCls: "pimcore_nav_icon_reports",
                    itemId: 'pimcore_menu_marketing_reports',
                    handler: this.showReports.bind(this, null)
                });
            }

            if (user.isAllowed("reports_config") && perspectiveCfg.inToolbar("settings.customReports")) {
                menu.marketing.items.push({
                    text: t("custom_reports"),
                    priority: 6,
                    iconCls: "pimcore_nav_icon_reports",
                    itemId: 'pimcore_menu_marketing_custom_reports',
                    handler: this.showCustomReports
                });
            }
        }
    },

    showCustomReports: function () {
        try {
            pimcore.globalmanager.get("custom_reports_settings").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("custom_reports_settings", new pimcore.bundle.customreports.custom.settings());
        }
    },

    showReports: function (reportClass, reportConfig) {
        try {
            pimcore.globalmanager.get("reports").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("reports", this.customReportsPanel.getNewReportInstance());
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
        if (user.isAllowed("reports_config")) {
            pimcore.helpers.keyBindingMapping.customReports = function() {
                customreports.showCustomReports();
            }
        }
        if (user.isAllowed("reports")) {
            pimcore.helpers.keyBindingMapping.reports = function() {
                customreports.showReports();
            }
        }
    }
})

const customreports = new pimcore.bundle.customreports.startup();