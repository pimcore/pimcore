pimcore.registerNS("pimcore.simpleBackendSearch");

pimcore.simpleBackendSearch = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function(e) {
        new pimcore.simpleBackendSearch.layout.toolbar();
        new pimcore.simpleBackendSearch.layout.quickSearch();
    }
})

const simpleBackendSearch = new pimcore.simpleBackendSearch();