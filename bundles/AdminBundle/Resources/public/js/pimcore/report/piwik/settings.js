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
            title: "Matomo/Piwik",
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
                            value: this.parent.getValue("piwik.piwik_url"),
                            enableKeyEvents: true
                        }
                    ]
                },
                {
                    xtype: "fieldset",
                    defaults: {
                        labelWidth: 200
                    },
                    title: t("piwik_api_settings"),
                    collapsible: true,
                    collapsed: true,
                    items: [
                        {
                            xtype: "textarea",
                            fieldLabel: t("piwik_api_client_options"),
                            name: "api_client_options",
                            height: 100,
                            width: 650,
                            id: "report_settings_piwik_api_client_options",
                            value: this.parent.getValue("piwik.api_client_options"),
                            style: "font-family: Consolas, 'Courier New', Courier, monospace;"
                        }, {
                            xtype: "displayfield",
                            width: 670,
                            hideLabel: true,
                            value: t("piwik_api_client_options_description", null,
                                { guzzleLink: 'http://docs.guzzlephp.org/en/stable/request-options.html'}),
                            cls: "pimcore_extra_label"
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
                            value: t("piwik_iframe_integration_info", null ,
                                { matomoLink: 'https://matomo.org/faq/troubleshooting/#faq_147'}),
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
                    border: false,
                    items: this.getConfigurations()
                }
            ]
        });

        this.loadMask = new Ext.LoadMask({
            target: this.panel,
            msg: t("please_wait")
        });

        this.loadMask.hide();

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
                this.buildSiteIdContainer(key, name, id),
                {
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

    buildSiteIdContainer: function(key, name, id) {
        var that = this;

        // initial site id state when loading the panel
        var siteId = this.parent.getValue("piwik.sites." + key + ".site_id");

        var siteIdInput = new Ext.form.field.Number({
            fieldLabel: t("piwik_site_id"),
            name: "site_id_" + id,
            width: 300,
            id: "report_settings_piwik_site_id_" + id,
            value: siteId
        });

        var container = new Ext.form.FieldContainer({
            layout: 'hbox',
            items: [
                siteIdInput
            ]
        });

        // do not show create/update buttons if api token is not configured
        if (!this.parent.getValue("piwik.api_token")) {
            return container;
        }

        var createHandler = function(url, method, successMessage, errorMessage) {
            return function () {
                that.loadMask.show();

                Ext.Ajax.request({
                    url: url,
                    method: method,
                    ignoreErrors: true, // do not pop up error window on failure
                    callback: function () {
                        that.loadMask.hide();
                    },

                    success: function (response) {
                        var json = Ext.decode(response.responseText);

                        var message = successMessage;
                        if (json.message) {
                            message += ': ' + json.message;
                        }

                        if (json.site_id) {
                            siteIdInput.setValue(json.site_id);
                        }

                        pimcore.helpers.showNotification(t("success"), message, "success");
                    },

                    failure: function (response) {
                        var message = errorMessage;

                        try {
                            var json = Ext.decode(response.responseText);
                            if (json.message) {
                                message += ': ' + json.message;
                            }
                        } catch (e) {}

                        pimcore.helpers.showNotification(t("error"), message, "error");
                    }
                });
            };
        };

        var createButton = new Ext.button.Button({
            text: t('piwik_api_create_site'),
            tooltip: t('piwik_api_create_site_tooltip'),
            iconCls: "pimcore_icon_piwik_api_create",
            hidden: true,
            style: "margin-left: 5px"
        });

        var updateButton = new Ext.button.Button({
            text: t('piwik_api_update_site'),
            tooltip: t('piwik_api_update_site_tooltip'),
            iconCls: "pimcore_icon_piwik_api_update",
            hidden: true,
            style: "margin-left: 5px"
        });

        var buttonStateHandler = function() {
            var val = siteIdInput.getValue();

            if (val && val > 0) {
                createButton.hide();
                updateButton.show();
            } else {
                // only show create button if there was no site ID configured
                // when the panel was loaded to avoid having the create button
                // showing up as soon as the value is cleared while the backend
                // config still holds the site id
                if (!siteId) {
                    createButton.show();
                }

                updateButton.hide();
            }
        };

        siteIdInput.on('change', buttonStateHandler);
        buttonStateHandler(); // trigger initial state

        createButton.setHandler(createHandler(
            Routing.generate('pimcore_admin_reports_piwik_apisiteupdate', {configKey: key}),
            'POST',
            t("piwik_api_create_site_success"),
            t("piwik_api_create_site_failure")
        ));

        updateButton.setHandler(createHandler(
            Routing.generate('pimcore_admin_reports_piwik_apisiteupdate', {configKey: key}),
            'PUT',
            t("piwik_api_update_site_success"),
            t("piwik_api_update_site_failure")
        ));

        container.add(createButton);
        container.add(updateButton);

        return container;
    },

    getValues: function () {
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
            api_token: Ext.getCmp("report_settings_piwik_api_token").getValue(),
            report_token: Ext.getCmp("report_settings_piwik_report_token").getValue(),
            api_client_options: Ext.getCmp("report_settings_piwik_api_client_options").getValue(),
            iframe_username: Ext.getCmp("report_settings_piwik_iframe_username").getValue(),
            iframe_password: Ext.getCmp("report_settings_piwik_iframe_password").getValue(),
            sites: sitesData
        };
    }
});

pimcore.layout.treepanelmanager.addOnReadyCallback(function() {
    'use strict';

    var user = pimcore.globalmanager.get("user");
    if (user.isAllowed("piwik_settings")) {
        pimcore.report.settings.broker.push("pimcore.report.piwik.settings");
    }
});
