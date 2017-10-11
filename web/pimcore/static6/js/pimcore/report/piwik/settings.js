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

pimcore.registerNS("pimcore.report.piwik.settings");
pimcore.report.piwik.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "piwik";
    },

    getLayout: function () {
        this.panel = new Ext.FormPanel({
            title: "Piwik",
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            items: [
                {
                    xtype: "fieldset",
                    defaults: {
                        labelWidth: 300
                    },
                    title: "General Settings",
                    items: [
                        {
                            xtype: "textfield",
                            fieldLabel: t("piwik_url"),
                            name: "piwik_url",
                            width: 670,
                            id: "report_settings_piwik_url",
                            value: this.parent.getValue("piwik.piwik_url")
                        }, {
                            xtype: "textfield",
                            fieldLabel: t("piwik_auth_token"),
                            name: "auth_token",
                            width: 670,
                            id: "report_settings_piwik_auth_token",
                            value: this.parent.getValue("piwik.auth_token")
                        }
                    ]
                },
                {
                    xtype: "panel",
                    style: "padding: 30px 0 0 0;",
                    border: false,
                    items: this.getConfigurations()
                }
            ]
        });

        return this.panel;
    },

    getConfigurations: function () {
        var configs = [];
        var sites = pimcore.globalmanager.get("sites");

        sites.each(function (record) {
            var id = record.data.id;
            if ("default" === id) {
                key = "default";
            } else {
                key = "site_" + id;
            }

            configs.push(this.getConfiguration(key, record.data.domain, id));
        }, this);

        return configs;
    },

    getConfiguration: function (key, name, id) {
        return {
            xtype: "fieldset",
            defaults: {
                labelWidth: 300
            },
            title: name,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("piwik_site_id"),
                    name: "site_id_" + id,
                    width: 670,
                    id: "report_settings_piwik_site_id_" + id,
                    value: this.parent.getValue("piwik.sites." + key + ".site_id")
                }, {
                    xtype: "fieldset",
                    collapsible: true,
                    collapsed: true,
                    title: t("code_settings"),
                    defaults: {
                        labelWidth: 300
                    },
                    items: [{
                        xtype: "textarea",
                        fieldLabel: t("code_before_init"),
                        name: "code_before_init_" + id,
                        height: 100,
                        width: 650,
                        id: "report_settings_piwik_code_before_init_" + id,
                        value: this.parent.getValue("piwik.sites." + key + ".code_before_init"),
                        style: "font-family: Consolas, 'Courier New', Courier, monospace;"
                    }, {
                        xtype: "textarea",
                        fieldLabel: t("code_before_pageview"),
                        name: "code_before_track_" + id,
                        height: 100,
                        width: 650,
                        id: "report_settings_piwik_code_before_track_" + id,
                        value: this.parent.getValue("piwik.sites." + key + ".code_before_track"),
                        style: "font-family: Consolas, 'Courier New', Courier, monospace;"
                    }, {
                        xtype: "textarea",
                        fieldLabel: t("code_after_pageview"),
                        name: "code_after_track_" + id,
                        height: 100,
                        width: 650,
                        id: "report_settings_piwik_code_after_track_" + id,
                        value: this.parent.getValue("piwik.sites." + key + ".code_after_track"),
                        style: "font-family: Consolas, 'Courier New', Courier, monospace;"
                    }]
                }
            ]
        };
    },

    getValues: function () {
        var formData = this.panel.getForm().getFieldValues();
        var sites = pimcore.globalmanager.get("sites");
        var sitesData = {};

        sites.each(function (record) {
            var id = record.get("id");
            if ("default" === id) {
                key = "default";
            } else {
                key = "site_" + id;
            }

            sitesData[key] = {
                site_id: Ext.getCmp("report_settings_piwik_site_id_" + id).getValue(),
                code_before_init: Ext.getCmp("report_settings_piwik_code_before_init_" + id).getValue(),
                code_before_track: Ext.getCmp("report_settings_piwik_code_before_track_" + id).getValue(),
                code_after_track: Ext.getCmp("report_settings_piwik_code_after_track_" + id).getValue()
            };
        }, this);

        var values = {
            piwik_url: Ext.getCmp("report_settings_piwik_url").getValue(),
            auth_token: Ext.getCmp("report_settings_piwik_auth_token").getValue(),
            sites: sitesData
        };

        return values;
    }
});

pimcore.report.settings.broker.push("pimcore.report.piwik.settings");
