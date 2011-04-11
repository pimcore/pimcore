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

pimcore.registerNS("pimcore.extensionmanager.share");
pimcore.extensionmanager.share = Class.create({

    initialize: function () {

        pimcore.settings.liveconnect.login(this.getTabPanel.bind(this), function () {
            pimcore.globalmanager.remove("extensionmanager_share");
        });
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extensionmanager_share");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_extensionmanager_share",
                title: t("extensions_share"),
                iconCls: "pimcore_icon_extension_share",
                border: false,
                layout: "fit",
                closable:true,
                items: []
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_extensionmanager_share");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extensionmanager_share");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }
});