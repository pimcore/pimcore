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
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.settings.system");
pimcore.settings.system = Class.create({

    initialize: function () {

        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_getsystem'),
            success: function (response) {

                this.data = Ext.decode(response.responseText);

                //valid languages
                try {
                    this.languagesStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        data: this.data.config,
                        proxy: {
                            type: 'memory',
                            reader: {
                                rootProperty: 'languages'
                            }
                        },
                        fields: ['language', 'display']
                    });
                } catch (e2) {
                    this.languagesStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        fields: ['language', 'display']
                    });
                }


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
                id: "pimcore_settings_system",
                title: t("system_settings"),
                iconCls: "pimcore_icon_system",
                border: false,
                layout: "fit",
                closable: true
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("settings_system");
            }.bind(this));


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
                        text: t("save"),
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    }
                ],
                items: [
                    {
                        xtype: 'fieldset',
                        title: t('appearance_and_branding'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        defaultType: 'textfield',
                        defaults: {width: 450},
                        items: [{
                            xtype: 'fieldset',
                            title: t('colors'),
                            collapsible: false,
                            width: "100%",
                            autoHeight: true,
                            items: [{
                                xtype: "container",
                                html: t('color_description'),
                                style: "margin-bottom:10px;"
                            }, {
                                xtype: "textfield",
                                fieldLabel: t('login_screen'),
                                width: 330,
                                value: this.getValue("branding.color_login_screen"),
                                name: 'branding.color_login_screen'
                            }, {
                                xtype: "textfield",
                                fieldLabel: t('admin_interface'),
                                width: 330,
                                value: this.getValue("branding.color_admin_interface"),
                                name: 'branding.color_admin_interface'
                            }, {
                                xtype: "checkbox",
                                boxLabel: t('invert_colors_on_login_screen'),
                                width: 330,
                                checked: this.getValue("branding.login_screen_invert_colors"),
                                name: 'branding.login_screen_invert_colors'
                            }]
                        }, {
                            xtype: 'fieldset',
                            title: t('custom_logo'),
                            collapsible: false,
                            width: "100%",
                            autoHeight: true,
                            items: [{
                                xtype: "container",
                                html: t('branding_logo_description'),
                                style: "margin-bottom:10px;"
                            }, {
                                xtype: "container",
                                id: "pimcore_custom_branding_logo",
                                html: '<img src="'+Routing.generate('pimcore_settings_display_custom_logo')+'" />',
                            }, {
                                xtype: "button",
                                text: t("upload"),
                                iconCls: "pimcore_icon_upload",
                                handler: function () {
                                    pimcore.helpers.uploadDialog(Routing.generate('pimcore_admin_settings_uploadcustomlogo'), null,
                                        function () {
                                            var cont = Ext.getCmp("pimcore_custom_branding_logo");
                                            var date = new Date();
                                            cont.update('<img src="'+Routing.generate('pimcore_settings_display_custom_logo', {'_dc': date.getTime()})+'" />');
                                        }.bind(this));
                                }.bind(this),
                                flex: 1
                            }, {
                                xtype: "button",
                                text: t("delete"),
                                iconCls: "pimcore_icon_delete",
                                handler: function () {
                                    Ext.Ajax.request({
                                        url: Routing.generate('pimcore_admin_settings_deletecustomlogo'),
                                        method: "DELETE",
                                        success: function (response) {
                                            var cont = Ext.getCmp("pimcore_custom_branding_logo");
                                            var date = new Date();
                                            cont.update('<img src="' + Routing.generate('pimcore_settings_display_custom_logo', {'_dc': date.getTime()}) + '" />');
                                        }
                                    });
                                }.bind(this),
                                flex: 1
                            }]
                        }, {
                            xtype: 'fieldset',
                            title: t('custom_login_background_image'),
                            collapsible: false,
                            width: "100%",
                            autoHeight: true,
                            items: [{
                                fieldLabel: t("url_to_custom_image_on_login_screen"),
                                xtype: "textfield",
                                name: "general.loginscreencustomimage",
                                value: this.getValue("general.login_screen_custom_image")
                            }]
                        }]
                    }
                    ,
                    {
                        xtype: 'fieldset',
                        title: t('localization_and_internationalization') + " (i18n/l10n)",
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 150,
                        defaultType: 'textfield',
                        defaults: {width: 300},
                        items: [{
                            xtype: 'combo',
                            fieldLabel: t('language_admin'),
                            typeAhead: true,
                            value: this.getValue("general.language"),
                            queryMode: 'local',
                            mode: 'local',
                            listWidth: 100,
                            width: 500,
                            //editable: true,     // If typeAhead is enabled the combo must be editable: true -- please change one of those settings.
                            store: pimcore.globalmanager.get("pimcorelanguages"),
                            displayField: 'display',
                            valueField: 'language',
                            forceSelection: true,
                            triggerAction: 'all',
                            name: 'general.language'
                        }, {
                            xtype: "displayfield",
                            hideLabel: true,
                            width: 600,
                            value: t('valid_languages_frontend_description') + " <br /><br />" + t('delete_language_note'),
                            cls: "pimcore_extra_label_bottom"
                        },
                            {
                                xtype: "fieldset",
                                layout: "hbox",
                                border: false,
                                style: "border-top: none !important",
                                padding: 0,
                                width: 600,
                                items: [{
                                    labelWidth: 150,
                                    fieldLabel: t("add_language"),
                                    xtype: "combo",
                                    id: "system_settings_general_languageSelection",
                                    triggerAction: 'all',
                                    queryMode: 'local',
                                    store: this.languagesStore,
                                    displayField: 'display',
                                    valueField: 'language',
                                    forceSelection: true,
                                    typeAhead: true,
                                    anyMatch: true,
                                    width: 450
                                }, {
                                    xtype: "button",
                                    iconCls: "pimcore_icon_add",
                                    handler: function () {
                                        var combo = Ext.getCmp("system_settings_general_languageSelection");
                                        this.addLanguage(combo.getValue());
                                    }.bind(this)
                                }]
                            }, {
                                xtype: "hidden",
                                id: "system_settings_general_validLanguages",
                                name: 'general.validLanguages',
                                value: this.getValue("general.valid_languages")
                            }, {
                                xtype: "hidden",
                                id: "system_settings_general_defaultLanguage",
                                name: "general.defaultLanguage",
                                value: this.getValue("general.default_language")
                            }, {
                                xtype: "container",
                                width: 450,
                                style: "margin-top: 20px;",
                                id: "system_settings_general_languageContainer",
                                items: [],
                                listeners: {
                                    beforerender: function () {
                                        // add existing language entries
                                        var locales = this.getValue("general.valid_languages").split(",");
                                        if (locales && locales.length > 0) {
                                            Ext.each(locales, this.addLanguage.bind(this));
                                        }
                                    }.bind(this)
                                }
                            }]
                    },
                    {
                        xtype: 'fieldset',
                        title: "Debug",
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 300,
                        defaultType: 'textfield',
                        defaults: {width: 600},
                        items: [{
                            boxLabel: t("debug_admin_translations"),
                            xtype: "checkbox",
                            name: "general.debug_admin_translations",
                            checked: this.getValue("general.debug_admin_translations")
                        }, {
                            xtype: 'textfield',
                            width: 650,
                            fieldLabel: t("email_debug_addresses") + "(CSV)" + ' <span style="color:red;">*</span>',
                            name: 'email.debug.emailAddresses',
                            value: this.getValue("email.debug.email_addresses"),
                            emptyText: "john@doe.com,jane@doe.com"
                        }]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('website'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 500},
                        items: [
                            {
                                fieldLabel: t("main_domain"),
                                name: "general.domain",
                                value: this.getValue("general.domain")
                            },
                            {
                                xtype: "checkbox",
                                boxLabel: t("redirect_unknown_domains_to_main_domain"),
                                name: "general.redirect_to_maindomain",
                                checked: this.getValue("general.redirect_to_maindomain")
                            },
                            {
                                fieldLabel: t("error_page") + " (" + t("default") + ")",
                                name: "documents.error_pages.default",
                                fieldCls: "input_drop_target",
                                value: this.getValue("documents.error_pages.default"),
                                width: 600,
                                xtype: "textfield",
                                listeners: {
                                    "render": function (el) {
                                        new Ext.dd.DropZone(el.getEl(), {
                                            reference: this,
                                            ddGroup: "element",
                                            getTargetFromEvent: function (e) {
                                                return this.getEl();
                                            }.bind(el),

                                            onNodeOver: function (target, dd, e, data) {
                                                if (data.records.length == 1 && data.records[0].data.elementType == "document") {
                                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                                }
                                            },

                                            onNodeDrop: function (target, dd, e, data) {
                                                if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                    var record = data.records[0];
                                                    var data = record.data;

                                                    if (data.elementType == "document") {
                                                        this.setValue(data.path);
                                                        return true;
                                                    }
                                                }
                                                return false;
                                            }.bind(el)
                                        });
                                    }
                                }
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('documents'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items: [
                            {
                                fieldLabel: t('store_version_history_in_days'),
                                name: 'documents.versions.days',
                                value: this.getValue("documents.versions.days"),
                                xtype: "numberfield",
                                id: "system_settings_documents_versions_days",
                                enableKeyEvents: true,
                                listeners: {
                                    "change": this.checkVersionInputs.bind(this, "documents", "days"),
                                    "afterrender": this.checkVersionInputs.bind(this, "documents", "days", "init")
                                },
                                minValue: 0
                            },
                            {
                                fieldLabel: t('store_version_history_in_steps'),
                                name: 'documents.versions.steps',
                                value: this.getValue("documents.versions.steps"),
                                xtype: "numberfield",
                                id: "system_settings_documents_versions_steps",
                                enableKeyEvents: true,
                                listeners: {
                                    "change": this.checkVersionInputs.bind(this, "documents", "steps"),
                                    "afterrender": this.checkVersionInputs.bind(this, "documents", "steps", "init")
                                },
                                minValue: 0
                            }
                        ]
                    }
                    ,
                    {
                        xtype: 'fieldset',
                        title: t('data_objects'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items: [
                            {
                                fieldLabel: t('store_version_history_in_days'),
                                name: 'objects.versions.days',
                                value: this.getValue("objects.versions.days"),
                                xtype: "numberfield",
                                id: "system_settings_objects_versions_days",
                                enableKeyEvents: true,
                                listeners: {
                                    "change": this.checkVersionInputs.bind(this, "objects", "days"),
                                    "afterrender": this.checkVersionInputs.bind(this, "objects", "days", "init")
                                },
                                minValue: 0
                            },
                            {
                                fieldLabel: t('store_version_history_in_steps'),
                                name: 'objects.versions.steps',
                                value: this.getValue("objects.versions.steps"),
                                xtype: "numberfield",
                                id: "system_settings_objects_versions_steps",
                                enableKeyEvents: true,
                                listeners: {
                                    "change": this.checkVersionInputs.bind(this, "objects", "steps"),
                                    "afterrender": this.checkVersionInputs.bind(this, "objects", "steps", "init")
                                },
                                minValue: 0
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('assets'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 600},
                        items: [
                            {
                                fieldLabel: t('store_version_history_in_days'),
                                name: 'assets.versions.days',
                                value: this.getValue("assets.versions.days"),
                                xtype: "numberfield",
                                id: "system_settings_assets_versions_days",
                                enableKeyEvents: true,
                                listeners: {
                                    "change": this.checkVersionInputs.bind(this, "assets", "days"),
                                    "afterrender": this.checkVersionInputs.bind(this, "assets", "days", "init")
                                },
                                width: 400,
                                minValue: 0
                            },
                            {
                                fieldLabel: t('store_version_history_in_steps'),
                                name: 'assets.versions.steps',
                                value: this.getValue("assets.versions.steps"),
                                xtype: "numberfield",
                                id: "system_settings_assets_versions_steps",
                                enableKeyEvents: true,
                                listeners: {
                                    "change": this.checkVersionInputs.bind(this, "assets", "steps"),
                                    "afterrender": this.checkVersionInputs.bind(this, "assets", "steps", "init")
                                },
                                width: 400,
                                minValue: 0
                            },
                            {
                                boxLabel: t("hide_edit_image_tab"),
                                xtype: "checkbox",
                                name: "assets.hide_edit_image",
                                checked: this.getValue("assets.hide_edit_image")
                            },
                            {
                                boxLabel: t("disable_tree_preview"),
                                xtype: "checkbox",
                                name: "assets.disable_tree_preview",
                                checked: this.getValue("assets.disable_tree_preview")
                            }
                        ]
                    }
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
        tabPanel.setActiveItem("pimcore_settings_system");
    },

    save: function () {

        this.layout.mask();

        var values = this.layout.getForm().getFieldValues();

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_setsystem'),
            method: "PUT",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {

                this.layout.unmask();

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
            }.bind(this)
        });
    },


    emailMethodSelected: function (type, combo) {

        var smtpFieldSet = combo.ownerCt.getComponent(type + "SmtpSettings");

        if (combo.getValue() == "smtp") {
            smtpFieldSet.show();
        } else {
            smtpFieldSet.hide();
            Ext.each(smtpFieldSet.query("textfield"), function (item) {
                item.setValue("");
            });
        }

        pimcore.layout.refresh();

    },

    smtpAuthSelected: function (type, combo) {

        var username = combo.ownerCt.getComponent(type + "_username");
        var pass = combo.ownerCt.getComponent(type + "_password");

        if (!combo.getValue()) {
            username.hide();
            pass.hide();
            username.setValue("");
            pass.setValue("");
        } else {
            username.show();
            pass.show();
        }
    },

    checkVersionInputs: function (elementType, type, field, event) {

        var mappingOpposite = {
            steps: "days",
            days: "steps"
        };

        var value = Ext.getCmp("system_settings_" + elementType + "_versions_" + type).getValue();

        if (event == "init") {
            if (!value) {
                return;
            }
        }

        if (value !== null) {
            Ext.getCmp("system_settings_" + elementType + "_versions_" + mappingOpposite[type]).disable();
            Ext.getCmp("system_settings_" + elementType + "_versions_" + mappingOpposite[type]).setValue("");
        } else {
            Ext.getCmp("system_settings_" + elementType + "_versions_" + mappingOpposite[type]).enable();
        }
    },

    addLanguage: function (language) {

        if (empty(language)) {
            return;
        }

        // find the language entry in the store, because "language" can be the display value too
        var index = this.languagesStore.findExact("language", language);
        if (index < 0) {
            index = this.languagesStore.findExact("display", language)
        }

        if (index >= 0) {

            var rec = this.languagesStore.getAt(index);
            language = rec.get("language");

            // add the language to the hidden field used to send the languages to the action
            var languageField = Ext.getCmp("system_settings_general_validLanguages");
            var addedLanguages = languageField.getValue().split(",");
            if (!in_array(language, addedLanguages)) {
                addedLanguages.push(language);
                languageField.setValue(addedLanguages.join(","));
            }

            // add the language to the container, so that further settings for the language can be set (eg. fallback, ...)
            var container = Ext.getCmp("system_settings_general_languageContainer");
            var lang = container.getComponent(language);
            if (lang) {
                return;
            }

            container.add({
                xtype: "fieldset",
                itemId: language,
                title: rec.get("display"),
                labelWidth: 250,
                width: 590,
                style: "position: relative;",
                items: [{
                    xtype: "textfield",
                    width: 450,
                    fieldLabel: t("fallback_languages"),
                    name: "general.fallbackLanguages." + language,
                    value: this.getValue("general.fallback_languages." + language)
                }, {
                    xtype: "radio",
                    name: "general.defaultLanguageRadio",
                    boxLabel: t("default_language"),
                    checked: this.getValue("general.default_language") == language || (!this.getValue("general.default_language") && container.items.length == 0 ),
                    listeners: {
                        change: function (el, checked) {
                            if (checked) {
                                var defaultLanguageField = Ext.getCmp("system_settings_general_defaultLanguage");
                                defaultLanguageField.setValue(language);
                            }
                        }.bind(this)
                    }
                }, {
                    xtype: "button",
                    title: t("delete"),
                    iconCls: "pimcore_icon_delete",
                    style: "position:absolute; right: 5px; top:12px;",
                    handler: this.removeLanguage.bind(this, language)
                }]
            });
            container.updateLayout();
        }
    },

    removeLanguage: function (language) {

        // remove the language out of the hidden field
        var languageField = Ext.getCmp("system_settings_general_validLanguages");
        var addedLanguages = languageField.getValue().split(",");
        if (in_array(language, addedLanguages)) {
            addedLanguages.splice(array_search(language, addedLanguages), 1);
            languageField.setValue(addedLanguages.join(","));
        }

        // remove the default language from hidden field
        var defaultLanguageField = Ext.getCmp("system_settings_general_defaultLanguage");
        if (defaultLanguageField.getValue() == language) {
            defaultLanguageField.setValue("");
        }

        // remove the language from the container
        var container = Ext.getCmp("system_settings_general_languageContainer");
        var lang = container.getComponent(language);
        if (lang) {
            container.remove(lang);
        }
        container.updateLayout();
    }

});
