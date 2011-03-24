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

pimcore.registerNS("pimcore.report.analytics.settings");
pimcore.report.analytics.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "analytics";
    },

    getLayout: function () {

        this.panel = new Ext.FormPanel({
            layout: "pimcoreform",
            title: "Google Analytics",
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            items: [
                {
                    xtype: "displayfield",
                    width: 300,
                    hideLabel: true,
                    value: t("analytics_notice"),
                    cls: "pimcore_extra_label",
                    style: "color: #ff0000"
                },
                {
                    xtype: "displayfield",
                    width: 300,
                    hideLabel: true,
                    value: "&nbsp;<br />" + t("analytics_settings_username_description"),
                    cls: "pimcore_extra_label"
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("username"),
                    name: "username",
                    value: this.parent.getValue("analytics.username"),
                    width: 200
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("password"),
                    name: "password",
                    inputType: "password",
                    value: this.parent.getValue("analytics.password"),
                    width: 200
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
                    displayField: 'name',
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        url: "/admin/reports/settings/get-analytics-profiles",
                        root: "data",
                        idProperty: "id",
                        fields: ["name", "id", "trackid","accountid"]
                    }),
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
                            Ext.getCmp("report_settings_analytics_trackid_" + id).setValue(record.data.trackid);
                            Ext.getCmp("report_settings_analytics_accountid_" + id).setValue(record.data.accountid);
                        }.bind(this, id)
                    },
                    valueField: 'id',
                    width: 250,
                    forceSelection: true,
                    triggerAction: 'all',
                    hiddenName: 'profile_' + id,
                    id: "report_settings_analytics_profile_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".profile")
                },{
                    xtype: "textfield",
                    fieldLabel: t("analytics_trackid"),
                    name: "trackid_" + id,
                    id: "report_settings_analytics_trackid_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".trackid")
                },{
                    xtype: "textfield",
                    fieldLabel: t("analytics_accountid"),
                    name: "accountid_" + id,
                    id: "report_settings_analytics_accountid_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".accountid")
                },{
                    xtype: "checkbox",
                    fieldLabel: t("analytics_advanced_mode"),
                    name: "advanced_" + id,
                    id: "report_settings_analytics_advanced_" + id,
                    checked: this.parent.getValue("analytics.sites." + key + ".advanced")
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
            profile: Ext.getCmp("report_settings_analytics_profile_default").getValue(),
            trackid: Ext.getCmp("report_settings_analytics_trackid_default").getValue(),
            accountid: Ext.getCmp("report_settings_analytics_accountid_default").getValue(),
            advanced: Ext.getCmp("report_settings_analytics_advanced_default").getValue()
        };

        sites.each(function (record) {
            sitesData["site_" + record.data.id] = {
                profile: Ext.getCmp("report_settings_analytics_profile_" + record.data.id).getValue(),
                trackid: Ext.getCmp("report_settings_analytics_trackid_" + record.data.id).getValue(),
                accountid: Ext.getCmp("report_settings_analytics_accountid_" + record.data.id).getValue(),
                advanced: Ext.getCmp("report_settings_analytics_advanced_" + record.data.id).getValue()
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


pimcore.report.settings.broker.push("pimcore.report.analytics.settings");
