pimcore.registerNS('pimcore.bundle.googlemarketing.startup');

pimcore.bundle.googlemarketing.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (event) {
        const menu = event.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (menu.marketing && perspectiveCfg.inToolbar("settings.marketingReports")
            && user.isAllowed("google_marketing")) {
            menu.marketing.items.push({
                text: t("marketing_settings"),
                iconCls: "pimcore_nav_icon_marketing_settings",
                itemId: 'pimcore_menu_marketing_settings',
                handler: this.marketingSettings,
                priority: 30
            });
        }
    },

    marketingSettings: function () {
        try {
            pimcore.globalmanager.get("bundle_marketing_settings").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_marketing_settings", new pimcore.bundle.googlemarketing.settings());
        }
    }
});

var pimcoreBundleGoogleMarketing = new pimcore.bundle.googlemarketing.startup();