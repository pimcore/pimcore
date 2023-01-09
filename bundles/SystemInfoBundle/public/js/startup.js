pimcore.registerNS('pimcore.bundle.system_info.startup');

pimcore.bundle.system_info.startup = Class.create({
    user: null,
    toolbar: null,
    perspectiveConfig: null,

    initialize: function(){
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (event) {
        const menu = event.detail.menu;
        this.user = pimcore.globalmanager.get('user');
        this.toolbar = pimcore.globalmanager.get('layout_toolbar');
        this.perspectiveConfig = pimcore.globalmanager.get('perspective');
        const systemInfoMenuItems = this.getSystemInfoMenu();

        const filteredMenu = menu.extras.items.filter(function (item) {
            return item.itemId === 'pimcore_menu_extras_system_info';
        });

        if (filteredMenu.length > 0) {
            const systemInfoMenu = filteredMenu.shift();
            systemInfoMenuItems.map(function(item) {
                systemInfoMenu.menu.items.push(item);
            });
        } else {
            menu.extras.items.push({
                text: t("system_infos_and_tools"),
                iconCls: "pimcore_nav_icon_info",
                hideOnClick: false,
                itemId: 'pimcore_menu_extras_system_info',
                menu: {
                    cls: "pimcore_navigation_flyout",
                    shadow: false,
                    items: systemInfoMenuItems
                }
            })
        }
    },

    getSystemInfoMenu: function () {
        const items = [];

        if (this.user.admin && this.perspectiveConfig.inToolbar('extras.systemtools')) {

            if (this.perspectiveConfig.inToolbar('extras.systemtools.phpinfo')) {
                items.push(
                    {
                        text: t('php_info'),
                        iconCls: 'pimcore_nav_icon_php',
                        itemId: 'pimcore_menu_extras_system_info_php_info',
                        handler: this.showPhpInfo
                    }
                );
            }

            if (this.perspectiveConfig.inToolbar('extras.systemtools.opcache')) {
                items.push(
                    {
                        text: t('php_opcache_status'),
                        iconCls: 'pimcore_nav_icon_reports',
                        itemId: 'pimcore_menu_extras_system_info_php_opcache_status',
                        handler: this.showOpcacheStatus
                    }
                );
            }

            if (this.perspectiveConfig.inToolbar('extras.systemtools.requirements')) {
                items.push(
                    {
                        text: t('system_requirements_check'),
                        iconCls: 'pimcore_nav_icon_systemrequirements',
                        itemId: 'pimcore_menu_extras_system_info_system_requirements_check',
                        handler: this.showSystemRequirementsCheck
                    }
                );
            }
        }

        return items;
    },

    showPhpInfo: function () {
        pimcore.helpers.openGenericIframeWindow("phpinfo", Routing.generate('pimcore_bundle_system_info_settings_phpinfo'), "pimcore_icon_php", "PHP Info");
    },

    showOpcacheStatus: function () {
        pimcore.helpers.openGenericIframeWindow("opcachestatus", Routing.generate('pimcore_bundle_system_info_settings_opcache_index'), "pimcore_icon_reports", "PHP OPcache Status");
    },

    showSystemRequirementsCheck: function () {
        pimcore.helpers.openGenericIframeWindow("systemrequirementscheck", Routing.generate('pimcore_bundle_system_info_settings_install_checks'), "pimcore_icon_systemrequirements", "System-Requirements Check");
    },

});

var bundle_system_info = new pimcore.bundle.system_info.startup();