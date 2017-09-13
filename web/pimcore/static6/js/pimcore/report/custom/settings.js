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

pimcore.registerNS("pimcore.report.custom.settings");
pimcore.report.custom.settings = Class.create({

    initialize: function (parent) {
        this.getPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_custom_reports_settings");
    },

    getPanel: function () {

        var editor = new pimcore.report.custom.panel();

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_custom_reports_settings",
                title: t("custom_reports"),
                iconCls: "pimcore_icon_reports",
                layout: "fit",
                closable:true,
                items: [editor.getTabPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_custom_reports_settings");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("custom_reports_settings");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }
});
