/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.object.classificationstore.configPanel");
pimcore.object.classificationstore.configPanel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_object_classificationstore_configpanel");
    },

    getTabPanel: function () {

        if (!this.panel) {
            var panelButtons = [];

            this.panel = new Ext.TabPanel({
                title: t("classificationstore_menu_config"),
                closable: true,
                deferredRender: false,
                forceLayout: true,
                activeTab: 1,
                id: "pimcore_object_classificationstore_configpanel",
                iconCls: "pimcore_icon_classificationstore",
                items: [this.getCollectionsPanel(), this.getGroupsPanel(), this.getPropertiesPanel()],
                buttons: panelButtons
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_object_classificationstore_configpanel");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("classifcationstore_config");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getCollectionsPanel: function () {
        var panel = new pimcore.object.classificationstore.collectionsPanel();
        return panel.getPanel();
    },

    getGroupsPanel: function () {
        var groupsPanel = new pimcore.object.classificationstore.groupsPanel();
        return groupsPanel.getPanel();
    },

    getPropertiesPanel: function () {
        var propertiesPanel = new pimcore.object.classificationstore.propertiespanel();
        return propertiesPanel.getPanel();
    }

});

