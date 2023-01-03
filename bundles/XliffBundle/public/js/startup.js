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
pimcore.registerNS("pimcore.xliff");


pimcore.xliff = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {

        let menu = e.detail.menu;
        let that = this;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (user.isAllowed("translations") && perspectiveCfg.inToolbar("extras.translations")) {
            menu.extras.items.some(function(item, index) {
                if (item.itemId === 'pimcore_menu_extras_translations'){
                    menu.extras.items[index].menu.items.push({
                        text: "XLIFF " + t("export") + "/" + t("import"),
                        iconCls: "pimcore_nav_icon_translations",
                        itemId: 'pimcore_menu_extras_translations_xliff',
                        handler: that.xliffImportExport,
                        priority: 20,
                    });
                    return true;
                }
            });
        }
    },

    xliffImportExport: function() {
        try {
            pimcore.globalmanager.get("xliff").activate();
        } catch (e) {
            pimcore.globalmanager.add("xliff", new pimcore.settings.translation.xliff());
        }
    },
})

const xliff = new pimcore.xliff();