pimcore.registerNS("pimcore.wordexport");


pimcore.wordexport = Class.create({
    initialize: function () {
        //document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        const toolbar = pimcore.globalmanager.get('layout_toolbar');

        if (user.isAllowed("translations") && perspectiveCfg.inToolbar("extras.translations")) {
            let index = toolbar.extrasMenu.items.keys.indexOf('pimcore_menu_extras_translations');
            toolbar.extrasMenu.items.items[index].menu.add({
                text: "Microsoft® Word " + t("export"),
                iconCls: "pimcore_nav_icon_word_export",
                itemId: 'pimcore_menu_extras_translations_word_export',
                handler: this.wordExport
            });


        }
    },

    wordExport: function () {
        try {
            pimcore.globalmanager.get("word").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("word", new pimcore.settings.translation.word());
        }
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        if (user.isAllowed("glossary")) {

        }
    }
})

const wordexport = new pimcore.wordexport();