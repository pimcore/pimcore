/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in 
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.classificationstore.configPanel");
pimcore.object.classificationstore.configPanel = Class.create({

    initialize: function (storeConfig) {
        this.storeConfig = storeConfig;
        this.getTabPanel();
    },

    //activate: function () {
    //    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    //    tabPanel.setActiveItem("pimcore_object_classificationstore_configpanel");
    //},

    getTabPanel: function () {

        if (!this.panel) {
            var panelButtons = [];

            this.panel = new Ext.TabPanel({
                title: t("classificationstore_menu_config"),
                closable: true,
                //deferredRender: false,
                //forceLayout: true,
                activeTab: 1,
                items: [this.getCollectionsPanel() , this.getGroupsPanel(), this.getPropertiesPanel()],
                buttons: panelButtons
            });

            //var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            //tabPanel.add(this.panel);
            //tabPanel.setActiveItem("pimcore_object_classificationstore_configpanel");

            //this.panel.on("destroy", function () {
            //    pimcore.globalmanager.remove("classificationstore_config");
            //}.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getCollectionsPanel: function () {
        var panel = new pimcore.object.classificationstore.collectionsPanel(this.storeConfig);
        return panel.getPanel();
    },

    getGroupsPanel: function () {
        var groupsPanel = new pimcore.object.classificationstore.groupsPanel(this.storeConfig);
        return groupsPanel.getPanel();
    },

    getPropertiesPanel: function () {
        var propertiesPanel = new pimcore.object.classificationstore.propertiespanel(this.storeConfig);
        return propertiesPanel.getPanel();
    }

});

