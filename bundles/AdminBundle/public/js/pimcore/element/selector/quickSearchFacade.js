pimcore.registerNS('pimcore.element.selector.quickSearchFacade');

pimcore.element.selector.quickSearchFacade = new Class.create({
    name: 'quickSearchImplementationRegistry',
    quickSearchClass: null,

    initialize: function () {
        if(!pimcore.globalmanager.get(this.name)) {
            pimcore.globalmanager.add(this.name, this);
        }
    },

    getRegistry: function () {
        return pimcore.globalmanager.get(this.name);
    },

    getImplementation: function () {
        return this.getRegistry().quickSearchClass
    },

    hasImplementation: function () {
        return this.getImplementation() !== null;
    },

    registerImplementation: function (quickSearchClass) {
        this.getRegistry().quickSearchClass = quickSearchClass;
    },
    
    show: function () {
        if(this.hasImplementation()){
            this.getImplementation().show();
        }
    },
    
    hide: function () {
        if(this.hasImplementation()){
            this.getImplementation().hide();
        }
    }
});

const quickSearchFacade = new pimcore.element.selector.quickSearchFacade();