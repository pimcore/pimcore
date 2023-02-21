pimcore.registerNS('pimcore.element.selector.searchFacade');

pimcore.element.selector.searchFacade = new Class.create({
    name: 'searchImplementationRegistry',
    searchClass: null,

    initialize: function () {
        if(!pimcore.globalmanager.get(this.name)) {
            pimcore.globalmanager.add(this.name, this);
        }
    },

    getRegistry: function () {
        return pimcore.globalmanager.get(this.name);
    },

    getImplementation: function () {
        return this.getRegistry().searchClass;
    },

    hasImplementation: function () {
        return this.getImplementation() !== null;
    },

    registerImplementation: function (searchClass) {
        this.getRegistry().searchClass = searchClass;
    },

    openItemSelector: function (multiselect, callback, restrictions, config) {
        if(this.hasImplementation()) {
            this.getImplementation().openItemSelector(multiselect, callback, restrictions, config);
        }
    },

    showQuickSearch: function () {
        if(this.hasImplementation()){
            this.getImplementation().showQuickSearch();
        }
    },

    hideQuickSearch: function () {
        if(this.hasImplementation()){
            this.getImplementation().hideQuickSearch();
        }
    },

    getObjectRelationInlineSearchRoute: function () {
        if(this.hasImplementation()) {
            return this.getImplementation().getObjectRelationInlineSearchRoute();
        }
        return null;
    }
});

const searchFacade = new pimcore.element.selector.searchFacade();
