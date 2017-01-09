/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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

        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        if(true || perspectiveCfg.inToolbar("ecommerce")) {

            // init
            var menuItems = toolbar.ecommerceMenu;
            if (!menuItems) {
                menuItems = new Ext.menu.Menu({cls: "pimcore_navigation_flyout"});
                toolbar.ecommerceMenu = menuItems;
            }
            var user = pimcore.globalmanager.get("user");

            var insertPoint = Ext.get("pimcore_menu_settings");
            if(!insertPoint) {
                var dom = Ext.dom.Query.select('#pimcore_navigation ul li:last');
                insertPoint = Ext.get(dom[0]);
            }

            var config = pimcore.plugin.OnlineShop.plugin.config;

            // pricing rules
            if (perspectiveCfg.inToolbar("ecommerce.rules") && user.isAllowed("plugin_onlineshop_pricing_rules") && (!config.menu || config.menu.pricingRules.disabled == 0)) {
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
                };

                // add to menu
                menuItems.add(item);
            }


            // order backend
            if (perspectiveCfg.inToolbar("ecommerce.orderbackend") && user.isAllowed("plugin_onlineshop_back-office_order") && (!config.menu || config.menu.orderlist.disabled == 0)) {
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
                            pimcore.globalmanager.add(orderPanelId, new pimcore.tool.genericiframewindow(orderPanelId, config.menu.orderlist.route, "plugin_onlineshop_back-office_order", t('plugin_onlineshop_back-office_order')));
                        }
                    }
                };

                // add to menu
                menuItems.add(item);
            }

            // add onlineshop main menu
            if (menuItems.items.length > 0) {
                this.navEl = Ext.get(
                    insertPoint.insertHtml(
                        "afterEnd",
                        '<li id="pimcore_menu_onlineshop" class="pimcore_menu_item icon-basket" data-menu-tooltip="' + t('plugin_onlineshop_mainmenu') + '"></li>'
                    )
                );

                this.navEl.on("mousedown", toolbar.showSubMenu.bind(menuItems));
                pimcore.helpers.initMenuTooltips();
            }
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