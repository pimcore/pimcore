pimcore.registerNS('pimcore.bundle.SystemInfo');

pimcore.bundle.SystemInfo = Class.create({
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

        const menus = this.getSystemInfoMenu();

        if (menus.length > 0 && menu.extras) {
            menus.map(function(item) {
                menu.extras.items.push(item);
            });
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
        pimcore.helpers.openGenericIframeWindow("phpinfo", Routing.generate('pimcore_admin_misc_phpinfo'), "pimcore_icon_php", "PHP Info");
    },

    showOpcacheStatus: function () {
        pimcore.helpers.openGenericIframeWindow("opcachestatus", Routing.generate('pimcore_admin_external_opcache_index'), "pimcore_icon_reports", "PHP OPcache Status");
    },

    showSystemRequirementsCheck: function () {
        pimcore.helpers.openGenericIframeWindow("systemrequirementscheck", Routing.generate('pimcore_admin_install_check'), "pimcore_icon_systemrequirements", "System-Requirements Check");
    },

});

var systemInfoBundle = new pimcore.bundle.SystemInfo();