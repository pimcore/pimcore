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
 * @copyright  Copyright (c) 2009-2012 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
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

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.TabPanel({
                title: t("keyvalue_menu_config"),
                closable: true,
                deferredRender: false,
                forceLayout: true,
                activeTab: 0,
                id: "pimcore_plugin_keyvalueconfig_panel",
                iconCls: "pimcore_icon_key",
                items: [this.getGroupsPanel(), this.getPropertiesPanel()]
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
    }
});

