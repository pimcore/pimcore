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

pimcore.registerNS("pimcore.bundle.glossary.startup");
/**
 * @private
 */
pimcore.bundle.glossary.startup = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (menu.extras && user.isAllowed("glossary") && perspectiveCfg.inToolbar("extras.glossary")) {
            menu.extras.items.push({
                text: t("glossary"),
                iconCls: "pimcore_nav_icon_glossary",
                priority: 5,
                itemId: 'pimcore_menu_extras_glossary',
                handler: this.editGlossary,
            });
        }
    },

    editGlossary: function() {
        try {
            pimcore.globalmanager.get("bundle_glossary").activate();
        } catch (e) {
            pimcore.globalmanager.add("bundle_glossary", new pimcore.bundle.glossary.settings());
        }
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        if (user.isAllowed("glossary")) {
            pimcore.helpers.keyBindingMapping.glossary = function() {
                pimcoreBundleGlossary.editGlossary();
            }
        }
    }
})

const pimcoreBundleGlossary = new pimcore.bundle.glossary.startup();


