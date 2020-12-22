/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


pimcore.registerNS("pimcore.bundle.EcommerceFramework.bundle");

pimcore.bundle.EcommerceFramework.bundle = Class.create(pimcore.plugin.admin, {

    menuItems: null,

    menuInitialized: false,

    getClassName: function () {
        return "pimcore.bundle.EcommerceFramework.bundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    uninstall: function () {
    },

    initializeMenu: function (toolbar, menuItems) {
        if (this.menuInitialized) {
            return;
        }

        // add e-commerce framework main menu
        this.navEl = Ext.get('pimcore_menu_ecommerce');
        this.navEl.show();
        this.navEl.on("mousedown", toolbar.showSubMenu.bind(menuItems));

        pimcore.helpers.initMenuTooltips();

        this.menuInitialized = true;
    },

    pimcoreReady: function (params, broker) {
        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (!perspectiveCfg.inToolbar("ecommerce")) {
            return
        }

        var toolbar = pimcore.globalmanager.get("layout_toolbar");

        // init
        var menuItems = toolbar.ecommerceMenu;
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

        var user = pimcore.globalmanager.get("user");

        var config = pimcore.bundle.EcommerceFramework.bundle.config;

        // pricing rules
        if (perspectiveCfg.inToolbar("ecommerce.rules") && user.isAllowed("bundle_ecommerce_pricing_rules") && (!config.menu || config.menu.pricing_rules.enabled)) {
            // add pricing rules to menu
            // create item
            var pricingPanelId = "bundle_ecommerce_pricing_config";
            var item = {
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
            var orderPanelId = "bundle_ecommerce_back-office_order";
            var item = {
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

        if (user.isAllowed('piwik_reports')) {
            this.loadReportItems(toolbar, menuItems);
        }

        if (menuItems.items.length > 0) {
            this.initializeMenu(toolbar, menuItems);
        }
    },

    loadReportItems: function (toolbar, menuItems) {
        var that = this;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_ecommerceframework_reports_piwik_reports'),
            ignoreErrors: true,
            success: function (response) {
                var json;

                try {
                    json = Ext.decode(response.responseText);

                    if (!json.data) {
                        return;
                    }
                } catch (e) {
                    console.error(e);
                    return;
                }

                var reportItems = [];
                Ext.Array.each(json.data, function (siteConfig) {
                    if (reportItems.length > 0) {
                        reportItems.push(new Ext.menu.Separator({}));
                    }

                    var title = '';
                    if ('default' !== siteConfig.id) {
                        title = siteConfig.title + ' - ';
                    }

                    Ext.Array.each(siteConfig.entries, function (entry) {
                        reportItems.push({
                            text: title + entry.title,
                            iconCls: 'pimcore_icon_reports',
                            handler: function () {
                                pimcore.helpers.openGenericIframeWindow(
                                    ['ecommerce', siteConfig.id, entry.id].join('-'),
                                    entry.url,
                                    'pimcore_icon_reports',
                                    title + entry.fullTitle
                                );
                            }
                        });
                    });
                });

                if (reportItems.length > 0) {
                    menuItems.add({
                        text: t('reports'),
                        iconCls: "pimcore_icon_reports",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: reportItems
                        }
                    });

                    that.initializeMenu(toolbar, menuItems);
                }
            }
        });
    },

    postOpenObject: function (object, type) {
        if (pimcore.globalmanager.get("user").isAllowed("bundle_ecommerce_pricing_rules")) {

            if (type == "object" && object.data.general.o_className == "OnlineShopVoucherSeries") {
                var tab = new pimcore.bundle.EcommerceFramework.VoucherSeriesTab(object, type);

                object.tab.items.items[1].insert(1, tab.getLayout());
                object.tab.items.items[1].updateLayout();
                pimcore.layout.refresh();
            }
        }
        if (pimcore.globalmanager.get("user").isAllowed("bundle_ecommerce_back-office_order")) {

            if (type == "object" && object.data.general.o_className == "OnlineShopOrder") {
                var tab = new pimcore.bundle.EcommerceFramework.OrderTab(object, type);
                object.tab.items.items[1].insert(0, tab.getLayout());
                object.tab.items.items[1].updateLayout();
                object.tab.items.items[1].setActiveTab(0);
                pimcore.layout.refresh();
            }
        }
    }

});

new pimcore.bundle.EcommerceFramework.bundle();
