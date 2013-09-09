/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
pimcore.registerNS("pimcore.plugin.deployment");
pimcore.plugin.deployment = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.deployment";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params,broker) {

        var user = pimcore.globalmanager.get("user");

        if (user.isAllowed("deployment")) {
            var toolbar = pimcore.globalmanager.get("layout_toolbar");
            var globalManagerKey = 'pimcore.plugin.deployment.packages';
            toolbar.extrasMenu.add({
                text: t("deployment"),
                hideOnClick: false,
                iconCls: "pimcore_icon_deployment",
                menu: {
                    cls: "pimcore_navigation_flyout",
                    items: [{
                        text: t("deployment_packages"),
                        iconCls: "pimcore_icon_menu_extension",
                        handler: function () {
                            try {
                                pimcore.globalmanager.get(globalManagerKey).activate();
                            }
                            catch (e) {
                                pimcore.globalmanager.add(globalManagerKey, new pimcore.plugin.deployment.packages());
                            }
                        }
                    }]
                }
            });
        }
    }
});
var deployment = new pimcore.plugin.deployment();