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

pimcore.registerNS("pimcore.report.tagmanager.settings");
pimcore.report.tagmanager.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "tagmanager";
    },

    getLayout: function () {

        this.panel = new Ext.FormPanel({
            title: "Google Tag Manager",
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            items: [
                {
                    xtype: "panel",
                    style: "padding:30px 0 0 0;",
                    border: false,
                    items: this.getConfigurations()
                }
            ]
        });

        return this.panel;
    },

    getConfigurations: function () {

        this.configCount = 0;
        var configs = [];
        var sites = pimcore.globalmanager.get("sites");

        sites.each(function (record) {
            var id = record.data.id;
            if (id == "default") {
                key = "default";
            } else {
                key = "site_" + id;
            }
            configs.push(this.getConfiguration(key, record.data.domain, id));
        }, this);


        return configs;
    },

    getConfiguration: function (key, name, id) {

        var config = {
            xtype: "fieldset",
            title: name,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: "Container-ID" + " (GTM-AB1234)",
                    name: "containerId",
                    labelWidth: 250,
                    width: 450,
                    value: this.parent.getValue("tagmanager.sites." + key + ".containerId"),
                    id: "report_settings_tagmanager_containerId_" + id
                }
            ]
        };

        return config;
    },

    getValues: function () {

        var formData = this.panel.getForm().getFieldValues();
        var sites = pimcore.globalmanager.get("sites");
        var sitesData = {};

        sites.each(function (record) {
            var id = record.get("id");
            var key = "";
            if (id == "default") {
                key = "default";
            } else {
                key = "site_" + id;
            }

            sitesData[key] = {
                containerId: Ext.getCmp("report_settings_tagmanager_containerId_" + id).getValue()
            };
        }, this);

        var values = {
            sites: sitesData
        };

        return values;
    }
});


pimcore.report.settings.broker.push("pimcore.report.tagmanager.settings");
