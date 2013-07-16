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
        //var navContainer = Ext.get(Ext.query("#pimcore_navigation > ul > li:last"));
        //this.navEl = Ext.get(navContainer.insertSibling('<li id="pimcore_menu_deployment" class="pimcore_menu_item icon-flash">' + t('deployment') + '</li>'));
        //var navContainer = Ext.get(Ext.query("#pimcore_navigation > ul")[0]);
        //this.navEl = Ext.get(navContainer.insertHtml("beforeEnd", '<li id="pimcore_menu_custom" class="pimcore_menu_item icon-truck">' + t('deployment') + '</li>'));
    },
    pimcoreReady: function (params,broker){
       /* var menu = new Ext.menu.Menu({
            items: [{
                text: "Item 1",
                iconCls: "pimcore_icon_apply",
                handler: function () {alert("pressed 1")}
            }, {
                text: "Item 2",
                iconCls: "pimcore_icon_delete",
                handler: function () {alert("pressed 2")}
            }],
            cls: "pimcore_navigation_flyout"
        });
        var toolbar = pimcore.globalmanager.get("layout_toolbar");
        this.navEl.on("mousedown", toolbar.showSubMenu.bind(menu));*/
    }
});
var deployment = new pimcore.plugin.deployment();