pimcore.registerNS('pimcore.registry.searchImplementationRegistry');

pimcore.registry.searchImplementationRegistry = new Class.create({
    name: 'searchImplementationRegistry',
    searchClass: null,

    initialize: function () {
        try {
            pimcore.globalmanager.get(this.name);
        }
        catch (e) {
            pimcore.globalmanager.add(this.name, this);
        }
    },

    getImplementation: function () {
        return pimcore.globalmanager.get(this.name);
    },

    hasImplementation: function () {
        return this.getImplementation().searchClass !== undefined;
    },

    registerImplementation: function (searchClass) {
        this.getImplementation().searchClass = searchClass;
    },

    openItemSelector: function () {
        if(this.hasImplementation()){
            const implementationClass = this.getImplementation().searchClass;

            //call implementation
            try {
                implementationClass.openItemSelector({}); //TODO: define config object
            }
            catch (e) {
                //TODO: handle error
            }
        }
    }
});

const searchRegistry = new pimcore.registry.searchImplementationRegistry();
