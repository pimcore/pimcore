pimcore.registerNS('pimcore.bundle.search.layout.toolbar');

pimcore.bundle.search.layout.toolbar = Class.create({

    initialize: function (menu) {
        this.perspectiveCfg = pimcore.globalmanager.get("perspective");
        this.user = pimcore.globalmanager.get("user");
        this.quickSearch = new pimcore.bundle.search.layout.quickSearch();
        this.menu = menu;

        this.createSearchEntry();
    },

    createSearchEntry: function () {
        if (this.perspectiveCfg.inToolbar("search")) {
            const searchItems = [];

            if ((this.user.isAllowed("documents") || this.user.isAllowed("assets") || this.user.isAllowed("objects")) && this.perspectiveCfg.inToolbar("search.quickSearch")) {
                searchItems.push({
                    text: t("quicksearch"),
                    iconCls: "pimcore_nav_icon_quicksearch",
                    itemId: 'pimcore_menu_search_quick_search',
                    handler: function () {
                        this.quickSearch.show();
                    }.bind(this)
                });
                searchItems.push('-');
            }

            const searchAction = function (type) {
                pimcore.globalmanager.get('searchImplementationRegistry').openItemSelector(
                    false,
                    function (selection) {
                        pimcore.helpers.openElement(selection.id, selection.type, selection.subtype);
                    },
                    {type: [type]},
                    {
                        asTab: true,
                        context: {
                            scope: "globalSearch"
                        }
                    }
                );
            };

            if (this.user.isAllowed("documents") && this.perspectiveCfg.inToolbar("search.documents")) {
                searchItems.push({
                    text: t("documents"),
                    iconCls: "pimcore_nav_icon_document",
                    itemId: 'pimcore_menu_search_documents',
                    handler: searchAction.bind(this, "document")
                });
            }

            if (this.user.isAllowed("assets") && this.perspectiveCfg.inToolbar("search.assets")) {
                searchItems.push({
                    text: t("assets"),
                    iconCls: "pimcore_nav_icon_asset",
                    itemId: 'pimcore_menu_search_assets',
                    handler: searchAction.bind(this, "asset")
                });
            }

            if (this.user.isAllowed("objects") && this.perspectiveCfg.inToolbar("search.objects")) {
                searchItems.push({
                    text: t("data_objects"),
                    iconCls: "pimcore_nav_icon_object",
                    itemId: 'pimcore_menu_search_data_objects',
                    handler: searchAction.bind(this, "object")
                });
            }

            if (searchItems.length > 0) {
                this.menu.search = {
                    items: searchItems,
                    shadow: false,
                    listeners: true,
                    cls: "pimcore_navigation_flyout"
                };
            }
        }
    }
});