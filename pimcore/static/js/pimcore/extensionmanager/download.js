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

pimcore.registerNS("pimcore.extensionmanager.download");
pimcore.extensionmanager.download = Class.create({

    initialize: function () {

        pimcore.settings.liveconnect.login(this.getTabPanel.bind(this), function () {
            pimcore.globalmanager.remove("extensionmanager_download");
        });
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extensionmanager_download");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_extensionmanager_download",
                title: t("download_extension"),
                iconCls: "pimcore_icon_extensionmanager_download",
                border: false,
                layout: "fit",
                closable:true,
                items: []
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_extensionmanager_download");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extensionmanager_download");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }
});