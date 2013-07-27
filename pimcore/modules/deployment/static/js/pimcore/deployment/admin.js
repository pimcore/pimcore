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
        this.navEl = Ext.get('pimcore_menu_logout').insertSibling('<li id="pimcore_menu_custom" class="pimcore_menu_item icon-exchange">' + t('deployment') + '</li>');
     },

    pimcoreReady: function (params,broker){
        var menu = new Ext.menu.Menu({
            items: [{
                text: t("deployment_packages"),
                iconCls: "pimcore_icon_menu_extension",
                handler: function () {alert("pressed 1")}
            }],
            cls: "pimcore_navigation_flyout"
        });
        this.navEl.on("mousedown", pimcore.globalmanager.get("layout_toolbar").showSubMenu.bind(menu));
    }
});
var deployment = new pimcore.plugin.deployment();