pimcore.registerNS("pimcore.plugin.OnlineShop.plugin");

pimcore.plugin.OnlineShop.plugin = Class.create(pimcore.plugin.admin,{

    getClassName: function (){
        return "pimcore.plugin.OnlineShop";
    },

    initialize: function(){
        pimcore.plugin.broker.registerPlugin(this);





    },

    uninstall: function(){
        //TODO remove from menu
    },

    pimcoreReady: function (params,broker) {

        var toolbar = pimcore.globalmanager.get("layout_toolbar");

        // init
        var menuItems = new Ext.menu.Menu({cls: "pimcore_navigation_flyout"});
        var user = pimcore.globalmanager.get("user");

        var searchButton = Ext.get("pimcore_menu_settings");

        if(user.isAllowed("plugin_onlineshop_pricing_rules")) {
            this.navEl = Ext.get(
                searchButton.insertHtml(
                    "afterEnd",
                    '<li id="pimcore_menu_onlineshop" class="pimcore_menu_item icon-basket">' + t('plugin_onlineshop_mainmenu') + '</li>'
                )
            );



            // add pricing rules to menu
            if(user.isAllowed("plugin_onlineshop_pricing_rules"))
            {
                // create item
                var panelId = "plugin_onlineshop_pricing_config";
                var item = {
                    text: t("plugin_onlineshop_pricing_rules"),
                    iconCls: "plugin_onlineshop_pricing_rules",
                    handler: function () {
                        try {
                            pimcore.globalmanager.get(panelId).activate();
                        }
                        catch (e) {
                            pimcore.globalmanager.add(panelId, new pimcore.plugin.OnlineShop.pricing.config.panel(panelId));
                        }
                    }
                }
                // add to menu
                menuItems.add(item);
            }


            // add onlineshop main menu
            if(menuItems.items.length > 0)
            {
                this.navEl.on("mousedown", toolbar.showSubMenu.bind(menuItems));
            }
        }
    }
});

new pimcore.plugin.OnlineShop.plugin();