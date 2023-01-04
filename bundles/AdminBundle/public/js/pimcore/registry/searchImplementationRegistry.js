pimcore.registerNS('pimcore.registry.searchImplementationRegistry');

pimcore.registry.searchImplementationRegistry = new Class.create({
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

    openItemSelector: function () {
        if(this.hasImplementation()){
            //call implementation
            try {
                this.getImplementation().openItemSelector({}); //TODO: define config object
            }
            catch (e) {
                //TODO: handle error
            }
        }
    }
});

const searchRegistry = new pimcore.registry.searchImplementationRegistry();
