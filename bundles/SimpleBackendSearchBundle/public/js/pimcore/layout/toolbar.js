pimcore.registerNS("pimcore.simpleBackendSearch.layout.toolbar");

pimcore.simpleBackendSearch.layout.toolbar = Class.create({
    initialize: function () {
        this.createToolbarEntry();
    },

    createToolbarEntry: function() {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get('perspective');

        if (perspectiveCfg.inToolbar("search")) {
            const searchItems = [];

            if ((user.isAllowed("documents") || user.isAllowed("assets") || user.isAllowed("objects")) && perspectiveCfg.inToolbar("search.quickSearch")) {
                searchItems.push({
                    text: t("quicksearch"),
                    iconCls: "pimcore_nav_icon_quicksearch",
                    itemId: 'pimcore_menu_search_quick_search',
                    handler: function () {
                        pimcore.simpleBackendSearch.layout.quickSearch.show();
                    }
                });
                searchItems.push('-');
            }

            if (user.isAllowed("documents") && perspectiveCfg.inToolbar("search.documents")) {
                searchItems.push({
                    text: t("documents"),
                    iconCls: "pimcore_nav_icon_document",
                    itemId: 'pimcore_menu_search_documents',
                    handler: this.searchAction.bind(this, "document")
                });
            }

            if (user.isAllowed("assets") && perspectiveCfg.inToolbar("search.assets")) {
                searchItems.push({
                    text: t("assets"),
                    iconCls: "pimcore_nav_icon_asset",
                    itemId: 'pimcore_menu_search_assets',
                    handler: this.searchAction.bind(this, "asset")
                });
            }

            if (user.isAllowed("objects") && perspectiveCfg.inToolbar("search.objects")) {
                searchItems.push({
                    text: t("data_objects"),
                    iconCls: "pimcore_nav_icon_object",
                    itemId: 'pimcore_menu_search_data_objects',
                    handler: this.searchAction.bind(this, "object")
                });
            }

            if(searchItems.length > 0){
                const toolbar = pimcore.globalmanager.get('layout_toolbar');

                toolbar.searchMenu = new Ext.menu.Menu({
                    items: searchItems,
                    shadow: false,
                    cls: "pimcore_navigation_flyout",
                    listeners: {
                        "show": function (e) {
                            Ext.get('pimcore_menu_search').addCls('active');
                        },
                        "hide": function (e) {
                            Ext.get('pimcore_menu_search').removeCls('active');
                        }
                    }
                });

                if (toolbar.searchMenu) {
                    let navEl = Ext.get("pimcore_menu_search");
                    navEl.show();
                    navEl.on("mousedown", toolbar.showSubMenu.bind(toolbar.searchMenu));
                }
            }
        }
    },

    searchAction: function (type) {
        pimcore.helpers.itemselector(false, function (selection) {
            pimcore.helpers.openElement(selection.id, selection.type, selection.subtype);
        },
        {type: [type]},
        {
            asTab: true,
            context: {
                scope: "globalSearch"
            }
        });
    }
});