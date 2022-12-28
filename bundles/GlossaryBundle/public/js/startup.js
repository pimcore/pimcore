pimcore.registerNS("pimcore.glossary");


pimcore.glossary = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (menu.extras && user.isAllowed("glossary") && perspectiveCfg.inToolbar("extras.glossary")) {
            menu.extras.items.push({
                text: t("glossary"),
                iconCls: "pimcore_nav_icon_glossary",
                priority: 5,
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


