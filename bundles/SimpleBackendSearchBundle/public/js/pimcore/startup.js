pimcore.registerNS('pimcore.bundle.search');

pimcore.bundle.search = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    registerKeyBinding: function () {
        //TODO: implement
    },

    preMenuBuild: function () {
        //TODO: implement
    }
});

const searchBundle = new pimcore.bundle.search();