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

        // add pricing config item to settings menu
//        pimcore.globalmanager.get("layout_toolbar").settingsMenu.items.each(function(item) {
//            if(item.iconCls == 'pimcore_icon_object') {
//                item.menu.add({
//                    text: t("plugin_online_shop_pricing"),
//                    iconCls: "plugin_online_shop_pricing",
//                    handler: function () {
//                        try {
//                            pimcore.globalmanager.get("plugin_online_shop_pricing_config").activate();
//                        }
//                        catch (e) {
//                            pimcore.globalmanager.add("plugin_online_shop_pricing_config", new pimcore.plugin.OnlineShop.pricing.config.panel());
//                        }
//
//                    }
//                });
//            }
//        });



        // submenü item
        var panelId = "plugin_onlineshop_pricing_config";
        var item = {
            text: t("plugin_onlineshop_pricing_config"),
            iconCls: "plugin_onlineshop_pricing_icon_config",
            handler: function () {
                try {
                    pimcore.globalmanager.get(panelId).activate();
                }
                catch (e) {
                    pimcore.globalmanager.add(panelId, new pimcore.plugin.OnlineShop.pricing.config.panel(panelId));
                }
            }
        }


        // search for ower menu
        var toolbar = Ext.getCmp("pimcore_panel_toolbar");
        var parentMenu = toolbar.find("id", "plugin_onlineshop_mainmenu");
        var menu;
        if(!(parentMenu && parentMenu[0]))
        {
            // create new
            var insertPoint = pimcore.globalmanager.get("user").isAllowed("seemode") ? toolbar.items.length-6 : toolbar.items.length-5;
            menu = new Ext.menu.Menu();

            toolbar.insert(insertPoint, {
                text: t('plugin_onlineshop_mainmenu'),
                cls: "pimcore_main_menu",
                iconCls: "plugin_onlineshop_icon_mainmenu",
                id: "plugin_onlineshop_mainmenu",
                menu: menu
            });
            pimcore.layout.refresh();
        }
        else
        {
            menu = parentMenu[0].menu;
        }


        // add to menu
        menu.addItem(item);

    }
});

new pimcore.plugin.OnlineShop.plugin();