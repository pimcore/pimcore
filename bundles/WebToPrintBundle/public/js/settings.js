/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.web2print.settings");
/**
 * @private
 */
pimcore.bundle.web2print.settings = Class.create({

    initialize: function () {

        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_web2print_settings_getweb2print'),
            success: function (response) {

                this.data = Ext.decode(response.responseText);
                this.getTabPanel();

            }.bind(this)
        });
    },

    getValue: function (key, ignoreCheck) {

        var nk = key.split("\.");
        var current = this.data.values;

        for (var i = 0; i < nk.length; i++) {
            if (current[nk[i]]) {
                current = current[nk[i]];
            } else {
                current = null;
                break;
            }
        }

        if (ignoreCheck || (typeof current != "object" && typeof current != "array" && typeof current != "function")) {
            return current;
        }

        return "";
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = Ext.create('Ext.panel.Panel', {
                id: "pimcore_settings_web2print",
                title: t("web2print_settings"),
                iconCls: "pimcore_icon_printpage pimcore_icon_overlay_setting",
                border: false,
                layout: "fit",
                closable: true
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("bundle_web2print");
            }.bind(this));


            this.pdfReactorSettings = Ext.create("Ext.form.FieldSet", {
                title: t('web2print_pdfreactor_settings'),
                collapsible: true,
                collapsed: false,
                hidden: this.getValue("generalTool") != 'pdfreactor',
                autoHeight: true,
                defaultType: 'textfield',
                defaults: {width: 450},
                items: [
                    {
                        fieldLabel: t("web2print_protocol"),
                        xtype: "combo",
                        width: 600,
                        editable: false,
                        name: "pdfreactorProtocol",
                        value: this.getValue("pdfreactorProtocol"),
                        store: [
                            ["http", "http"],
                            ["https", "https"]
                        ],
                        mode: "local",
                        triggerAction: "all"
                    },{
                        xtype: 'textfield',
                        width: 650,
                        fieldLabel: t("web2print_server"),
                        name: 'pdfreactorServer',
                        value: this.getValue("pdfreactorServer")
                    },{
                        xtype: 'textfield',
                        width: 650,
                        fieldLabel: t("web2print_port"),
                        name: 'pdfreactorServerPort',
                        value: this.getValue("pdfreactorServerPort"),
                        emptyText: "9423"
                    },{
                        xtype: 'textfield',
                        width: 650,
                        fieldLabel: t("web2print_baseURL"),
                        name: 'pdfreactorBaseUrl',
                        value: this.getValue("pdfreactorBaseUrl")
                    }, {
                        xtype: "displayfield",
                        hideLabel: true,
                        width: 600,
                        value: t('web2print_baseURL_txt'),
                        emptyText: "http://my-domain.org",
                        cls: "pimcore_extra_label_bottom"
                    },{
                        xtype: 'textfield',
                        width: 650,
                        fieldLabel: t("web2print_apiKey"),
                        name: 'pdfreactorApiKey',
                        value: this.getValue("pdfreactorApiKey")
                    }, {
                        xtype: "displayfield",
                        hideLabel: true,
                        width: 600,
                        value: t('web2print_apiKey_txt'),
                        cls: "pimcore_extra_label_bottom"
                    },{
                        xtype: 'textarea',
                        width: 650,
                        height: 200,
                        fieldLabel: t("web2print_licence"),
                        name: 'pdfreactorLicence',
                        value: this.getValue("pdfreactorLicence")
                    }, {
                        xtype: 'checkbox',
                        fieldLabel: t("web2print_enableLenientHttpsMode"),
                        name: 'pdfreactorEnableLenientHttpsMode',
                        value: this.getValue("pdfreactorEnableLenientHttpsMode")
                    }, {
                        xtype: "displayfield",
                        hideLabel: true,
                        width: 600,
                        value: t('web2print_enableLenientHttpsMode_txt'),
                        cls: "pimcore_extra_label_bottom"
                    }, {
                        xtype: 'checkbox',
                        fieldLabel: t("web2print_enableDebugMode"),
                        name: 'pdfreactorEnableDebugMode',
                        value: this.getValue("pdfreactorEnableDebugMode")
                    }
                ]
            });

            this.chromiumSettings = Ext.create("Ext.form.FieldSet", {
                title: t('web2print_chromium_settings'),
                collapsible: true,
                collapsed: false,
                autoHeight: true,
                hidden: this.getValue("generalTool") != 'chromium',
                defaultType: 'textfield',
                defaults: {width: 450},
                items: [
                    {
                        xtype: "displayfield",
                        fieldLabel: t("web2print_chromium_documentation_docker"),
                        name: 'additions',
                        width: 850,
                        value: t('web2print_chromium_documentation_docker_text'),
                    },
                    {
                        xtype: 'textfield',
                        width: 650,
                        fieldLabel: t("web2print_hostURL"),
                        name: 'chromiumHostUrl',
                        value: this.getValue("chromiumHostUrl")
                    },
                    {
                        xtype: "displayfield",
                        fieldLabel: t("web2print_chromium_documentation"),
                        name: 'documentation',
                        width: 600,
                        value: t('web2print_chromium_options_documentation'),
                        autoEl:{
                            tag: 'a',
                            target: '_blank',
                            href: "https://chromedevtools.github.io/devtools-protocol/tot/Page/#method-printToPDF", // suggesting the link mentioned in https://github.com/chrome-php/chrome/blob/6bc3ad7de6d17a3beedd5c114850ac6fcf24f28b/src/PageUtils/PagePdf.php#L24-L43
                        }
                    },
                    {
                        xtype: 'textarea',
                        width: 850,
                        height: 200,
                        fieldLabel: t("web2print_chromium_settings"),
                        name: 'chromiumSettings',
                        value: this.getValue("chromiumSettings")
                    },
                    {
                        xtype: "displayfield",
                        fieldLabel: t("web2print_chromium_requirements"),
                        name: 'requirements',
                        width: 600,
                        value: t('web2print_chromium_requirements_documentation'),
                        autoEl:{
                            tag: 'a',
                            target: '_blank',
                            href: "https://github.com/chrome-php/chrome#requirements",
                        }
                    },
                    {
                        xtype: "displayfield",
                        fieldLabel: t("web2print_chromium_documentation_additions"),
                        name: 'additions',
                        width: 850,
                        value: t('web2print_chromium_documentation_additions_text'),
                    },
                    {
                        xtype: "displayfield",
                        fieldLabel: t("web2print_json_converter"),
                        name: 'json_converter',
                        width: 600,
                        value: t('web2print_json_converter_link'),
                        autoEl:{
                            tag: 'a',
                            target: '_blank',
                            href: "https://jsonformatter.org/",
                        }
                    }
                ]
            });

            this.gotenbergSettings = Ext.create("Ext.form.FieldSet", {
                title: t('web2print_gotenberg_settings'),
                collapsible: true,
                collapsed: false,
                autoHeight: true,
                hidden: this.getValue("generalTool") != 'gotenberg',
                defaultType: 'textfield',
                defaults: {width: 450},
                items: [
                    {
                        xtype: "displayfield",
                        fieldLabel: t("web2print_gotenberg_documentation_additions"),
                        name: 'additions',
                        width: 850,
                        value: t('web2print_gotenberg_documentation_additions_text'),
                    },{
                        xtype: 'textfield',
                        width: 650,
                        fieldLabel: t("web2print_hostURL"),
                        name: 'gotenbergHostUrl',
                        value: this.getValue("gotenbergHostUrl"),
                        emptyText: "http://nginx:80"
                    }, {
                        xtype: 'textarea',
                        width: 850,
                        height: 200,
                        fieldLabel: t("web2print_gotenberg_settings"),
                        name: 'gotenbergSettings',
                        value: this.getValue("gotenbergSettings")
                    },{
                        xtype: "displayfield",
                        fieldLabel: t("web2print_gotenberg_documentation"),
                        name: 'documentation',
                        width: 600,
                        value: t('web2print_gotenberg_options_documentation'),
                        autoEl:{
                            tag: 'a',
                            target: '_blank',
                            href: "https://gotenberg.dev/docs/modules/chromium#routes",
                        }
                    },{
                        xtype: "displayfield",
                        fieldLabel: t("web2print_json_converter"),
                        name: 'json_converter',
                        width: 600,
                        value: t('web2print_json_converter_link'),
                        autoEl:{
                            tag: 'a',
                            target: '_blank',
                            href: "https://jsonformatter.org/",
                        }
                    }
                ]
            });

            this.layout = Ext.create('Ext.form.Panel', {
                bodyStyle: 'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                forceLayout: true,
                defaults: {
                    forceLayout: true
                },
                fieldDefaults: {
                    labelWidth: 250
                },
                buttons: [
                    {
                        text: t("test"),
                        handler: this.test.bind(this),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/approval.svg"
                    },
                    {
                        text: t("save"),
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply",
                        disabled: !pimcore.settings['web2print-writeable']
                    }
                ],
                items: [
                    {
                        xtype: 'fieldset',
                        title: t('general'),
                        collapsible: false,
                        autoHeight: true,
                        defaultType: 'textfield',
                        defaults: {width: 450},
                        items: [
                            {
                                fieldLabel: t("web2print_tool"),
                                xtype: "combo",
                                width: 600,
                                editable: false,
                                name: "generalTool",
                                value: this.getValue("generalTool"),
                                store: [
                                    ["pdfreactor", "PDFreactor"],
                                    ["chromium", "Chromium"],
                                    ["gotenberg", "Gotenberg Chromium"],
                                ],
                                mode: "local",
                                triggerAction: "all",
                                listeners: {
                                    select: function(combo, record) {
                                        this.pdfReactorSettings.hide();
                                        this.gotenbergSettings.hide();
                                        this.chromiumSettings.hide();

                                        if(combo.getValue() == "pdfreactor") {
                                            this.pdfReactorSettings.show();
                                        } else if(combo.getValue() == "chromium") {
                                            this.chromiumSettings.show();
                                        } else if(combo.getValue() == "gotenberg") {
                                            this.gotenbergSettings.show();
                                        }

                                    }.bind(this)
                                }
                            },
                            {
                                fieldLabel: t("web2print_save_mode"),
                                xtype: "combo",
                                width: 600,
                                editable: false,
                                name: "generalDocumentSaveMode",
                                value: this.getValue("generalDocumentSaveMode"),
                                store: [
                                    ["default", "default"],
                                    ["cleanup", "cleanup"]
                                ],
                                mode: "local",
                                triggerAction: "all"
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('web2print_save_mode_txt'),
                                cls: "pimcore_extra_label_bottom"
                            }
                        ]
                    }
                    , this.pdfReactorSettings, this.gotenbergSettings, this.chromiumSettings
                ]
            });

            this.panel.add(this.layout);

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem(this.panel);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_settings_web2print");
    },

    getValues: function () {
        var values = this.layout.getForm().getFieldValues();
        Object.keys(values).forEach(function (key) {
            if (key.includes('displayfield')) {
                delete values[key];
            }
        });
        return values;
    },

    save: function () {
        var values = this.getValues();

        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_web2print_settings_setweb2print'),
            method: "PUT",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");

                        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                            if (buttonValue == "yes") {
                                window.location.reload();
                            }
                        }.bind(this));
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("saving_failed"),
                            "error", t(res.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }
        });
    },

    test: function () {
        window.open(Routing.generate('pimcore_bundle_web2print_settings_testweb2print'), "_blank");
    }
});
