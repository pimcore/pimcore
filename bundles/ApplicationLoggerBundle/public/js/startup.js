pimcore.registerNS("pimcore.bundle.applicationlogger.startup");

pimcore.bundle.applicationlogger.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");
        let menu = e.detail.menu;

        if (user.isAllowed("application_logging")&& perspectiveCfg.inToolbar("extras.applicationlog")) {
            menu.extras.items.push({
                text: t("log_applicationlog"),
                iconCls: "pimcore_nav_icon_log_admin",
                itemId: 'pimcore_menu_extras_application_log',
                handler: this.logAdmin
            });
        }
    },

    logAdmin: function () {
        try {
            pimcore.globalmanager.get("pimcore_applicationlog_admin").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("pimcore_applicationlog_admin", new pimcore.bundle.applicationlogger.log.admin());
        }
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        if (user.isAllowed("application_logging")) {
            pimcore.helpers.keyBindingMapping.applicationLogger = function() {
                applicationLogger.logAdmin();
            }
        }
    }
})

const applicationLogger = new pimcore.bundle.applicationlogger.startup();