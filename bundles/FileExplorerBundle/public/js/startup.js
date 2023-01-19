pimcore.registerNS("pimcore.bundle.fileexplorer.startup");

pimcore.bundle.fileexplorer.startup = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (event) {
        const menu = event.detail.menu;
        this.user = pimcore.globalmanager.get('user');
        this.toolbar = pimcore.globalmanager.get('layout_toolbar');
        const systemInfoMenuItems = this.getSystemInfoMenu();

        const filteredMenu = menu.extras.items.filter(function (item) {
            return item.itemId === 'pimcore_menu_extras_system_info';
        });

        const systemInfoMenu = filteredMenu.shift();
        systemInfoMenuItems.map(function(item) {
            systemInfoMenu.menu.items.push(item);
        });

    },

    getSystemInfoMenu: function () {
        const items = [];

        const user = pimcore.globalmanager.get('user');
        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (
            (user.isAllowed('fileexplorer') || user.admin) &&
            perspectiveCfg.inToolbar("extras") &&
            perspectiveCfg.inToolbar("extras.systemtools") &&
            perspectiveCfg.inToolbar("extras.systemtools.fileexplorer")
        ) {
            items.push({
                text: t("pimcore_file_explorer_bundle_server_file_explorer"),
                iconCls: "pimcore_nav_icon_fileexplorer",
                itemId: 'pimcore_menu_extras_system_info_server_fileexplorer',
                handler: this.showFileExplorer,
                priority: 50
            });
        }

        return items;
    },

    showFileExplorer: function () {
        try {
            pimcore.globalmanager.get("bundle_file_explorer").activate();
        } catch (e) {
            pimcore.globalmanager.add("bundle_file_explorer", new pimcore.bundle.fileexplorer.explorer());
        }
    },
})

const fileexplorer = new pimcore.bundle.fileexplorer.startup();