/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


pimcore.registerNS("pimcore.plugin.OnlineShop.plugin");

pimcore.plugin.OnlineShop.plugin = Class.create(pimcore.plugin.admin,{

    menuItems: null,

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
        var menuItems = toolbar.ecommerceMenu;
        if(!menuItems) {
            menuItems = new Ext.menu.Menu({cls: "pimcore_navigation_flyout"});
            toolbar.ecommerceMenu = menuItems;
        }
        var user = pimcore.globalmanager.get("user");

        var searchButton = Ext.get("pimcore_menu_settings");



        // pricing rules
        if(user.isAllowed("plugin_onlineshop_pricing_rules")) {
            // add pricing rules to menu
            // create item
            var pricingPanelId = "plugin_onlineshop_pricing_config";
            var item = {
                text: t("plugin_onlineshop_pricing_rules"),
                iconCls: "plugin_onlineshop_pricing_rules",
                handler: function () {
                    try {
                        pimcore.globalmanager.get(pricingPanelId).activate();
                    }
                    catch (e) {
                        pimcore.globalmanager.add(pricingPanelId, new pimcore.plugin.OnlineShop.pricing.config.panel(pricingPanelId));
                    }
                }
            }
            // add to menu
            menuItems.add(item);
        }


        // order backend
        if(user.isAllowed("plugin_onlineshop_back-office_order")) {
            // create item
            var orderPanelId = "plugin_onlineshop_back-office_order";
            var item = {
                text: t("plugin_onlineshop_back-office_order"),
                iconCls: "plugin_onlineshop_back-office_order",
                handler: function () {
                    try {
                        pimcore.globalmanager.get(orderPanelId).activate();
                    }
                    catch (e) {
                        pimcore.globalmanager.add(orderPanelId, new pimcore.tool.genericiframewindow(orderPanelId, "/plugin/OnlineShop/admin-order/list", "plugin_onlineshop_back-office_order", t('plugin_onlineshop_back-office_order')));
                    }
                }
            };

            // add to menu
            menuItems.add(item);
        }




        if(user.admin) {

            var item = {
                text: t("plugin_onlineshop_clear_config_cache"),
                iconCls: "plugin_onlineshop_clear_config_cache",
                handler: function () {
                    Ext.Ajax.request({
                        url: '/plugin/OnlineShop/admin/clear-cache'
                    });
                }
            }
            // add to menu
            menuItems.add(item);
        }

        // add onlineshop main menu
        if(menuItems.items.length > 0)
        {
            this.navEl = Ext.get(
                searchButton.insertHtml(
                    "afterEnd",
                    '<li id="pimcore_menu_onlineshop" class="pimcore_menu_item icon-basket">' + t('plugin_onlineshop_mainmenu') + '</li>'
                )
            );

            this.navEl.on("mousedown", toolbar.showSubMenu.bind(menuItems));
        }

    },


    postOpenObject: function (object, type) {
        if (pimcore.globalmanager.get("user").isAllowed("plugin_onlineshop_pricing_rules")) {

            if (type == "object" && object.data.general.o_className == "OnlineShopVoucherSeries") {
                var tab = new pimcore.plugin.onlineshop.VoucherSeriesTab(object, type);

                object.tab.items.items[1].insert(1, tab.getLayout());
                object.tab.items.items[1].doLayout();
                pimcore.layout.refresh();
            }

        }
    }

});

new pimcore.plugin.OnlineShop.plugin();