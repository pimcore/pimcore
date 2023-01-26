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

pimcore.registerNS("pimcore.bundle.staticroutes.startup");
/**
 * @private
 */
pimcore.bundle.staticroutes.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (user.isAllowed("routes") && perspectiveCfg.inToolbar("settings.routes")) {
            menu.settings.items.push({
                text: t("static_routes"),
                iconCls: "pimcore_nav_icon_routes",
                priority: 95,
                itemId: 'pimcore_menu_settings_static_routes',
                handler: this.editRoutes
            });
        }
    },

    editRoutes: function () {

        try {
            pimcore.globalmanager.get("bundle_staticroutes").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_staticroutes", new pimcore.bundle.staticroutes.settings());
        }
    }
})

const pimcoreBundleStaticroutes = new pimcore.bundle.staticroutes.startup();