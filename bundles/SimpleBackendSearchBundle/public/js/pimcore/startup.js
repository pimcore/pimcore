pimcore.registerNS("pimcore.simpleBackendSearch");

pimcore.simpleBackendSearch = Class.create({


    initialize: function () {
        //TODO cfeldkirchner enable `registerKeyBinding` event once the new event is merged
        //document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function(e) {
        new pimcore.simpleBackendSearch.layout.toolbar();
        Ext.create('pimcore.simpleBackendSearch.layout.quickSearch');
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get('perspective');

        if ((user.isAllowed("documents") || user.isAllowed("assets") || user.isAllowed("objects")) && perspectiveCfg.inToolbar("search.quickSearch")) {
            pimcore.helpers.keyBindingMapping.quickSearch = function() {
                pimcore.simpleBackendSearch.layout.quickSearch.show();
            }
        }

        if (user.isAllowed("documents") && perspectiveCfg.inToolbar("search.documents")) {
            pimcore.helpers.keyBindingMapping.searchDocument = function () {
                //TODO
            }
        }

        if (user.isAllowed("assets") && perspectiveCfg.inToolbar("search.assets")) {
            pimcore.helpers.keyBindingMapping.searchAsset = function () {
                //TODO
            }
        }

        if (user.isAllowed("objects") && perspectiveCfg.inToolbar("search.objects")) {
            pimcore.helpers.keyBindingMapping.searchObject = function () {
                //TODO
            }
        }
    }
})

const simpleBackendSearch = new pimcore.simpleBackendSearch();