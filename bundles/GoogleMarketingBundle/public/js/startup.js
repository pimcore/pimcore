pimcore.registerNS('pimcore.bundle.google_marketing.startup');

pimcore.bundle.google_marketing.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (event) {
        const menu = event.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (menu.marketing && perspectiveCfg.inToolbar("settings.marketingReports")
            && user.isAllowed("reports") && user.isAllowed("system_settings")) {
            menu.marketing.items.push({
                text: t("marketing_settings"),
                iconCls: "pimcore_nav_icon_marketing_settings",
                itemId: 'pimcore_menu_marketing_settings',
                handler: this.reportSettings,
                priority: 30
            });
        }
    },
    reportSettings: function () {
        try {
            pimcore.globalmanager.get("reports_settings").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("reports_settings", new pimcore.report.settings());
        }
    }
});

var google_marketing_bundle = new pimcore.bundle.google_marketing.startup();