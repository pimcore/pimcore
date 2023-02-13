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

pimcore.registerNS('pimcore.bundle.search.layout.toolbar');

/**
 * @private
 */
pimcore.bundle.search.layout.toolbar = Class.create({
    initialize: function (menu) {
        this.perspectiveCfg = pimcore.globalmanager.get('perspective');
        this.user = pimcore.globalmanager.get('user');
        this.searchRegistry = pimcore.globalmanager.get('searchImplementationRegistry');
        this.menu = menu;

        this.createSearchEntry();
    },

    createSearchEntry: function () {
        if (this.perspectiveCfg.inToolbar("search")) {
            const searchItems = [];

            if ((this.user.isAllowed("documents") ||
                this.user.isAllowed("assets") ||
                this.user.isAllowed("objects")) &&
                this.perspectiveCfg.inToolbar("search.quickSearch")) {
                searchItems.push({
                    text: t("quicksearch"),
                    iconCls: "pimcore_nav_icon_quicksearch",
                    itemId: 'pimcore_menu_search_quick_search',
                    handler: function () {
                        this.searchRegistry.showQuickSearch();
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
                    label: t('search'),
                    iconCls: 'pimcore_main_nav_icon_search',
                    items: searchItems,
                    shadow: false,
                    cls: "pimcore_navigation_flyout"
                };
            }
        }
    }
});