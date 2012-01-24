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

pimcore.registerNS("pimcore.report.seocontentdiagnostics.settings");
pimcore.report.seocontentdiagnostics.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "seocontentdiagnostics";
    },

    getLayout: function () {

        this.panel = new Ext.FormPanel({
            layout: "pimcoreform",
            title: t("seocontentdiagnostics"),
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            items: [
                {
                    xtype: "displayfield",
                    width: 300,
                    hideLabel: true,
                    value: t("seo_content_diagnostics_notice"),
                    cls: "pimcore_extra_label",
                    style: "color: #ff0000"
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
            labelWidth: 300,
            title: name,
            items: [
                {
                    xtype: "checkbox",
                    fieldLabel: t("enable"),
                    name: "seocontentdiagnostics_enabled_" + id,
                    id: "seocontentdiagnostics_enabled_" + id,
                    checked: this.parent.getValue("seocontentdiagnostics.sites." + key + ".enabled")
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
            enabled: Ext.getCmp("seocontentdiagnostics_enabled_default").getValue()
        };

        sites.each(function (record) {
            sitesData["site_" + record.data.id] = {
                enabled: Ext.getCmp("seocontentdiagnostics_enabled_" + record.data.id).getValue()
            };
        }, this);

        var values = {
            sites: sitesData
        };

        return values;
    }
});


pimcore.report.settings.broker.push("pimcore.report.seocontentdiagnostics.settings");
