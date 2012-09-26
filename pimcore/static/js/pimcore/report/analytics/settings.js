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
                    value: "&nbsp;<br />" + t("analytics_settings_description") + "<br /><br />" + t('only_required_for_reporting_in_pimcore_but_not_for_code_integration'),
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
            labelWidth: 300,
            title: name,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("analytics_trackid_code"),
                    name: "trackid_" + id,
                    id: "report_settings_analytics_trackid_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".trackid")
                },{
                    xtype: "textarea",
                    fieldLabel: t("analytics_additional_code"),
                    name: "additionalcode_" + id,
                    height: 100,
                    width: 350,
                    id: "report_settings_analytics_additionalcode_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".additionalcode")
                },{
                    xtype: "textarea",
                    fieldLabel: t("analytics_additional_code_before_pageview"),
                    name: "additionalcodebeforepageview" + id,
                    height: 100,
                    width: 350,
                    id: "report_settings_analytics_additionalcodebeforepageview_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".additionalcodebeforepageview")
                },{
                    xtype: "displayfield",
                    hideLabel: true,
                    width: 500,
                    style: "margin-top:20px;",
                    value: t('only_required_for_reporting_in_pimcore_but_not_for_code_integration'),
                    cls: "pimcore_extra_label_bottom"
                },
                {
                    xtype:'combo',
                    fieldLabel: t('profile'),
                    typeAhead:true,
                    displayField: 'name',
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        url: "/admin/reports/analytics/get-profiles",
                        root: "data",
                        idProperty: "id",
                        fields: ["name","id","trackid","accountid","internalid"]
                    }),
                    listeners: {
                        "select": function (id, el, record, index) {
                            Ext.getCmp("report_settings_analytics_trackid_" + id).setValue(record.get("trackid"));
                            Ext.getCmp("report_settings_analytics_accountid_" + id).setValue(record.get("accountid"));
                            Ext.getCmp("report_settings_analytics_internalid_" + id).setValue(record.get("internalid"));
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
                    fieldLabel: t("analytics_accountid"),
                    name: "accountid_" + id,
                    id: "report_settings_analytics_accountid_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".accountid")
                },{
                    xtype: "textfield",
                    fieldLabel: t("analytics_internalid"),
                    name: "internalid_" + id,
                    id: "report_settings_analytics_internalid_" + id,
                    value: this.parent.getValue("analytics.sites." + key + ".internalid")
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
            additionalcode: Ext.getCmp("report_settings_analytics_additionalcode_default").getValue(),
            additionalcodebeforepageview: Ext.getCmp("report_settings_analytics_additionalcodebeforepageview_default").getValue(),
            accountid: Ext.getCmp("report_settings_analytics_accountid_default").getValue(),
            internalid: Ext.getCmp("report_settings_analytics_internalid_default").getValue(),
            advanced: Ext.getCmp("report_settings_analytics_advanced_default").getValue()
        };

        sites.each(function (record) {
            sitesData["site_" + record.data.id] = {
                profile: Ext.getCmp("report_settings_analytics_profile_" + record.data.id).getValue(),
                trackid: Ext.getCmp("report_settings_analytics_trackid_" + record.data.id).getValue(),
                additionalcode: Ext.getCmp("report_settings_analytics_additionalcode_" + record.data.id).getValue(),
                additionalcodebeforepageview: Ext.getCmp("report_settings_analytics_additionalcodebeforepageview_" + record.data.id).getValue(),
                accountid: Ext.getCmp("report_settings_analytics_accountid_" + record.data.id).getValue(),
                internalid: Ext.getCmp("report_settings_analytics_internalid_" + record.data.id).getValue(),
                advanced: Ext.getCmp("report_settings_analytics_advanced_" + record.data.id).getValue()
            };
        }, this);

        var values = {
            sites: sitesData
        };

        return values;
    }
});


pimcore.report.settings.broker.push("pimcore.report.analytics.settings");
