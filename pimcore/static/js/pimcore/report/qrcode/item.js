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

pimcore.registerNS("pimcore.report.qrcode.item");
pimcore.report.qrcode.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;

        this.addLayout();
    },


    getAnalyticsVisiblity: function () {
        if(!pimcore.settings.google_analytics_enabled) {
            return false;
        }

        if(this.form && this.form.rendered) {
            var values = this.form.getForm().getFieldValues();
            if(!values["googleAnalytics"]) {
                return false;
            }
        } else {
            if(!this.data.googleAnalytics) {
                return false;
            }
        }
        return true;
    },

    addLayout: function () {

        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        }); 


        var fieldListeners = {
            "keyup": this.generateCode.bind(this)
        };

        var store;

        if(pimcore.settings.google_analytics_enabled) {
            store = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                url: '/admin/reports/analytics/chartmetricdata',
                baseParams: {
                    "metric[]": "visits",
                    filters: "ga:campaign==" + this.data.name + ";ga:medium==QR-Code;ga:source==Mobile"
                },
                root: 'data',
                fields: ['timestamp','datetext','visits']
            });
        } else {
            store = new Ext.data.ArrayStore({
                autoDestroy: true,
                autoLoad: true,
                data: [],
                fields: ['timestamp','datetext','visits']
            });
        }

        this.analytics = new Ext.form.FieldSet({
            hidden: !this.getAnalyticsVisiblity(),
            title: t("google_analytics"),
            items: [{
                xtype: 'linechart',
                store: store,
                xField: 'datetext',
                height: 240,
                series: [
                    {
                        type:'line',
                        displayName: t("visits"),
                        yField: 'visits',
                        style: {
                            color: 0x15428B
                        }
                    }
                ]
            }],
            buttons: [{
                text: t("show_in_google_anaytics"),
                iconCls: "pimcore_icon_analytics",
                handler: function () {
                    var analyticsUrl = "#report/trafficsources-campaigns/a{accountId}w{internalWebPropertyId}p{id}/"
                        + "%3F_r.drilldown%3Danalytics.campaign%3A" + this.data.name
                                                                    + "%2Canalytics.sourceMedium%3AQR-Code/";
                    window.open("/admin/reports/analytics/deeplink?url=" + encodeURIComponent(analyticsUrl));
                }.bind(this)
            }]
        });

        this.form = new Ext.form.FormPanel({
            layout: "pimcoreform",
            region: "center",
            bodyStyle: "padding:10px",
            labelWidth: 150,
            autoScroll: true,
            border:false,
            items: [{
                xtype: "fieldset",
                title: t("general"),
                collapsible: false,
                items: [{
                    xtype: "textfield",
                    name: "name",
                    value: this.data.name,
                    fieldLabel: t("name"),
                    width: 300,
                    disabled: true
                },{
                    xtype: "textarea",
                    name: "description",
                    value: this.data.description,
                    fieldLabel: t("description"),
                    width: 300,
                    height: 50
                },{
                    xtype: "textfield",
                    name: "url",
                    value: this.data.url,
                    fieldLabel: "URL",
                    width: 300,
                    cls: "input_drop_target",
                    enableKeyEvents: true,
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: el,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function(target, dd, e, data) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop : function (el, target, dd, e, data) {
                                    if (data.node.attributes.elementType == "document") {
                                        el.setValue(data.node.attributes.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(this, el)
                            });
                        }.bind(this)
                    }
                },{
                    xtype: "checkbox",
                    name: "googleAnalytics",
                    checked: this.data.googleAnalytics,
                    fieldLabel: t("google_analytics"),
                    handler: function () {
                        if(this.getAnalyticsVisiblity()) {
                            this.analytics.show();
                        } else {
                            this.analytics.hide();
                        }
                    }.bind(this)
                },{
                    xtype: "displayfield",
                    hideLabel: true,
                    value: t("source") + ":Mobile, " + t("medium") + ":QR-Code, " + t("name") + ":" + this.data.name,
                    cls: "pimcore_extra_label_bottom"
                }]
            }, {
                xtype: "fieldset",
                title: t("style"),
                collapsible: false,
                items: [{
                    xtype: "textfield",
                    name: "foreColor",
                    value: this.data.foreColor,
                    fieldLabel: t("foreground_color"),
                    width: 70,
                    emptyText: "#000000",
                    enableKeyEvents: true,
                    listeners: fieldListeners
                }, {
                    xtype: "textfield",
                    name: "backgroundColor",
                    value: this.data.backgroundColor,
                    fieldLabel: t("background_color"),
                    width: 70,
                    emptyText: "#FFFFFF",
                    enableKeyEvents: true,
                    listeners: fieldListeners
                }]
            }, this.analytics]
        });

        this.codePanel = new Ext.Panel({
            html: '',
            border: true,
            height: 250
        });

        this.preview = new Ext.Panel({
            region: "east",
            width: 270,
            border:false,
            autoScroll: true,
            bodyStyle: "padding: 10px;",
            items: [this.codePanel, {
                border: false,
                buttons: [{
                    text: "PNG",
                    iconCls: "pimcore_icon_png",
                    handler: this.download.bind(this, "image")
                },{
                    text: "EPS",
                    iconCls: "pimcore_icon_eps",
                    handler: this.download.bind(this, "eps")
                }, {
                    text: "SVG",
                    iconCls: "pimcore_icon_svg",
                    handler: this.download.bind(this, "svg")
                }]
            }]
        });

        this.panel = new Ext.Panel({
            border: false,
            layout: "border",
            closable: true,
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_qrcode_panel_" + this.data.name,
            items: [this.form, this.preview],
            buttons: panelButtons
        });

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();

        this.generateCode();
    },


    generateCode: function () {
        var params = this.form.getForm().getFieldValues();
        delete params["description"];
        delete params["undefined"];

        var d = new Date();
        params["_dc"] = d.getTime();
        params["name"] = this.data.name;

        var codeUrl = "/admin/reports/qrcode/code/?" + Ext.urlEncode(params);
        this.codePanel.update('<img src="' + codeUrl + '" style="padding:10px; width:228px;" />');
    },

    save: function () {

        var m = Ext.encode(this.form.getForm().getFieldValues());
        Ext.Ajax.request({
            url: "/admin/reports/qrcode/update",
            method: "post",
            params: {
                configuration: m,
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getRootNode().reload();
        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
    },

    download: function (format) {

        var params = this.form.getForm().getFieldValues();
        delete params["description"];
        delete params["undefined"];

        params["renderer"] = format;
        params["download"] = "true";
        params["name"] = this.data.name;
        params["moduleSize"] = 20;

        var codeUrl = "/admin/reports/qrcode/code/?" + Ext.urlEncode(params);
        pimcore.helpers.download(codeUrl);
    }
});
