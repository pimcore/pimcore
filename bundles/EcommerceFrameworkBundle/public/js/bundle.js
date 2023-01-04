/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


document.addEventListener(pimcore.events.pimcoreReady, (e) => {
    const perspectiveCfg = pimcore.globalmanager.get("perspective");

    if (!perspectiveCfg.inToolbar("ecommerce")) {
        return
    }

    const toolbar = pimcore.globalmanager.get("layout_toolbar");

    // init
    let menuItems = toolbar.ecommerceMenu;
    if (!menuItems) {
        menuItems = new Ext.menu.Menu({
            cls: "pimcore_navigation_flyout",
            listeners: {
                "show": function (e) {
                    Ext.get('pimcore_menu_ecommerce').addCls('active');
                },
                "hide": function (e) {
                    Ext.get('pimcore_menu_ecommerce').removeCls('active');
                }
            }
        });
        toolbar.ecommerceMenu = menuItems;
    }

    const user = pimcore.globalmanager.get("user");

    let config = pimcore.bundle.EcommerceFramework.config;

    // pricing rules
    if (perspectiveCfg.inToolbar("ecommerce.rules") && user.isAllowed("bundle_ecommerce_pricing_rules") && (!config.menu || config.menu.pricing_rules.enabled)) {
        // add pricing rules to menu
        // create item
        const pricingPanelId = "bundle_ecommerce_pricing_config";
        const item = {
            text: t("bundle_ecommerce_pricing_rules"),
            iconCls: "pimcore_nav_icon_commerce_pricing_rules",
            handler: function () {
                try {
                    pimcore.globalmanager.get(pricingPanelId).activate();
                }
                catch (e) {
                    pimcore.globalmanager.add(pricingPanelId, new pimcore.bundle.EcommerceFramework.pricing.config.panel(pricingPanelId));
                }
            }
        };

        // add to menu
        menuItems.add(item);
    }

    // order backend
    if (perspectiveCfg.inToolbar("ecommerce.orderbackend") && user.isAllowed("bundle_ecommerce_back-office_order") && (!config.menu || config.menu.order_list.enabled)) {
        // create item
        const orderPanelId = "bundle_ecommerce_back-office_order";
        const item = {
            text: t("bundle_ecommerce_back-office_order"),
            iconCls: "pimcore_nav_icon_commerce_backoffice",
            handler: function () {
                try {
                    pimcore.globalmanager.get(orderPanelId).activate();
                }
                catch (e) {
                    pimcore.globalmanager.add(orderPanelId, new pimcore.tool.genericiframewindow(orderPanelId, config.menu.order_list.route, "bundle_ecommerce_back-office_order", t('bundle_ecommerce_back-office_order')));
                }
            }
        };

        // add to menu
        menuItems.add(item);
    }

    if (menuItems.items.length > 0) {
        initializeMenu(toolbar, menuItems);
    }
});

function initializeMenu(toolbar, menuItems) {
    // add e-commerce framework main menu
    let navEl = Ext.get('pimcore_menu_ecommerce');
    navEl.show();
    navEl.on("mousedown", toolbar.showSubMenu.bind(menuItems));

    pimcore.helpers.initMenuTooltips();
}

document.addEventListener(pimcore.events.postOpenObject, (e) => {
    if (pimcore.globalmanager.get("user").isAllowed("bundle_ecommerce_pricing_rules")) {

        if (e.detail.type == "object" && e.detail.object.data.general.className == "OnlineShopVoucherSeries") {
            const tab = new pimcore.bundle.EcommerceFramework.VoucherSeriesTab(e.detail.object, e.detail.type);

            e.detail.object.tab.items.items[1].insert(1, tab.getLayout());
            e.detail.object.tab.items.items[1].updateLayout();
            pimcore.layout.refresh();
        }
    }
    if (pimcore.globalmanager.get("user").isAllowed("bundle_ecommerce_back-office_order")) {

        if (e.detail.type == "object" && e.detail.object.data.general.className == "OnlineShopOrder") {
            const tab = new pimcore.bundle.EcommerceFramework.OrderTab(e.detail.object, e.detail.type);
            e.detail.object.tab.items.items[1].insert(0, tab.getLayout());
            e.detail.object.tab.items.items[1].updateLayout();
            e.detail.object.tab.items.items[1].setActiveTab(0);
            pimcore.layout.refresh();
        }
    }
});
