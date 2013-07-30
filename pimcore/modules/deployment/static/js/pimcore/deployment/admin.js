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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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

        var toolbar = pimcore.globalmanager.get("layout_toolbar");

        toolbar.extrasMenu.add({
            text: t("deployment"),
            hideOnClick: false,
            iconCls: "pimcore_icon_deployment",
            menu: {
                cls: "pimcore_navigation_flyout",
                items: [{
                    text: t("deployment_packages"),
                    iconCls: "pimcore_icon_menu_extension",
                    handler: function () {alert("pressed 1")}
                }]
            }
        });
    }
});
var deployment = new pimcore.plugin.deployment();