pimcore.registerNS('pimcore.bundle.system_info.startup');

pimcore.bundle.system_info.startup = Class.create({
    user: null,
    toolbar: null,
    perspectiveConfig: null,

    initialize: function(){
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (event) {
        let menu = event.detail.menu;

        this.addSystemInfoMenu(menu);
    },

    addSystemInfoMenu: function (menu) {
        let that = this;
        const items = [];
        const user = pimcore.globalmanager.get('user');
        const perspectiveConfig = pimcore.globalmanager.get("perspective");

        if (user.admin && perspectiveConfig.inToolbar('extras.systemtools')) {
            menu.extras.items.some(function(item, index) {
                if (item.itemId === 'pimcore_menu_extras_system_info') {
                    if (perspectiveConfig.inToolbar('extras.systemtools.phpinfo')) {
                        menu.extras.items[index].menu.items.push({
                            text: t('bundle_systemInfo_php_info'),
                            iconCls: 'pimcore_nav_icon_php',
                            itemId: 'pimcore_menu_extras_system_info_php_info',
                            handler: that.showPhpInfo,
                            priority: 10,
                        });
                    }

                    if (perspectiveConfig.inToolbar('extras.systemtools.opcache')) {
                        menu.extras.items[index].menu.items.push({
                            text: t('bundle_systemInfo_php_opcache_status'),
                            iconCls: 'pimcore_nav_icon_reports',
                            itemId: 'pimcore_menu_extras_system_info_php_opcache_status',
                            handler: that.showOpcacheStatus,
                            priority: 20,
                        });
                    }
                }
            });
        }

        return items;
    },

    showPhpInfo: function () {
        pimcore.helpers.openGenericIframeWindow("phpinfo", Routing.generate('pimcore_bundle_systeminfo_settings_phpinfo'), "pimcore_icon_php", "PHP Info");
    },

    showOpcacheStatus: function () {
        pimcore.helpers.openGenericIframeWindow("opcachestatus", Routing.generate('pimcore_bundle_systeminfo_opcache_index'), "pimcore_icon_reports", "PHP OPcache Status");
    },
});

var bundle_system_info = new pimcore.bundle.system_info.startup();