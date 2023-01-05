pimcore.registerNS('pimcore.bundle.search');

pimcore.bundle.search = Class.create({
    registry: null,

    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function () {
        this.registerSearchService();
    },

    registerKeyBinding: function () {
        //TODO: implement
    },

    registerSearchService: function () {
        this.registry = pimcore.globalmanager.get('searchImplementationRegistry');

        //register search/selector
        this.registry.registerImplementation(new pimcore.bundle.search.element.service());
    },

    preMenuBuild: function () {
        //TODO: implement

        //TODO: add navbar items
    }
});

const searchBundle = new pimcore.bundle.search();