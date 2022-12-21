pimcore.registerNS("pimcore.web2print");

pimcore.web2print = Class.create({
    initialize: function () {
        //document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        const toolbar = pimcore.globalmanager.get('layout_toolbar');

        if (user.isAllowed("glossary") && perspectiveCfg.inToolbar("extras.glossary")) {
            if (user.isAllowed("web2print_settings") && perspectiveCfg.inToolbar("settings.web2print")) {

                toolbar.settingsMenu.add({
                    text: t("web2print_settings"),
                    iconCls: "pimcore_nav_icon_print_settings",
                    itemId: 'pimcore_menu_settings_web2print_settings',
                    handler: this.web2printSettings
                });
            }
        }
    },

    web2printSettings: function () {

        try {
            pimcore.globalmanager.get("web2print_web2print").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("web2print_web2print", new pimcore.web2print.web2print());
        }
    },

});

const web2print = new pimcore.web2print();