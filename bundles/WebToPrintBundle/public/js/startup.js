pimcore.registerNS("pimcore.bundle.web2print.startup");

pimcore.bundle.web2print.startup = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");


        if (user.isAllowed("web2print_settings") && perspectiveCfg.inToolbar("settings.web2print")) {

            menu.settings.items.push({
                text: t("web2print_settings"),
                iconCls: "pimcore_nav_icon_print_settings",
                priority: 55,
                itemId: 'pimcore_menu_settings_web2print_settings',
                handler: this.web2printSettings
            });
        }

    },

    web2printSettings: function () {
        try {
            pimcore.globalmanager.get("bundle_web2print").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_web2print", new pimcore.bundle.web2print.settings());
        }
    },

});

const pimcoreBUndleWeb2print = new pimcore.bundle.web2print.startup();