pimcore.registerNS('pimcore.bundle.googleMarketing');

pimcore.bundle.googleMarketing = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (event) {
        var user = pimcore.globalmanager.get("user");
        var toolbar = Ext.getCmp("pimcore_panel_toolbar");
        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (perspectiveCfg.inToolbar('marketing') && user.isAllowed("reports") && user.isAllowed("system_settings")) {
            console.log(pimcore.globalmanager.get('layout.toolbar'));
        }
    }
});

var googleMarketingBundle = new pimcore.bundle.googleMarketing();