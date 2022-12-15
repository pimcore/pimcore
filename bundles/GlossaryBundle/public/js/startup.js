pimcore.registerNS("pimcore.glossary");


pimcore.glossary = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        const toolbar = pimcore.globalmanager.get('layout_toolbar');

        if (user.isAllowed("glossary") && perspectiveCfg.inToolbar("extras.glossary")) {
            const index = 0;
            toolbar.extrasMenu.insert(index, {
                text: t("glossary"),
                iconCls: "pimcore_nav_icon_glossary",
                itemId: 'pimcore_menu_extras_glossary',
                handler: this.editGlossary,
            });
        }
    },

    editGlossary: function() {
        try {
            pimcore.globalmanager.get("glossary").activate();
        } catch (e) {
            pimcore.globalmanager.add("glossary", new pimcore.settings.glossary());
        }
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        if (user.isAllowed("glossary")) {
            pimcore.helpers.keyBindingMapping.glossary = function() {
                glossary.editGlossary();
            }
        }
    }
})

const glossary = new pimcore.glossary();


