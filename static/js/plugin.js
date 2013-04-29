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

        // init
        var menuItems = [];
        var user = pimcore.globalmanager.get("user");


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
            menuItems.push(item);
        }


        // add onlineshop main menu
        if(menuItems.length > 0)
        {
            var toolbar = Ext.getCmp("pimcore_panel_toolbar");
            var insertPoint = pimcore.globalmanager.get("user").isAllowed("seemode") ? toolbar.items.length-6 : toolbar.items.length-5;
            toolbar.insert(insertPoint, {
                text: t('plugin_onlineshop_mainmenu'),
                cls: "pimcore_main_menu",
                iconCls: "plugin_onlineshop_icon_mainmenu",
                id: "plugin_onlineshop_mainmenu",
                menu: new Ext.menu.Menu({
                    items: menuItems
                })
            });
            pimcore.layout.refresh();
        }
    }
});

new pimcore.plugin.OnlineShop.plugin();