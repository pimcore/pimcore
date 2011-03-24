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
                    value: "&nbsp;<br />" + t("webastertools_settings_username_description"),
                    cls: "pimcore_extra_label"
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("username"),
                    name: "username",
                    width: 200,
                    value: this.parent.getValue("webmastertools.username")
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("password"),
                    name: "password",
                    inputType: "password",
                    width: 200,
                    value: this.parent.getValue("webmastertools.password")
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
                    xtype:'combo',
                    fieldLabel: t('profile'),
                    typeAhead:true,
                    value: this.parent.getValue("webmastertools.sites." + key + ".profile"),
                    displayField: 'profile',
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        url: "/admin/reports/settings/get-webmastertools-sites",
                        root: "data",
                        idProperty: "profile",
                        fields: ["profile", "verification"]
                    }),
                    valueField: 'profile',
                    width: 250,
                    forceSelection: true,
                    triggerAction: 'all',
                    hiddenName: 'profile',
                    listeners: {
                        "focus": function (el) {
                            var values = this.panel.getForm().getFieldValues();

                            el.getStore().reload({
                                params: {
                                    username: values["username"],
                                    password: values["password"]
                                }
                            });
                        }.bind(this),
                        "select": function (id, el, record, index) {
                            Ext.getCmp("report_settings_webmastertools_verification_" + id).setValue(record.data.verification);
                        }.bind(this, id)
                    },
                    id: "report_settings_webmastertools_profile_" + id
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("verification_filename"),
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
            profile: Ext.getCmp("report_settings_webmastertools_profile_default").getValue(),
            verification: Ext.getCmp("report_settings_webmastertools_verification_default").getValue()
        };

        sites.each(function (record) {
            sitesData["site_" + record.data.id] = {
                profile: Ext.getCmp("report_settings_webmastertools_profile_" + record.data.id).getValue(),
                verification: Ext.getCmp("report_settings_webmastertools_verification_" + record.data.id).getValue()
            };
        }, this);

        var values = {
            username: formData.username,
            password: formData.password,
            sites: sitesData
        };

        return values;
    }
});


pimcore.report.settings.broker.push("pimcore.report.webmastertools.settings");
