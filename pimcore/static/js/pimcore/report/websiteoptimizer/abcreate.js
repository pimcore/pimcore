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

pimcore.registerNS("pimcore.report.websiteoptimizer.abcreate");
pimcore.report.websiteoptimizer.abcreate = Class.create(pimcore.report.abstract, {

    matchType: function (type) {
        var types = ["document_page"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types) && pimcore.settings.google_analytics_enabled) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "create_ab_test";
    },

    getIconCls: function () {
        return "pimcore_icon_websiteoptimizer_abcreate";
    },

    getPanel: function () {

        this.versionStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/websiteoptimizer/get-document-versions",
            baseParams: {
                id: this.reference.id
            },
            root: 'versions',
            fields: ['id', {name: 'date', convert: function (v, r) {
                var d = new Date(intval(v) * 1000);

                var ret = d.format("Y-m-d H:i");
                if (r.user) {
                    ret += " - " + r.user.username;
                }
                return ret;
            }}]
        });


        this.conversionPage = new Ext.form.TextField({
            name: "conversionPage",
            fieldLabel: t("conversion_page"),
            width: 400,
            listeners: {
                "render": this.addDropTarget.bind(this)
            }
        });

        this.form = new Ext.form.FormPanel({
            bodyStyle: "padding: 10px;",
            labelWidth: 200,
            items: [
                {
                    xtype: "displayfield",
                    value: t("create_ab_description"),
                    hideLabel: true
                },
                {
                    xtype: "textfield",
                    name: "name",
                    width: 200,
                    fieldLabel: t("experiment_name")
                },
                {
                    xtype: "hidden",
                    name: "documentId",
                    value: this.reference.id
                },
                this.conversionPage,
                {
                    xtype: "toolbar",
                    style: "margin: 20px 0 10px 0",
                    items: [
                        {
                            xtype: "tbtext",
                            text: "<b>" + t("add_your_test_pages") + "</b>"
                        },
                        "->",
                        {
                            text: t("add_variation_page"),
                            iconCls: "pimcore_icon_add",
                            handler: function () {
                                this.form.add(this.getTestPageForm());
                                this.form.doLayout();
                            }.bind(this)
                        }
                    ]
                },
                this.getTestPageForm(),
                this.getTestPageForm()
            ],
            buttons: [
                {
                    text: t("save"),
                    handler: this.save.bind(this),
                    iconCls: "pimcore_icon_analytics_apply"
                }
            ]
        });

        var panel = new Ext.Panel({
            title: t("create_ab_test"),
            bodyStyle: "padding: 10px",
            autoScroll: true,
            items: [this.form]
        });

        return panel;
    },

    getTestPageForm: function () {
        if (!this.testPageCount) {
            this.testPageCount = 0;
        }

        this.testPageCount++;

        return new Ext.form.FieldSet({
            title: t("variation_page") + " " + this.testPageCount,
            collapsible: false,
            labelWidth: 150,
            items: [
                {
                    xtype: "textfield",
                    name: "page_name_" + this.testPageCount,
                    fieldLabel: t("name")
                },
                {
                    xtype: "textfield",
                    name: "page_url_" + this.testPageCount,
                    fieldLabel: t("URL"),
                    width: 400,
                    listeners: {
                        "render": this.addDropTarget.bind(this)
                    }
                }/*,{
                 xtype: "displayfield",
                 value: t("or")
                 },{
                 xtype: "combo",
                 name: "page_version_" + this.testPageCount,
                 fieldLabel: t("select_version"),
                 store: this.versionStore,
                 displayField: "date",
                 valueField: "id",
                 triggerAction: "all",
                 width: 300
                 }*/
            ]
        });
    },

    addDropTarget: function (el) {

        new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: "element",
            getTargetFromEvent: function(e) {
                return this.getEl();
            }.bind(el),

            onNodeOver : function(target, dd, e, data) {
                return Ext.dd.DropZone.prototype.dropAllowed;
            },

            onNodeDrop : function (target, dd, e, data) {
                if (data.node.attributes.elementType == "document") {
                    this.setValue(data.node.attributes.path);
                    return true;
                }
                return false;
            }.bind(el)
        });
    },

    save: function () {
        
        Ext.Ajax.request({
            url: "/admin/reports/websiteoptimizer/ab-save",
            params: this.form.getForm().getFieldValues(),
            success: function (response) {

                try {
                    var res = Ext.decode(response.responseText);
                    if (res && res.success) {
                        Ext.Msg.alert(t("create_ab_test"), t("ab_test_was_created_successfully"));

                        //this.reportPanel.reportContainer.removeAll();
                        //this.reportPanel.reportContainer.doLayout();
                    }
                    else {
                        throw("error");
                    }
                }
                catch (e) {
                    Ext.Msg.alert(t("create_ab_test"), t("ab_test_error"));
                }
            }.bind(this)
        });
    }
});

// add to report broker
pimcore.report.broker.addGroup("websiteoptimizer", "google_websiteoptimizer", "pimcore_icon_report_websiteoptimizer_group");
pimcore.report.broker.addReport(pimcore.report.websiteoptimizer.abcreate, "websiteoptimizer");
