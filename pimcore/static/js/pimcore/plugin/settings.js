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

pimcore.registerNS("pimcore.plugin.settings");
pimcore.plugin.settings = Class.create({
    initialize: function (pluginName, iframeSrc) {

        if (!this.panel) {

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            var height = tabPanel.getHeight();
            height = height - 40;
            console.log(height);
            this.panel = new Ext.Panel({
                id: "pimcore_plugin_" + pluginName,
                title: t('settings_plugins') + ' - ' + pluginName,
                border: false,
                layout: "fit",
                closable:true,
                html: '<iframe frameborder="0" style="width:100%; height: ' + height + 'px" src="' + iframeSrc + '" id="plugin_iframe_' + pluginName + '"></iframe>'

            });


            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_plugin_" + pluginName);


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("plugin_settings_" + pluginName);
            }.bind(this));
            pimcore.layout.refresh();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_plugin_" + pluginName);
    }
});