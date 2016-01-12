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

pimcore.registerNS("pimcore.object.keyvalue.configpanel");
pimcore.object.keyvalue.configpanel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_plugin_keyvalueconfig_panel");
    },

    getExportUrl: function() {
        return "/admin/key-value/export";
    },

    getUploadUrl: function() {
        return "/admin/key-value/import";
    },

    getTabPanel: function () {

        if (!this.panel) {
            var panelButtons = [];

            panelButtons.push({
                text: t("import"),
                iconCls: "pimcore_icon_class_import",
                handler: this.upload.bind(this)
            });

            panelButtons.push({
                text: t("export"),
                iconCls: "pimcore_icon_class_export",
                handler: function() {
                    pimcore.helpers.download(this.getExportUrl());
                }.bind(this)
            });

            this.panel = new Ext.TabPanel({
                title: t("keyvalue_menu_config"),
                closable: true,
                deferredRender: false,
                forceLayout: true,
                activeTab: 0,
                id: "pimcore_plugin_keyvalueconfig_panel",
                iconCls: "pimcore_icon_key",
                items: [this.getGroupsPanel(), this.getPropertiesPanel()],
                buttons: panelButtons
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_plugin_keyvalueconfig_panel");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("keyvalue_config");
            }.bind(this));

            pimcore.layout.refresh();

        }

        return this.panel;
    },


    getGroupsPanel: function () {
        var groupsPanel = new pimcore.object.keyvalue.groupspanel();
        return groupsPanel.getPanel();
    },


    getPropertiesPanel: function () {
        var propertiesPanel = new pimcore.object.keyvalue.propertiespanel();
        return propertiesPanel.getPanel();
    },

    upload: function() {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function() {
            this.panel.removeAll();
            var groupsPanel = this.getGroupsPanel();
            this.panel.add(groupsPanel);
            this.panel.add(this.getPropertiesPanel());
            this.panel.setActiveTab(groupsPanel);
            pimcore.layout.refresh();
        }.bind(this), function (response) {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    }
});

