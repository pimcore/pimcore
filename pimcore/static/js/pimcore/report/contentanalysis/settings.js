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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.report.contentanalysis.settings");
pimcore.report.contentanalysis.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "contentanalysis";
    },

    getLayout: function () {

        this.panel = new Ext.FormPanel({
            layout: "pimcoreform",
            title: t("content_social_analysis"),
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            items: [{
                fieldLabel: t("enable"),
                xtype: "checkbox",
                name: "enabled",
                checked: this.parent.getValue("contentanalysis.enabled")
            }, {
                fieldLabel: t("exclude_patterns"),
                xtype: "textarea",
                width: 400,
                height: 80,
                value: this.parent.getValue("contentanalysis.excludePatterns"),
                name: "excludePatterns"
            }, {
                xtype: "displayfield",
                width: 600,
                value: t("exclude_patterns_description"),
                cls: "pimcore_extra_label_bottom"
            }, {
                xtype: "button",
                text: t("cleanup_existing_data"),
                handler: function () {

                    var waitBox = Ext.MessageBox.wait(t("please_wait"));

                    Ext.Ajax.request({
                        url: "/admin/reports/settings/cleanup-existing-content-analysis-data",
                        params: this.getValues(),
                        method: "post",
                        success: function () {
                            waitBox.hide();
                        },
                        failure: function () {
                            waitBox.hide();
                        }
                    });
                }.bind(this)
            }]
        });

        return this.panel;
    },

    getValues: function () {
        var formData = this.panel.getForm().getFieldValues();
        return formData;
    }
});


pimcore.report.settings.broker.push("pimcore.report.contentanalysis.settings");
