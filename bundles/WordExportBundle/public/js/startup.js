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

pimcore.registerNS("pimcore.bundle.wordexport.startup");
/**
 * @private
 */
pimcore.bundle.wordexport.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {

        let menu = e.detail.menu;
        let that = this;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");
        if (user.isAllowed("word_export") && perspectiveCfg.inToolbar("extras.word_export")) {
            menu.extras.items.some(function(item, index) {
                if (item.itemId === 'pimcore_menu_extras_translations'){
                    menu.extras.items[index].menu.items.push({
                        text: "Microsoft® Word " + t("export"),
                        iconCls: "pimcore_nav_icon_word_export",
                        priority: 25,
                        itemId: 'pimcore_menu_extras_translations_word_export',
                        handler: that.wordExport
                    });
                    return true;
                }
            });
        }
    },

    wordExport: function () {
        try {
            pimcore.globalmanager.get("bundle_word_export").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_word_export", new pimcore.bundle.wordexport.settings());
        }
    }
})

const pimcoreBundleWordExport = new pimcore.bundle.wordexport.startup();