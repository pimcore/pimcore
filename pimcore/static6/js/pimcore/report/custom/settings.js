/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
                bodyStyle: "padding: 10px;",
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
