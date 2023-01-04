pimcore.registerNS('pimcore.bundle.search.service');

pimcore.bundle.search.service = Class.create({
   registry: null,

    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
   },

    pimcoreReady: function () {
        this.registry = pimcore.globalmanager.get('searchImplementationRegistry')

        //register search/selector
        this.registry.registerImplementation(new pimcore.bundle.search.element.selector());
    }
});

const pimcoreSearchService = new pimcore.bundle.search.service();