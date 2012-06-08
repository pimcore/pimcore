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

pimcore.registerNS("pimcore.report.webmastertools.settings");
pimcore.report.webmastertools.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "webmastertools";
    },

    getLayout: function () {

        this.panel = new Ext.FormPanel({
            layout: "pimcoreform",
            title: "Google Webmastertools",
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            items: [
                {
                    xtype: "displayfield",
                    width: 300,
                    hideLabel: true,
                    value: "&nbsp;<br />" + t("webastertools_settings_description"),
                    cls: "pimcore_extra_label"
                },
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

        // get default
        configs.push(this.getConfiguration("default", t("main_site"), "default"));

        sites.each(function (record) {
            configs.push(this.getConfiguration("site_" + record.data.id, record.data.domains.split(",").join(", "), record.data.id));
        }, this);


        return configs;
    },

    getConfiguration: function (key, name, id) {

        var config = {
            xtype: "fieldset",
            labelWidth: 250,
            title: name,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("verification_filename_text"),
                    name: "verification",
                    width: 250,
                    value: this.parent.getValue("webmastertools.sites." + key + ".verification"),
                    id: "report_settings_webmastertools_verification_" + id
                }
            ]
        };

        return config;
    },

    getValues: function () {

        var formData = this.panel.getForm().getFieldValues();
        var sites = pimcore.globalmanager.get("sites");
        var sitesData = {};

        // default site
        sitesData["default"] = {
            verification: Ext.getCmp("report_settings_webmastertools_verification_default").getValue()
        };

        sites.each(function (record) {
            sitesData["site_" + record.data.id] = {
                verification: Ext.getCmp("report_settings_webmastertools_verification_" + record.data.id).getValue()
            };
        }, this);

        var values = {
            sites: sitesData
        };

        return values;
    }
});


pimcore.report.settings.broker.push("pimcore.report.webmastertools.settings");
