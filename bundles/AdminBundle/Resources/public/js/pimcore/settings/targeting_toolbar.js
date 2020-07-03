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

pimcore.registerNS("pimcore.settings.targetingToolbar");
pimcore.settings.targetingToolbar = Class.create({
    initialize: function () {
        var that = this;
        var cookieName = "pimcore_targeting_debug";

        var buttons = [];
        if ("1" === Ext.util.Cookies.get(cookieName)) {
            buttons.push({
                text: t("deactivate"),
                iconCls: "pimcore_icon_targeting_toolbar_disable",
                handler: function () {
                    Ext.util.Cookies.clear(cookieName);
                    that.window.close();
                }
            });
        } else {
            buttons.push({
                text: t("activate"),
                iconCls: "pimcore_icon_targeting_toolbar_enable",
                handler: function () {
                    Ext.util.Cookies.set(cookieName, "1");
                    that.window.close();
                }
            });
        }

        this.window = new Ext.Window({
            layout: "fit",
            width: 500,
            closeAction: "close",
            modal: true,
            items: [{
                xtype: "panel",
                border: false,
                bodyStyle: "padding: 20px; font-size: 14px;",
                html: t("targeting_toolbar_browser_note", null,
                    {
                            targetingLink: 'https://pimcore.com/docs/6.x/Development_Documentation/Tools_and_Features/Targeting_and_Personalization/index.html#page_Debugging-Targeting-Data'
                    })
            }],
            buttons: buttons
        });

        pimcore.viewport.add(this.window);
        this.window.show();
    }
});
