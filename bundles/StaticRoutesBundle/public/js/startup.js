pimcore.registerNS("pimcore.staticroutes");


pimcore.staticroutes = Class.create({
    initialize: function () {
        //document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        const toolbar = pimcore.globalmanager.get('layout_toolbar');

        if (user.isAllowed("routes") && perspectiveCfg.inToolbar("settings.routes")) {
            toolbar.settingsMenu.add({
                text: t("static_routes"),
                iconCls: "pimcore_nav_icon_routes",
                itemId: 'pimcore_menu_settings_static_routes',
                handler: this.editRoutes
            });
        }
    },

    editRoutes: function () {

        try {
            pimcore.globalmanager.get("staticroutes").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("staticroutes", new pimcore.settings.staticroutes());
        }
    },



    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        if (user.isAllowed("glossary")) {

        }
    }
})

const staticroutes = new pimcore.staticroutes();