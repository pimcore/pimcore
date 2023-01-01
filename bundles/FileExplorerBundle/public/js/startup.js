pimcore.registerNS("pimcore.settings.fileexplorer");

pimcore.settings.fileexplorer = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },


    pimcoreReady: function(e) {
        const user = pimcore.globalmanager.get('user');
        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        var toolbar = pimcore.globalmanager.get('layout_toolbar');

        if (
            user.admin &&
            perspectiveCfg.inToolbar("extras") &&
            perspectiveCfg.inToolbar("extras.systemtools") &&
            perspectiveCfg.inToolbar("extras.systemtools.fileexplorer")
        ) {
            const index = toolbar.extrasMenu.items.keys.indexOf('pimcore_menu_extras_system_info');
            const systemInfoMenu  = toolbar.extrasMenu.items.items[index];

            systemInfoMenu.getMenu().add(
                {
                    text: t("server_fileexplorer"),
                    iconCls: "pimcore_nav_icon_fileexplorer",
                    itemId: 'pimcore_menu_extras_system_info_server_fileexplorer',
                    handler: this.showFilexplorer
                }
            )
        }
    },

    showFilexplorer: function () {
        try {
            pimcore.globalmanager.get("fileexplorer").activate();
        } catch (e) {
            pimcore.globalmanager.add("fileexplorer", new pimcore.fileexplorer.explorer());
        }
    },
})

const fileexplorer = new pimcore.settings.fileexplorer();