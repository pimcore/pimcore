pimcore.registerNS("pimcore.bundle.applicationLogger.startup");

pimcore.applicationLogger.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");
        var applicationLoggerMenu = [];

        if (user.isAllowed("application_logging")&& perspectiveCfg.inToolbar("extras.applicationlog")) {
            applicationLoggerMenu.push({
                text: t("log_applicationlog"),
                iconCls: "pimcore_nav_icon_log_admin",
                itemId: 'pimcore_menu_extras_application_log',
                handler: this.logAdmin
            });
        }

        if (applicationLoggerMenu.length > 0) {
            const toolbar = pimcore.globalmanager.get('layout_toolbar');

            toolbar.extrasMenu.add(applicationLoggerMenu);
        }
    },

    logAdmin: function () {
        try {
            pimcore.globalmanager.get("pimcore_applicationlog_admin").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("pimcore_applicationlog_admin", new pimcore.log.admin());
        }
    },
})

const applicationLogger = new pimcore.applicationLogger.startup();