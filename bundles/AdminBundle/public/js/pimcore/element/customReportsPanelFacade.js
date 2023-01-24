pimcore.registerNS('pimcore.element.customReportsPanelFacade');

pimcore.element.customReportsPanelFacade = new Class.create({
    name: 'customReportsPanelImplementationFactory',
    className: null,

    initialize: function () {
        if(!pimcore.globalmanager.get(this.name)) {
            pimcore.globalmanager.add(this.name, this);
        }
    },

    getRegistry: function () {
        return pimcore.globalmanager.get(this.name);
    },

    getImplementation: function () {
        return this.getRegistry().className
    },

    hasImplementation: function () {
        return this.getImplementation() !== null;
    },

    registerImplementation: function (className) {
        this.getRegistry().className = className;
    },

    getNewReportInstance: function (type = null) {
        if(this.hasImplementation()){
            //call implementation
            try {
                reportClass = stringToFunction(this.className);
                return new reportClass(type);
            }
            catch (e) {
                console.log(e);
            }
        }
    }
});

const customReportsPanelFacade = new pimcore.element.customReportsPanelFacade();