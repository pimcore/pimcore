pimcore.registerNS('pimcore.bundle.SystemInfo');

pimcore.bundle.SystemInfo = Class.create({
    user: null,
    toolbar: null,
    perspectiveConfig: null,
    initialize: function(){
        document.addEventListener(pimcore.events.pimcoreReady, this.onReady.bind(this));
    },

    onReady: function () {
        this.user = pimcore.globalmanager.get('user');
        this.toolbar = pimcore.globalmanager.get('layout_toolbar');
        this.perspectiveConfig = pimcore.globalmanager.get('perspective');

        this.addSystemInfoMenu();
    },

    addSystemInfoMenu: function () {
        if (this.user.admin && this.perspectiveConfig.inToolbar('extras.systemtools')) {
            var items = [];

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

            let extrasMenu = pimcore.globalmanager.get('toolbar.extrasMenu');

            let systemInfoMenu = extrasMenu.items.items[extrasMenu.items.indexMap['pimcore_menu_extras_system_info']];

            items.map(function(item) {
                systemInfoMenu.menu.add(item);
            });
        }
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