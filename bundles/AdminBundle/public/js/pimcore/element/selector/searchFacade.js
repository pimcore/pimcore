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
        return this.getRegistry().searchClass
    },

    hasImplementation: function () {
        return this.getImplementation() !== undefined;
    },

    registerImplementation: function (searchClass) {
        this.getRegistry().searchClass = searchClass;
    },

    openItemSelector: function (multiselect, callback, restrictions, config) {
        if(this.hasImplementation()){
            //call implementation
            try {
                this.getImplementation().openItemSelector(multiselect, callback, restrictions, config); //TODO: define config object
            }
            catch (e) {
                //TODO: handle error
            }
        }
    }
});

const searchFacade = new pimcore.element.selector.searchFacade();
