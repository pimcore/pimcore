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

pimcore.registerNS("pimcore.settings.system");
pimcore.settings.system = Class.create({

    initialize: function () {

        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: "/admin/settings/get-system",
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

            // debug
            if (this.data.values.general.debug) {
                this.data.values.general.debug = true;
            }

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
                        title: t('general'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        defaultType: 'textfield',
                        defaults: {width: 450},
                        items: [
                            {
                                fieldLabel: t('timezone'),
                                name: 'general.timezone',
                                xtype: "combo",
                                forceSelection: true,
                                triggerAction: 'all',
                                store: this.data.config.timezones,
                                value: this.getValue("general.timezone"),
                                width: 600
                            },
                            {
                                fieldLabel: t("additional_path_variable") + " (" + t(this.data.config.path_separator) + " " + t("separated") + ") (/x/y" + this.data.config.path_separator + "/foo/bar)",
                                xtype: "textfield",
                                name: "general.path_variable",
                                value: this.getValue("general.path_variable"),
                                width: 600
                            },
                            {
                                xtype: 'combo',
                                fieldLabel: t('language_admin'),
                                typeAhead: true,
                                value: this.getValue("general.language"),
                                queryMode: 'local',
                                listWidth: 100,
                                //editable: true,     // If typeAhead is enabled the combo must be editable: true -- please change one of those settings.
                                store: pimcore.globalmanager.get("pimcorelanguages"),
                                displayField: 'display',
                                valueField: 'language',
                                forceSelection: true,
                                triggerAction: 'all',
                                name: 'general.language'
                            },
                            {
                                fieldLabel: t("url_to_custom_image_on_login_screen"),
                                xtype: "textfield",
                                name: "general.loginscreencustomimage",
                                value: this.getValue("general.loginscreencustomimage")
                            },
                            {
                                fieldLabel: t('turn_off_anonymous_usage_submissions'),
                                xtype: "checkbox",
                                name: "general.disableusagestatistics",
                                checked: this.getValue("general.disableusagestatistics")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('usage_statistics_info'),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                fieldLabel: t("instance_identifier"),
                                xtype: "textfield",
                                name: "general.instanceIdentifier",
                                value: this.getValue("general.instanceIdentifier"),
                                width: 450
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('instance_identifier_info'),
                                cls: "pimcore_extra_label_bottom"
                            }
                        ]
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
                            xtype: "displayfield",
                            hideLabel: true,
                            width: 600,
                            value: t('valid_languages_frontend_description'),
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
                                value: this.getValue("general.validLanguages")
                            }, {
                                xtype: "hidden",
                                id: "system_settings_general_defaultLanguage",
                                name: "general.defaultLanguage",
                                value: this.getValue("general.defaultLanguage")
                            }, {
                                xtype: "container",
                                width: 450,
                                style: "margin-top: 20px;",
                                id: "system_settings_general_languageContainer",
                                items: [],
                                listeners: {
                                    beforerender: function () {
                                        // add existing language entries
                                        var locales = this.getValue("general.validLanguages").split(",");
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
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 600},
                        items: [
                            {
                                fieldLabel: "DEBUG",
                                xtype: "checkbox",
                                name: "general.debug",
                                id: "system_settings_general_debug",
                                checked: this.getValue("general.debug"),
                                listeners: {
                                    change: function (el, checked) {
                                        // set the current client ip to the debug ip field
                                        var ipField = Ext.getCmp("system_settings_general_debug_ip");
                                        if (checked && empty(ipField.getValue())) {
                                            ipField.setValue(this.data.config.client_ip);
                                        }
                                    }.bind(this)
                                }
                            },
                            {
                                fieldLabel: t("only_for_ip"),
                                xtype: "textfield",
                                id: "system_settings_general_debug_ip",
                                name: "general.debug_ip",
                                width: 600,
                                value: this.getValue("general.debug_ip")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t("debug_description"),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b>" + t("log_applicationlog") + "</b>"
                            },
                            {
                                fieldLabel: t("log_config_send_summary_per_mail"),
                                xtype: "checkbox",
                                name: "applicationlog.mail_notification.send_log_summary",
                                checked: this.getValue("applicationlog.mail_notification.send_log_summary")
                            },
                            {
                                fieldLabel: t("log_config_filter_priority"),
                                xtype: "combo",
                                name: "applicationlog.mail_notification.filter_priority",
                                value: this.getValue("applicationlog.mail_notification.filter_priority"),
                                store: [
                                    [7, "DEBUG"],
                                    [6, "INFO"],
                                    [5, "NOTICE"],
                                    [4, "WARNING"],
                                    [3, "ERROR"],
                                    [2, "CRITICAL"],
                                    [1, "ALERT"],
                                    [0, "EMERG"]
                                ],
                                mode: "local",
                                editable: false,
                                triggerAction: "all"
                            },
                            {
                                fieldLabel: t('log_config_mail_receiver'),
                                name: 'applicationlog.mail_notification.mail_receiver',
                                value: this.getValue("applicationlog.mail_notification.mail_receiver")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('log_config_mail_receiver_description'),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                fieldLabel: t('log_config_archive_treshold'),
                                name: 'applicationlog.archive_treshold',
                                value: this.getValue("applicationlog.archive_treshold") ? this.getValue("applicationlog.archive_treshold") : '30'
                            },
                            {
                                fieldLabel: t('log_config_archive_alternative_database'),
                                name: 'applicationlog.archive_alternative_database',
                                value: this.getValue("applicationlog.archive_alternative_database")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('log_config_archive_description'),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                fieldLabel: t("debug_admin_translations"),
                                xtype: "checkbox",
                                name: "general.debug_admin_translations",
                                checked: this.getValue("general.debug_admin_translations")
                            },
                            {
                                fieldLabel: 'DEV-Mode (<span style="color:red;font-weight:bold;">'
                                + t('do_not_use_in_production') + '</span>)',
                                xtype: "checkbox",
                                name: "general.devmode",
                                id: "system_settings_general_devmode",
                                checked: this.getValue("general.devmode")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('dev_mode_description'),
                                cls: "pimcore_extra_label_bottom"
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('email_settings'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        items: [{
                            xtype: "fieldset",
                            title: t("delivery_settings"),
                            collapsible: false,
                            defaults: {width: 600},
                            labelWidth: 250,
                            defaultType: 'textfield',
                            autoHeight: true,
                            items: [
                                {
                                    xtype: 'textfield',
                                    width: 650,
                                    fieldLabel: t("email_debug_addresses") + "(CSV)" + ' <span style="color:red;">*</span>',
                                    name: 'email.debug.emailAddresses',
                                    value: this.getValue("email.debug.emailaddresses"),
                                    emptyText: "john@doe.com,jane@doe.com"
                                },
                                {
                                    fieldLabel: t("email_method") + ' <span style="color:red;">*</span>',
                                    xtype: "combo",
                                    name: "email.method",
                                    value: this.getValue("email.method"),
                                    store: [
                                        ["mail", "mail"],
                                        ["smtp", "smtp"]
                                    ],
                                    listeners: {
                                        select: this.emailMethodSelected.bind(this, "email")
                                    },
                                    mode: "local",
                                    editable: false,
                                    forceSelection: true,
                                    triggerAction: "all"
                                },
                                {
                                    xtype: "fieldset",
                                    title: "SMTP",
                                    width: 600,
                                    itemId: "emailSmtpSettings",
                                    defaultType: 'textfield',
                                    hidden: (this.getValue("email.method") == "smtp") ? false : true,
                                    items: [{
                                        fieldLabel: t("email_smtp_host") + ' <span style="color:red;">*</span>',
                                        name: "email.smtp.host",
                                        value: this.getValue("email.smtp.host")
                                    },
                                        {
                                            fieldLabel: t("email_smtp_ssl"),
                                            xtype: "combo",
                                            width: 425,
                                            name: "email.smtp.ssl",
                                            value: this.getValue("email.smtp.ssl"),
                                            store: [
                                                ["", t('no_ssl')],
                                                ["tls", "TLS"],
                                                ["ssl", "SSL"]
                                            ],
                                            mode: "local",
                                            editable: false,
                                            forceSelection: true,
                                            triggerAction: "all"
                                        },
                                        {
                                            fieldLabel: t("email_smtp_port"),
                                            name: "email.smtp.port",
                                            value: this.getValue("email.smtp.port")
                                        },
                                        {
                                            fieldLabel: t("email_smtp_name"),
                                            name: "email.smtp.name",
                                            value: this.getValue("email.smtp.name")
                                        },
                                        {
                                            fieldLabel: t("email_smtp_auth_method"),
                                            xtype: "combo",
                                            width: 425,
                                            name: "email.smtp.auth.method",
                                            value: this.getValue("email.smtp.auth.method"),
                                            store: [
                                                ["", t('no_authentication')],
                                                ["login", "LOGIN"],
                                                ["plain", "PLAIN"],
                                                ["crammd5", "CRAM-MD5"]
                                            ],
                                            mode: "local",
                                            editable: false,
                                            forceSelection: true,
                                            triggerAction: "all",
                                            listeners: {
                                                select: this.smtpAuthSelected.bind(this, "email")
                                            }
                                        },
                                        {
                                            fieldLabel: t("email_smtp_auth_username"),
                                            name: "email.smtp.auth.username",
                                            itemId: "email_username",
                                            hidden: (this.getValue("email.smtp.auth.method").length > 1) ? false : true,
                                            value: this.getValue("email.smtp.auth.username")
                                        },
                                        {
                                            fieldLabel: t("email_smtp_auth_password"),
                                            name: "email.smtp.auth.password",
                                            inputType: "password",
                                            itemId: "email_password",
                                            hidden: (this.getValue("email.smtp.auth.method").length > 1) ? false : true,
                                            value: this.getValue("email.smtp.auth.password")
                                        }
                                    ]
                                },
                                {
                                    fieldLabel: t("email_senderemail") + ' <span style="color:red;">*</span>',
                                    name: "email.sender.email",
                                    value: this.getValue("email.sender.email")
                                },
                                {
                                    fieldLabel: t("email_sendername"),
                                    name: "email.sender.name",
                                    value: this.getValue("email.sender.name")
                                },
                                {
                                    fieldLabel: t("email_returnemail"),
                                    name: "email.return.email",
                                    value: this.getValue("email.return.email")
                                },
                                {
                                    fieldLabel: t("email_returnname"),
                                    name: "email.return.name",
                                    value: this.getValue("email.return.name")
                                }
                            ]
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
                                fieldLabel: t("redirect_unknown_domains_to_main_domain"),
                                name: "general.redirect_to_maindomain",
                                checked: this.getValue("general.redirect_to_maindomain")
                            },
                            {
                                fieldLabel: t("default_error_page"),
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
                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                            },

                                            onNodeDrop: function (target, dd, e, data) {
                                                var record = data.records[0];
                                                var data = record.data;

                                                if (data.elementType == "document") {
                                                    this.setValue(data.path);
                                                    return true;
                                                }
                                                return false;
                                            }.bind(el)
                                        });
                                    }
                                }
                            }, {
                                xtype: "checkbox",
                                fieldLabel: t("show_cookie_notice"),
                                name: "general.show_cookie_notice",
                                checked: this.getValue("general.show_cookie_notice")
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('mysql_database'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items: [
                            {
                                fieldLabel: t('adapter'),
                                disabled: true,
                                name: 'database.adapter',
                                value: this.getValue("database.adapter")
                            }, {
                                fieldLabel: t('host'),
                                disabled: true,
                                name: 'database.params.host',
                                value: this.getValue("database.params.host")
                            },
                            {
                                fieldLabel: t('username'),
                                disabled: true,
                                name: 'database.params.username',
                                value: this.getValue("database.params.username")
                            },
                            {
                                fieldLabel: t('password'),
                                disabled: true,
                                inputType: "password",
                                name: 'database.params.password',
                                value: this.getValue("database.params.password")
                            },
                            {
                                fieldLabel: t('database_name'),
                                disabled: true,
                                name: 'database.params.dbname',
                                value: this.getValue("database.params.dbname")
                            },
                            {
                                fieldLabel: t('database_port'),
                                disabled: true,
                                name: 'database.params.port',
                                value: this.getValue("database.params.port")
                            }
                        ]
                    }
                    ,
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
                            },
                            {
                                fieldLabel: t('default_controller'),
                                name: 'documents.default_controller',
                                value: this.getValue("documents.default_controller")
                            },
                            {
                                fieldLabel: t('default_action'),
                                name: 'documents.default_action',
                                value: this.getValue("documents.default_action")
                            }, {
                                xtype: "displayfield",
                                hideLabel: true,
                                style: "margin-top: 10px;",
                                width: 600,
                                value: "&nbsp;"
                            }, {
                                fieldLabel: t('create_redirect_for_moved_renamed_page'),
                                xtype: "checkbox",
                                name: "documents.createredirectwhenmoved",
                                checked: this.getValue("documents.createredirectwhenmoved")
                            }, {
                                fieldLabel: t("allow_trailing_slash_for_documents"),
                                xtype: "combo",
                                name: "documents.allowtrailingslash",
                                value: this.getValue("documents.allowtrailingslash"),
                                store: [
                                    ["", t("yes")],
                                    ["no", t("no")]
                                ],
                                mode: "local",
                                editable: false,
                                forceSelection: true,
                                triggerAction: "all"
                            }, {
                                fieldLabel: t("generate_previews"),
                                xtype: "checkbox",
                                name: "documents.generatepreview",
                                checked: this.getValue("documents.generatepreview")
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
                                fieldLabel: t('absolute_path_to_icc_rgb_profile') + " (imagick)",
                                name: 'assets.icc_rgb_profile',
                                value: this.getValue("assets.icc_rgb_profile")
                            },
                            {
                                fieldLabel: t('absolute_path_to_icc_cmyk_profile') + " (imagick)",
                                name: 'assets.icc_cmyk_profile',
                                value: this.getValue("assets.icc_cmyk_profile")
                            },
                            {
                                fieldLabel: t("hide_edit_image_tab"),
                                xtype: "checkbox",
                                name: "assets.hide_edit_image",
                                checked: this.getValue("assets.hide_edit_image")
                            },
                            {
                                fieldLabel: t("disable_tree_preview"),
                                xtype: "checkbox",
                                name: "assets.disable_tree_preview",
                                checked: this.getValue("assets.disable_tree_preview")
                            }
                        ]
                    }
                    ,
                    {
                        xtype: 'fieldset',
                        title: t('google_credentials_and_api_keys'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 800},
                        items: [
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 800,
                                value: "<b>" + t('google_api_key_service') + "</b>",
                                cls: "pimcore_extra_label"
                            }, {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 800,
                                value: t("google_api_access_description"),
                                cls: "pimcore_extra_label"
                            },
                            {
                                fieldLabel: t('client_id'),
                                name: 'services.google.client_id',
                                value: this.getValue("services.google.client_id"),
                                width: 800
                            },
                            {
                                fieldLabel: t('email'),
                                name: 'services.google.email',
                                value: this.getValue("services.google.email"),
                                width: 800
                            }, {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 800,
                                value: this.data.config.google_private_key_exists ?
                                    t("google_api_private_key_installed")
                                    : ('<span style="color:red;">'
                                + t("google_api_key_missing")
                                + " <br />" + this.data.config.google_private_key_path
                                + '</span>'),
                                cls: "pimcore_extra_label"
                            }, {
                                xtype: "displayfield",
                                hideLabel: true,
                                style: "margin-top: 10px;",
                                width: 800,
                                value: "&nbsp;"
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 800,
                                value: "<b>" + t('google_api_key_simple') + "</b>",
                                cls: "pimcore_extra_label"
                            },
                            {
                                fieldLabel: t('server_api_key'),
                                name: 'services.google.simpleapikey',
                                value: this.getValue("services.google.simpleapikey"),
                                width: 850
                            },
                            {
                                fieldLabel: t('browser_api_key'),
                                name: 'services.google.browserapikey',
                                value: this.getValue("services.google.browserapikey"),
                                width: 850
                            }
                        ]
                    }
                    ,
                    {
                        xtype: 'fieldset',
                        title: t('full_page_cache'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 600},
                        items: [
                            {
                                fieldLabel: t("cache_enabled"),
                                xtype: "checkbox",
                                name: "cache.enabled",
                                checked: this.getValue("cache.enabled")
                            },
                            {
                                fieldLabel: t('lifetime'),
                                xtype: "numberfield",
                                name: 'cache.lifetime',
                                value: this.getValue("cache.lifetime"),
                                width: 350,
                                step: 100
                            },
                            {
                                xtype: "displayfield",
                                width: 600,
                                value: t("outputcache_lifetime_description"),
                                cls: "pimcore_extra_label_bottom"
                            }
                            ,
                            {
                                xtype: 'tagfield',
                                width: "100%",
                                resizable: true,
                                minChars: 2,
                                store: Ext.create('Ext.data.JsonStore', {
                                    proxy: {
                                        type: 'memory'
                                    },
                                    fields: ['value'],
                                    data: this.getValue("cache.excludePatternsArray", true)
                                }),
                                fieldLabel: t('exclude_patterns'),
                                name: 'cache.excludePatterns',
                                value: this.getValue("cache.excludePatterns"),
                                displayField: 'value',
                                valueField: 'value',
                                forceSelection: false,
                                delimiter: ',',
                                createNewOnEnter: true,
                                componentCls: 'superselect-no-drop-down'
                            },
                            {
                                xtype: "displayfield",
                                width: 600,
                                value: t("exclude_patterns_description"),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                fieldLabel: t('cache_disable_cookies'),
                                name: 'cache.excludeCookie',
                                value: this.getValue("cache.excludeCookie")
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('webservice'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 300},
                        items: [
                            {
                                fieldLabel: t("webservice_enabled"),
                                xtype: "checkbox",
                                name: "webservice.enabled",
                                checked: this.getValue("webservice.enabled")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t("webservice_description"),
                                cls: "pimcore_extra_label_bottom"
                            }
                        ]
                    }, {
                        xtype: 'fieldset',
                        title: t('http_connectivity_direct_proxy'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 300},
                        items: [
                            {
                                fieldLabel: t("select_connectivity_type"),
                                xtype: "combo",
                                name: "httpclient.adapter",
                                width: 400,
                                value: this.getValue("httpclient.adapter"),
                                store: [
                                    ["Socket", t("direct_socket")],
                                    ["Proxy", t("proxy")]
                                ],
                                mode: "local",
                                triggerAction: "all",
                                editable: false,
                                listeners: {
                                    afterrender: function (el) {
                                        if (el.getValue() == "Proxy") {
                                            Ext.getCmp("system_settings_proxy_settings").show();
                                        } else {
                                            Ext.getCmp("system_settings_proxy_settings").hide();
                                        }
                                    },
                                    select: function (el) {
                                        if (el.getValue() == "Proxy") {
                                            Ext.getCmp("system_settings_proxy_settings").show();
                                        } else {
                                            Ext.getCmp("system_settings_proxy_settings").hide();
                                        }
                                    }
                                }
                            },
                            {
                                xtype: "fieldset",
                                hidden: true,
                                id: "system_settings_proxy_settings",
                                collapsible: false,
                                title: t("proxy_settings"),
                                width: 600,
                                defaults: {width: 565},
                                items: [{
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_host'),
                                    name: 'httpclient.proxy_host',
                                    value: this.getValue("httpclient.proxy_host")
                                }, {
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_port'),
                                    name: 'httpclient.proxy_port',
                                    value: this.getValue("httpclient.proxy_port")
                                }, {
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_user'),
                                    name: 'httpclient.proxy_user',
                                    value: this.getValue("httpclient.proxy_user")
                                }, {
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_pass'),
                                    name: 'httpclient.proxy_pass',
                                    value: this.getValue("httpclient.proxy_pass")
                                }]
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: t('newsletter'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight: true,
                        labelWidth: 350,
                        items: [{
                            xtype: "checkbox",
                            fieldLabel: t("use_different_email_delivery_settings"),
                            name: "newsletter.usespecific",
                            checked: this.getValue("newsletter.usespecific"),
                            listeners: {
                                "change": function (el, checked) {
                                    if (checked) {
                                        Ext.getCmp("system_settings_newsletter_fieldset").show();
                                    } else {
                                        Ext.getCmp("system_settings_newsletter_fieldset").hide();
                                    }
                                }
                            }
                        }, {
                            xtype: "fieldset",
                            title: t("delivery_settings"),
                            collapsible: false,
                            defaults: {width: 600},
                            labelWidth: 250,
                            hidden: !this.getValue("newsletter.usespecific"),
                            id: "system_settings_newsletter_fieldset",
                            defaultType: 'textfield',
                            autoHeight: true,
                            items: [
                                {
                                    fieldLabel: t("email_method") + ' <span style="color:red;">*</span>',
                                    xtype: "combo",
                                    name: "newsletter.method",
                                    value: this.getValue("newsletter.method"),
                                    store: [
                                        ["mail", "mail"],
                                        ["smtp", "smtp"]
                                    ],
                                    listeners: {
                                        select: this.emailMethodSelected.bind(this, "newsletter")
                                    },
                                    editable: false,
                                    forceSelection: true,
                                    mode: "local",
                                    triggerAction: "all"
                                },
                                {
                                    xtype: "fieldset",
                                    title: "SMTP",
                                    width: 600,
                                    defaults: {width: 565},
                                    itemId: "newsletterSmtpSettings",
                                    defaultType: 'textfield',
                                    hidden: (this.getValue("newsletter.method") == "smtp") ? false : true,
                                    items: [{
                                        fieldLabel: t("email_smtp_host") + ' <span style="color:red;">*</span>',
                                        name: "newsletter.smtp.host",
                                        value: this.getValue("newsletter.smtp.host")
                                    },
                                        {
                                            fieldLabel: t("email_smtp_ssl"),
                                            xtype: "combo",
                                            name: "newsletter.smtp.ssl",
                                            value: this.getValue("newsletter.smtp.ssl"),
                                            store: [
                                                ["", t('no_ssl')],
                                                ["tls", "TLS"],
                                                ["ssl", "SSL"]
                                            ],
                                            editable: false,
                                            forceSelection: true,
                                            mode: "local",
                                            triggerAction: "all"
                                        },
                                        {
                                            fieldLabel: t("email_smtp_port"),
                                            name: "newsletter.smtp.port",
                                            value: this.getValue("newsletter.smtp.port")
                                        },
                                        {
                                            fieldLabel: t("email_smtp_name"),
                                            name: "newsletter.smtp.name",
                                            value: this.getValue("newsletter.smtp.name")
                                        },
                                        {
                                            fieldLabel: t("email_smtp_auth_method"),
                                            xtype: "combo",
                                            name: "newsletter.smtp.auth.method",
                                            value: this.getValue("newsletter.smtp.auth.method"),
                                            store: [
                                                ["", t('no_authentication')],
                                                ["login", "LOGIN"],
                                                ["plain", "PLAIN"],
                                                ["cram-md5", "CRAM-MD5"]
                                            ],
                                            mode: "local",
                                            editable: false,
                                            forceSelection: true,
                                            triggerAction: "all",
                                            listeners: {
                                                select: this.smtpAuthSelected.bind(this, "newsletter")
                                            }
                                        },
                                        {
                                            fieldLabel: t("email_smtp_auth_username"),
                                            name: "newsletter.smtp.auth.username",
                                            itemId: "newsletter_username",
                                            hidden: (this.getValue("newsletter.smtp.auth.method").length > 1) ? false : true,
                                            value: this.getValue("newsletter.smtp.auth.username")
                                        },
                                        {
                                            fieldLabel: t("email_smtp_auth_password"),
                                            name: "newsletter.smtp.auth.password",
                                            inputType: "password",
                                            itemId: "newsletter_password",
                                            hidden: (this.getValue("newsletter.smtp.auth.method").length > 1) ? false : true,
                                            value: this.getValue("newsletter.smtp.auth.password")
                                        }
                                    ]
                                },
                                {
                                    fieldLabel: t("email_senderemail") + ' <span style="color:red;">*</span>',
                                    name: "newsletter.sender.email",
                                    value: this.getValue("newsletter.sender.email")
                                },
                                {
                                    fieldLabel: t("email_sendername"),
                                    name: "newsletter.sender.name",
                                    value: this.getValue("newsletter.sender.name")
                                },
                                {
                                    fieldLabel: t("email_returnemail"),
                                    name: "newsletter.return.email",
                                    value: this.getValue("newsletter.return.email")
                                },
                                {
                                    fieldLabel: t("email_returnname"),
                                    name: "newsletter.return.name",
                                    value: this.getValue("newsletter.return.name")
                                }
                            ]
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
        var values = this.layout.getForm().getFieldValues();

        Ext.Ajax.request({
            url: "/admin/settings/set-system",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("system_settings_save_success"), "success");

                        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                            if (buttonValue == "yes") {
                                window.location.reload();
                            }
                        }.bind(this));
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("system_settings_save_error"),
                            "error", t(res.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("system_settings_save_error"), "error");
                }
            }
        });
    },


    emailMethodSelected: function (type, combo) {

        var smtpFieldSet = combo.ownerCt.getComponent(type + "SmtpSettings");

        if (combo.getValue() == "smtp") {
            smtpFieldSet.show();
        } else {
            smtpFieldSet.hide();
            Ext.each(smtpFieldSet.findByType("textfield"), function (item) {
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

        if (value) {
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
                    value: this.getValue("general.fallbackLanguages." + language)
                }, {
                    xtype: "radio",
                    name: "general.defaultLanguageRadio",
                    fieldLabel: t("default_language"),
                    checked: this.getValue("general.defaultLanguage") == language || (!this.getValue("general.defaultLanguage") && container.items.length == 0 ),
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
