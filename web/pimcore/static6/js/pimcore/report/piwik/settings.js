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
                    xtype: "displayfield",
                    width: 670,
                    hideLabel: true,
                    value: t("piwik_config_notice"),
                    cls: "pimcore_extra_label",
                    fieldStyle: {
                        color: "#ff0000"
                    }
                },
                {
                    xtype: "fieldset",
                    defaults: {
                        labelWidth: 200
                    },
                    title: t("general_settings"),
                    items: [
                        {
                            xtype: "textfield",
                            fieldLabel: t("piwik_url"),
                            name: "piwik_url",
                            width: 670,
                            id: "report_settings_piwik_url",
                            value: this.parent.getValue("piwik.piwik_url")
                        }, {
                            xtype: "checkbox",
                            fieldLabel: t("piwik_use_ssl"),
                            name: "piwik_use_ssl",
                            id: "report_settings_piwik_use_ssl",
                            checked: this.parent.getValue("piwik.piwik_use_ssl")
                        }
                    ]
                },
                {
                    xtype: "fieldset",
                    defaults: {
                        labelWidth: 200
                    },
                    title: t("piwik_tokens"),
                    collapsible: true,
                    collapsed: true,
                    items: [
                        {
                            xtype: "textfield",
                            inputType: "password",
                            fieldLabel: t("piwik_api_token"),
                            name: "api_token",
                            width: 670,
                            id: "report_settings_piwik_api_token",
                            value: this.parent.getValue("piwik.api_token")
                        }, {
                            xtype: "displayfield",
                            width: 670,
                            hideLabel: true,
                            value: t("piwik_api_token_info"),
                            cls: "pimcore_extra_label"
                        }, {
                            xtype: "textfield",
                            inputType: "password",
                            fieldLabel: t("piwik_report_token"),
                            name: "report_token",
                            width: 670,
                            id: "report_settings_piwik_report_token",
                            value: this.parent.getValue("piwik.report_token")
                        }, {
                            xtype: "displayfield",
                            width: 670,
                            hideLabel: true,
                            value: t("piwik_report_token_info"),
                            cls: "pimcore_extra_label"
                        }
                    ]
                },
                {
                    xtype: "fieldset",
                    defaults: {
                        labelWidth: 200
                    },
                    title: t("piwik_iframe_integration"),
                    collapsible: true,
                    collapsed: true,
                    items: [
                        {
                            xtype: "displayfield",
                            width: 670,
                            hideLabel: true,
                            value: t("piwik_iframe_integration_info"),
                            cls: "pimcore_extra_label"
                        }, {
                            xtype: "textfield",
                            fieldLabel: t("piwik_iframe_username"),
                            name: "iframe_username",
                            width: 670,
                            id: "report_settings_piwik_iframe_username",
                            value: this.parent.getValue("piwik.iframe_username")
                        }, {
                            xtype: "textfield",
                            inputType: "password",
                            fieldLabel: t("piwik_iframe_password"),
                            name: "iframe_password",
                            width: 670,
                            id: "report_settings_piwik_iframe_password",
                            value: this.parent.getValue("piwik.iframe_password")
                        }, {
                            xtype: "displayfield",
                            width: 670,
                            hideLabel: true,
                            value: t("piwik_iframe_password_info"),
                            cls: "pimcore_extra_label"
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
                labelWidth: 200
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
                        labelWidth: 200
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

        return {
            piwik_url: Ext.getCmp("report_settings_piwik_url").getValue(),
            use_ssl: Ext.getCmp("report_settings_piwik_use_ssl").getValue(),
            api_token: Ext.getCmp("report_settings_piwik_api_token").getValue(),
            report_token: Ext.getCmp("report_settings_piwik_report_token").getValue(),
            iframe_username: Ext.getCmp("report_settings_piwik_iframe_username").getValue(),
            iframe_password: Ext.getCmp("report_settings_piwik_iframe_password").getValue(),
            sites: sitesData
        };
    }
});

pimcore.report.settings.broker.push("pimcore.report.piwik.settings");
