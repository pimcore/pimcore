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
        pimcore.helpers.keyBindingMapping.quickSearch = function () {
            pimcore.globalmanager.get('quickSearchImplementationRegistry').show();
        }
    },

    registerSearchService: function () {
        this.searchRegistry = pimcore.globalmanager.get('searchImplementationRegistry');

        //register search/selector
        this.searchRegistry.registerImplementation(new pimcore.bundle.search.element.service());
    },

    registerQuickSearchService: function () {
        this.quickSearchRegistry = pimcore.globalmanager.get('quickSearchImplementationRegistry');

        //register quickSearch
        this.quickSearchRegistry.registerImplementation(new pimcore.bundle.search.layout.quickSearch());
    },

    preMenuBuild: function (event) {
        this.registerQuickSearchService();

        new pimcore.bundle.search.layout.toolbar(event.detail.menu);
    }
});

const searchBundle = new pimcore.bundle.search();