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

                //admin users
                try {
                    this.adminUsersStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        data: this.data,
                        root: 'adminUsers',
                        fields: ['id', 'username']
                    });
                } catch(e) {
                    this.adminUsersStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        fields: ['id', 'username']
                    });
                }

                //valid languages
                try {
                    this.languagesStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        data: this.data.config,
                        root: 'languages',
                        fields: ['language', 'display']
                    });
                } catch(e) {
                    this.languagesStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        fields: ['language', 'display']
                    });
                }

                //email - debug email addresses ckogler
                 try {
                    this.emailDebugAddressesStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        data: this.data.values.email.debug,
                        root: 'emaildebugaddressesArray',
                        fields: ['value']
                    });
                } catch(e) {
                    this.emailDebugAddressesStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        fields: ['value']
                    });
                }

                //cache exclude patterns
                try {
                    this.cacheExcludeStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        data: this.data.values.cache,
                        root: 'excludePatternsArray',
                        fields: ['value']
                    });
                } catch(e) {
                    this.cacheExcludeStore = new Ext.data.JsonStore({
                        autoDestroy: true,
                        fields: ['value']
                    });
                }

                this.getTabPanel();

            }.bind(this)
        });
    },

    getValue: function (key) {

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

        if (typeof current != "object" && typeof current != "array" && typeof current != "function") {
            return current;
        }

        return "";
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_settings_system",
                title: t("system_settings"),
                iconCls: "pimcore_icon_system",
                border: false,
                layout: "fit",
                closable:true
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_settings_system");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("settings_system");
            }.bind(this));

            // sites error pages
            var sitesErrorPagesFields = [];
            var sites = pimcore.globalmanager.get("sites");
            sitesErrorPagesFields.push(this.getErrorPageFieldConfig("default", t("main_site")));

            sites.each(function (record) {
                sitesErrorPagesFields.push(this.getErrorPageFieldConfig("site_" + record.data.id, record.data.domains.split(",")[0]));
            }, this);

            // debug
            if (this.data.values.general.debug) {
                this.data.values.general.debug = true;
            }

            this.layout = new Ext.FormPanel({
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                forceLayout: true,
                defaults: {
                    forceLayout: true
                },
                layout: "pimcoreform",
                buttons: [
                    {
                        text: "Save",
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    }
                ],
                items: [
                    {
                        xtype:'fieldset',
                        title: t('general'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('timezone'),
                                name: 'general.timezone',
                                xtype: "combo",
                                editable: false,
                                triggerAction: 'all',
                                store: this.data.config.timezones,
                                value: this.getValue("general.timezone"),
                                width: 400,
                                listWidth: 400
                            },
                            {
                                fieldLabel: t("view_suffix"),
                                xtype: "combo",
                                width: 250,
                                editable: false,
                                name: "general.viewSuffix",
                                value: this.getValue("general.viewSuffix"),
                                store: [
                                    ["", ".php (pimcore standard)"],
                                    ["phtml","phtml (zend framework standard)"]
                                ],
                                mode: "local",
                                triggerAction: "all"
                            },{
                                fieldLabel: t("absolute_path_to_php_cli_binary"),
                                xtype: "textfield",
                                name: "general.php_cli",
                                value: this.getValue("general.php_cli"),
                                width: 300
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('language_admin'),
                                typeAhead:true,
                                value: this.getValue("general.language"),
                                mode: 'local',
                                listWidth: 100,
                                editable: false,
                                store: pimcore.globalmanager.get("pimcorelanguages"),
                                displayField: 'display',
                                valueField: 'language',
                                forceSelection: true,
                                triggerAction: 'all',
                                hiddenName: 'general.language'
                            },{
                                xtype: 'superboxselect',
                                allowBlank:false,
                                queryDelay: 0,
                                triggerAction: 'all',
                                resizable: true,
                                mode: 'local',
                                anchor:'100%',
                                minChars: 1,
                                fieldLabel: t("valid_languages_frontend") + '<span style="color:red;">*</span>',
                                emptyText: t("valid_languages_frontend_empty_text"),
                                name: 'general.validLanguages',
                                value: this.getValue("general.validLanguages"),
                                store: this.languagesStore,
                                displayField: 'display',
                                valueField: 'language',
                                forceFormValue: true
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('valid_languages_frontend_description'),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                fieldLabel: t('show_random_pictures_on_login_screen'),
                                xtype: "checkbox",
                                name: "general.loginscreenimageservice",
                                checked: this.getValue("general.loginscreenimageservice")
                            },
                            {
                                fieldLabel: t("url_to_custom_image_on_login_screen"),
                                xtype: "textfield",
                                name: "general.loginscreencustomimage",
                                value: this.getValue("general.loginscreencustomimage")
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: "Debug",
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: "DEBUG",
                                xtype: "checkbox",
                                name: "general.debug",
                                checked: this.getValue("general.debug"),
                                listeners: {
                                    check: function (el, checked) {
                                        // set the current client ip to the debug ip field
                                        if(checked) {
                                            Ext.getCmp("system.settings.general.debug_ip").setValue(this.data.config.client_ip);
                                        }
                                    }.bind(this)
                                }
                            },
                            {
                                fieldLabel: t("only_for_ip"),
                                xtype: "textfield",
                                id: "system.settings.general.debug_ip",
                                name: "general.debug_ip",
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
                                value: "<b>" + t("password_protection") + " (HTTP-Auth)</b> "
                            },
                            {
                                fieldLabel: t('username'),
                                name: 'general.http_auth.username',
                                value: this.getValue("general.http_auth.username")
                            },
                            {
                                fieldLabel: t('password'),
                                name: 'general.http_auth.password',
                                value: this.getValue("general.http_auth.password")
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b style='color:red;'>" + t("http_auth_warning") + "</b>",
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b>" + t("logger") + "</b>"
                            },
                            {
                                fieldLabel: "FirePHP (Firebug/Wildfire)",
                                xtype: "checkbox",
                                name: "general.firephp",
                                checked: this.getValue("general.firephp")
                            },
                            {
                                fieldLabel: t('log_messages_user_mail_recipient'),
                                xtype: "combo",
                                editable: false,
                                triggerAction: 'all',
                                store: this.adminUsersStore,
                                value: this.getValue("general.logrecipient"),
                                width: 200,
                                listWidth: 200,
                                displayField: 'username',
                                valueField: 'id',
                                name: 'general.logrecipient',
                                typeAhead:true,
                                mode: 'local',
                                forceSelection: true
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('log_messages_user_mail_description'),
                                cls: "pimcore_extra_label_bottom"
                            },

                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b>" + t("loglevels") + "</b>"
                            },

                            {
                                fieldLabel: "DEBUG",
                                xtype: "checkbox",
                                name: "general.loglevel.debug",
                                checked: this.getValue("general.loglevel.debug")
                            },
                            {
                                fieldLabel: "INFO",
                                xtype: "checkbox",
                                name: "general.loglevel.info",
                                checked: this.getValue("general.loglevel.info")
                            },
                            {
                                fieldLabel: "NOTICE",
                                xtype: "checkbox",
                                name: "general.loglevel.notice",
                                checked: this.getValue("general.loglevel.notice")
                            },
                            {
                                fieldLabel: "WARNING",
                                xtype: "checkbox",
                                name: "general.loglevel.warning",
                                checked: this.getValue("general.loglevel.warning")
                            },
                            {
                                fieldLabel: "ERROR",
                                xtype: "checkbox",
                                name: "general.loglevel.error",
                                checked: this.getValue("general.loglevel.error")
                            },
                            {
                                fieldLabel: "CRITICAL",
                                xtype: "checkbox",
                                disabled: true,
                                name: "general.loglevel.critical",
                                checked: this.getValue("general.loglevel.critical")
                            },
                            {
                                fieldLabel: "ALERT",
                                xtype: "checkbox",
                                disabled: true,
                                name: "general.loglevel.alert",
                                checked: this.getValue("general.loglevel.alert")
                            },
                            {
                                fieldLabel: 'EMERGENCY',
                                xtype: "checkbox",
                                disabled: true,
                                name: "general.loglevel.emergency",
                                checked: this.getValue("general.loglevel.emergency")
                            },
                             {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t('loglevels_description'),
                                cls: "pimcore_extra_label_bottom"
                            },

                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "&nbsp;",
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                fieldLabel: t("debug_admin_translations"),
                                xtype: "checkbox",
                                name: "general.debug_admin_translations",
                                checked: this.getValue("general.debug_admin_translations")
                            },
                            {
                                fieldLabel: 'DEV-Mode (<span style="color:red;font-weight:bold;">DON`T ACTIVATE IT!</span>)',
                                xtype: "checkbox",
                                name: "general.devmode",
                                checked: this.getValue("general.devmode")
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('email_settings'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t("email_senderemail"),
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
                            },
                            {
                                fieldLabel: t("email_method"),
                                xtype: "combo",
                                name: "email.method",
                                value: this.getValue("email.method"),
                                store: [
                                    ["sendmail", "sendmail"],
                                    ["smtp","smtp"]
                                ],
                                listeners: {
                                    select: this.emailMethodSelected.bind(this)
                                },
                                mode: "local",
                                triggerAction: "all"
                            },
                            {
                                fieldLabel: t("email_smtp_host"),
                                id: "system.settings.email.smtp.host",
                                name: "email.smtp.host",
                                disabled: this.getValue("email.method") != "smtp",
                                value: this.getValue("email.smtp.host")
                            },
                            {
                                fieldLabel: t("email_smtp_ssl"),
                                xtype: "combo",
                                disabled: this.getValue("email.method") != "smtp",
                                name: "email.smtp.ssl",
                                id: "system.settings.email.smtp.ssl",
                                value: this.getValue("email.smtp.ssl"),
                                store: [
                                    ["", t('no_ssl')],
                                    ["tls","TLS"],
                                    ["ssl","SSL"]
                                ],
                                mode: "local",
                                triggerAction: "all"
                            },
                            {
                                fieldLabel: t("email_smtp_port"),
                                name: "email.smtp.port",
                                id: "system.settings.email.smtp.port",
                                disabled: this.getValue("email.method") != "smtp",
                                value: this.getValue("email.smtp.port")
                            },
                            {
                                fieldLabel: t("email_smtp_name"),
                                name: "email.smtp.name",
                                id: "system.settings.email.smtp.name",
                                disabled: this.getValue("email.method") != "smtp",
                                value: this.getValue("email.smtp.name")
                            },
                            {
                                fieldLabel: t("email_smtp_auth_method"),
                                xtype: "combo",
                                disabled: this.getValue("email.method") != "smtp",
                                name: "email.smtp.auth.method",
                                id: "system.settings.email.smtp.method",
                                value: this.getValue("email.smtp.auth.method"),
                                store: [
                                    ["", t('no_authentication')],
                                    ["login","LOGIN"],
                                    ["plain","PLAIN"],
                                    ["cram-md5", "CRAM-MD5"]
                                ],
                                mode: "local",
                                triggerAction: "all",
                                listeners: {
                                    select: this.smtpAuthSelected.bind(this)
                                }
                            },
                            {
                                fieldLabel: t("email_smtp_auth_username"),
                                name: "email.smtp.auth.username",
                                id: "system.settings.email.smtp.auth.username",
                                disabled: this.getValue("email.smtp.auth.method") == "",
                                value: this.getValue("email.smtp.auth.username")
                            },
                            {
                                fieldLabel: t("email_smtp_auth_password"),
                                name: "email.smtp.auth.password",
                                id: "system.settings.email.smtp.auth.password",
                                inputType: "password",
                                disabled: this.getValue("email.smtp.auth.method") == "",
                                value: this.getValue("email.smtp.auth.password")
                            },
                            {
                                xtype: 'superboxselect',
                                allowBlank:true,
                                queryDelay: 100,
                                triggerAction: 'all',
                                resizable: true,
                                mode: 'local',
                                anchor:'100%',
                                minChars: 2,
                                fieldLabel: t("email_debug_addresses"), //ckogler
                                name: 'email.debug.emailAddresses',
                                value: this.getValue("email.debug.emailaddresses"),
                                emptyText: t("email_debug_addresses_empty_text"),
                                store: this.emailDebugAddressesStore,
                                fields: ['value'],
                                displayField: 'value',
                                valueField: 'value',
                                allowAddNewData: true,
                                ctCls: 'superselect-no-drop-down',
                                listeners: {
                                    newitem: function(bs, v, f) {
                                        v = v + '';
                                        var newObj = {
                                            value: v
                                        };
                                        bs.addNewItem(newObj);
                                    }
                            }

                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('website'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t("domain"),
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
                                xtype: "checkbox",
                                fieldLabel: t("enable_targeting"),
                                name: "general.targeting",
                                checked: this.getValue("general.targeting")
                            },
                            {
                                xtype:'fieldset',
                                title: t('error_pages'),
                                collapsible: false,
                                collapsed: false,
                                autoHeight:true,
                                labelWidth: 300,
                                width: 600,
                                items: sitesErrorPagesFields
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('mysql_database'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('adapter'),
                                disabled: true,
                                name: 'database.adapter',
                                value: this.getValue("database.adapter")
                            },{
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
                    },
                    {
                        xtype:'fieldset',
                        title: t('documents'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('store_version_history_in_days'),
                                name: 'documents.versions.days',
                                value: this.getValue("documents.versions.days"),
                                xtype: "spinnerfield",
                                id: "system.settings.documents.versions.days",
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": this.checkVersionInputs.bind(this, "documents", "days"),
                                    "spin": this.checkVersionInputs.bind(this, "documents", "days"),
                                    "afterrender": this.checkVersionInputs.bind(this, "documents", "days", "init")
                                }
                            },
                            {
                                fieldLabel: t('store_version_history_in_steps'),
                                name: 'documents.versions.steps',
                                value: this.getValue("documents.versions.steps"),
                                xtype: "spinnerfield",
                                id: "system.settings.documents.versions.steps",
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": this.checkVersionInputs.bind(this, "documents", "steps"),
                                    "spin": this.checkVersionInputs.bind(this, "documents", "steps"),
                                    "afterrender": this.checkVersionInputs.bind(this, "documents", "steps", "init")
                                }
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
                            },{
                                xtype: "displayfield",
                                hideLabel: true,
                                style: "margin-top: 10px;",
                                width: 600,
                                value: "&nbsp;"
                            },{
                                fieldLabel: t('create_redirect_for_moved_renamed_page'),
                                xtype: "checkbox",
                                name: "documents.createredirectwhenmoved",
                                checked: this.getValue("documents.createredirectwhenmoved")
                            },{
                                fieldLabel: t("allow_trailing_slash_for_documents"),
                                xtype: "combo",
                                name: "documents.allowtrailingslash",
                                value: this.getValue("documents.allowtrailingslash"),
                                store: [
                                    ["",t("yes")],
                                    ["no",t("no")]
                                ],
                                mode: "local",
                                triggerAction: "all"
                            },{
                                fieldLabel: t("allow_capitals_for_documents"),
                                xtype: "combo",
                                name: "documents.allowcapitals",
                                value: this.getValue("documents.allowcapitals"),
                                store: [
                                    ["",t("yes")],
                                    ["no",t("no")]
                                ],
                                mode: "local",
                                triggerAction: "all"
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('objects'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('store_version_history_in_days'),
                                name: 'objects.versions.days',
                                value: this.getValue("objects.versions.days"),
                                xtype: "spinnerfield",
                                id: "system.settings.objects.versions.days",
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": this.checkVersionInputs.bind(this, "objects", "days"),
                                    "spin": this.checkVersionInputs.bind(this, "objects", "days"),
                                    "afterrender": this.checkVersionInputs.bind(this, "objects", "days", "init")
                                }
                            },
                            {
                                fieldLabel: t('store_version_history_in_steps'),
                                name: 'objects.versions.steps',
                                value: this.getValue("objects.versions.steps"),
                                xtype: "spinnerfield",
                                id: "system.settings.objects.versions.steps",
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": this.checkVersionInputs.bind(this, "objects", "steps"),
                                    "spin": this.checkVersionInputs.bind(this, "objects", "steps"),
                                    "afterrender": this.checkVersionInputs.bind(this, "objects", "steps", "init")
                                }
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('assets'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 250,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('hostname_for_webdav'),
                                name: 'assets.webdav.hostname',
                                value: this.getValue("assets.webdav.hostname")
                            },
                            {
                                fieldLabel: t('store_version_history_in_days'),
                                name: 'assets.versions.days',
                                value: this.getValue("assets.versions.days"),
                                xtype: "spinnerfield",
                                id: "system.settings.assets.versions.days",
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": this.checkVersionInputs.bind(this, "assets", "days"),
                                    "spin": this.checkVersionInputs.bind(this, "assets", "days"),
                                    "afterrender": this.checkVersionInputs.bind(this, "assets", "days", "init")
                                }
                            },
                            {
                                fieldLabel: t('store_version_history_in_steps'),
                                name: 'assets.versions.steps',
                                value: this.getValue("assets.versions.steps"),
                                xtype: "spinnerfield",
                                id: "system.settings.assets.versions.steps",
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": this.checkVersionInputs.bind(this, "assets", "steps"),
                                    "spin": this.checkVersionInputs.bind(this, "assets", "steps"),
                                    "afterrender": this.checkVersionInputs.bind(this, "assets", "steps", "init")
                                }
                            },
                            {
                                fieldLabel: t('absolute_path_to_ffmpeg_binary'),
                                name: 'assets.ffmpeg',
                                value: this.getValue("assets.ffmpeg"),
                                width: 300
                            },
                            {
                                fieldLabel: t('absolute_path_to_qtfaststart'),
                                name: 'assets.qtfaststart',
                                value: this.getValue("assets.qtfaststart"),
                                width: 300
                            },
                            {
                                fieldLabel: t('absolute_path_to_icc_rgb_profile') + " (imagick)",
                                name: 'assets.icc_rgb_profile',
                                value: this.getValue("assets.icc_rgb_profile"),
                                width: 300
                            },
                            {
                                fieldLabel: t('absolute_path_to_icc_cmyk_profile') + " (imagick)",
                                name: 'assets.icc_cmyk_profile',
                                value: this.getValue("assets.icc_cmyk_profile"),
                                width: 300
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('google_credentials_and_api_keys'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b>" + t('google_api_key_service') + "</b>",
                                cls: "pimcore_extra_label"
                            },{
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: t("google_api_access_description"),
                                cls: "pimcore_extra_label"
                            },
                            {
                                fieldLabel: t('client_id'),
                                name: 'services.google.client_id',
                                value: this.getValue("services.google.client_id"),
                                width: 600
                            },
                            {
                                fieldLabel: t('email'),
                                name: 'services.google.email',
                                value: this.getValue("services.google.email"),
                                width: 600
                            },{
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: this.data.config.google_private_key_exists ? t("google_api_private_key_installed") : ('<span style="color:red;">' + t("google_api_key_missing") + " <br />" + this.data.config.google_private_key_path + '</span>'),
                                cls: "pimcore_extra_label"
                            },{
                                xtype: "displayfield",
                                hideLabel: true,
                                style: "margin-top: 10px;",
                                width: 600,
                                value: "&nbsp;"
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b>" + t('google_api_key_simple') + "</b>",
                                cls: "pimcore_extra_label"
                            },
                            {
                                fieldLabel: t('server_api_key'),
                                name: 'services.google.simpleapikey',
                                value: this.getValue("services.google.simpleapikey"),
                                width: 650
                            },
                            {
                                fieldLabel: t('browser_api_key'),
                                name: 'services.google.browserapikey',
                                value: this.getValue("services.google.browserapikey"),
                                width: 650
                            },{
                                xtype: "displayfield",
                                hideLabel: true,
                                style: "margin-top: 10px;",
                                width: 600,
                                value: "&nbsp;"
                            },
                            {
                                xtype: "displayfield",
                                hideLabel: true,
                                width: 600,
                                value: "<b>" + t('translate_api_key') + "</b>",
                                cls: "pimcore_extra_label"
                            },
                            {
                                fieldLabel: t('api_key'),
                                name: 'services.translate.apikey',
                                value: this.getValue("services.translate.apikey"),
                                width: 650
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('output_cache'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 300},
                        items :[
                            {
                                fieldLabel: t("cache_enabled"),
                                xtype: "checkbox",
                                name: "cache.enabled",
                                checked: this.getValue("cache.enabled")
                            },
                            {
                                fieldLabel: t('lifetime'),
                                xtype: "spinnerfield",
                                name: 'cache.lifetime',
                                value: this.getValue("cache.lifetime"),
                                width: 80,
                                incrementValue: 100
                            },
                            {
                                xtype: "displayfield",
                                width: 600,
                                value: t("outputcache_lifetime_description"),
                                cls: "pimcore_extra_label_bottom"
                            },
                            {
                                xtype: 'superboxselect',
                                allowBlank:true,
                                queryDelay: 100,
                                triggerAction: 'all',
                                resizable: true,
                                mode: 'local',
                                anchor:'100%',
                                minChars: 2,
                                fieldLabel: t('cache_exclude_patterns'),
                                name: 'cache.excludePatterns',
                                value: this.getValue("cache.excludePatterns"),
                                emptyText: t("superselectbox_empty_text"),
                                store: this.cacheExcludeStore,
                                fields: ['value'],
                                displayField: 'value',
                                valueField: 'value',
                                allowAddNewData: true,
                                ctCls: 'superselect-no-drop-down',
                                listeners: {
                                    newitem: function(bs, v, f) {
                                        v = v + '';
                                        var newObj = {
                                            value: v
                                        };
                                        bs.addNewItem(newObj);
                                    }
                                }

                            },
                            {
                                xtype: "displayfield",
                                width: 600,
                                value: t("cache_exclude_patterns_description"),
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
                        xtype:'fieldset',
                        title: t('outputfilters'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'checkbox',
                        defaults: {width: 300},
                        items :[
                            {
                                fieldLabel: t("image_datauri_filter"),
                                xtype: "checkbox",
                                name: "outputfilters.imagedatauri",
                                checked: this.getValue("outputfilters.imagedatauri"),
                                style: "margin-bottom: 15px;"
                            },
                            {
                                fieldLabel: "LESS",
                                xtype: "checkbox",
                                name: "outputfilters.less",
                                checked: this.getValue("outputfilters.less")
                            },
                            {
                                fieldLabel: t("path_to_lessc_optional"),
                                xtype: "textfield",
                                name: "outputfilters.lesscpath",
                                value: this.getValue("outputfilters.lesscpath"),
                                style: "margin-bottom: 15px;"
                            },
                            {
                                fieldLabel: t("minify_css"),
                                xtype: "checkbox",
                                name: "outputfilters.cssminify",
                                checked: this.getValue("outputfilters.cssminify"),
                                style: "margin-bottom: 15px;"
                            },
                            {
                                fieldLabel: t("minify_javascript"),
                                xtype: "checkbox",
                                name: "outputfilters.javascriptminify",
                                checked: this.getValue("outputfilters.javascriptminify")
                            },
                            {
                                fieldLabel: t("minify_javascript_algorithm"),
                                xtype: "combo",
                                name: "outputfilters.javascriptminifyalgorithm",
                                value: this.getValue("outputfilters.javascriptminifyalgorithm"),
                                store: [
                                    [" ", "default"],
                                    ["jsmin","JSMin"],
                                    ["jsminplus","JSMinPlus"],
                                    ["yuicompressor", "YUI Compressor (Java required)"]
                                ],
                                mode: "local",
                                triggerAction: "all",
                                editable: false,
                                style: "margin-bottom: 15px;"
                            }
                        ]
                    },{
                        xtype:'fieldset',
                        title: t('webservice'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 300},
                        items :[
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
                    },{
                        xtype:'fieldset',
                        title: t('http_connectivity_direct_proxy'),
                        collapsible: true,
                        collapsed: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 300},
                        items :[
                            {
                                fieldLabel: t("select_connectivity_type"),
                                xtype: "combo",
                                name: "httpclient.adapter",
                                value: this.getValue("httpclient.adapter"),
                                store: [
                                    ["Zend_Http_Client_Adapter_Socket", t("direct_socket")],
                                    ["Zend_Http_Client_Adapter_Proxy",t("proxy")]
                                ],
                                mode: "local",
                                triggerAction: "all",
                                editable: false,
                                listeners: {
                                    afterrender: function (el) {
                                        if(el.getValue() == "Zend_Http_Client_Adapter_Proxy") {
                                            Ext.getCmp("system.settings.proxy_settings").show();
                                        } else {
                                            Ext.getCmp("system.settings.proxy_settings").hide();
                                        }
                                    },
                                    select: function (el) {
                                        if(el.getValue() == "Zend_Http_Client_Adapter_Proxy") {
                                            Ext.getCmp("system.settings.proxy_settings").show();
                                        } else {
                                            Ext.getCmp("system.settings.proxy_settings").hide();
                                        }
                                    }
                                }
                            },
                            {
                                xtype: "fieldset",
                                hidden: true,
                                id: "system.settings.proxy_settings",
                                collapsible: false,
                                title: t("proxy_settings"),
                                width: 400,
                                labelWidth: 130,
                                items: [{
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_host'),
                                    name: 'httpclient.proxy_host',
                                    width: 200,
                                    value: this.getValue("httpclient.proxy_host")
                                },{
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_port'),
                                    name: 'httpclient.proxy_port',
                                    width: 200,
                                    value: this.getValue("httpclient.proxy_port")
                                },{
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_user'),
                                    name: 'httpclient.proxy_user',
                                    width: 200,
                                    value: this.getValue("httpclient.proxy_user")
                                },{
                                    xtype: "textfield",
                                    fieldLabel: t('proxy_pass'),
                                    name: 'httpclient.proxy_pass',
                                    width: 200,
                                    value: this.getValue("httpclient.proxy_pass")
                                }]
                            }
                        ]
                    }
                ]
            });

            this.panel.add(this.layout);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_settings_system");
    },

    save: function () {
        var values = this.layout.getForm().getFieldValues();

        // check for mandatory fields
        if(empty(values["general.validLanguages"])) {
            Ext.MessageBox.alert(t("error"), t("mandatory_field_empty"));
            return;
        }


        
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
                        pimcore.helpers.showNotification(t("error"), t("system_settings_save_error"), "error", t(res.message));
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("system_settings_save_error"), "error");
                }
            }
        });
    },
    emailMethodSelected: function(combo, record, index) {
        var disabled = true;
        if (index == 1) {
            disabled = false;
        }
        this.layout.getForm().findField("system.settings.email.smtp.host").setDisabled(disabled);
        this.layout.getForm().findField("system.settings.email.smtp.port").setDisabled(disabled);
        this.layout.getForm().findField("system.settings.email.smtp.name").setDisabled(disabled);
        this.layout.getForm().findField("system.settings.email.smtp.method").setDisabled(disabled);
        this.layout.getForm().findField("system.settings.email.smtp.ssl").setDisabled(disabled);
        
        if (disabled) {
            this.layout.getForm().findField("system.settings.email.smtp.host").setValue();
            this.layout.getForm().findField("system.settings.email.smtp.port").setValue();
            this.layout.getForm().findField("system.settings.email.smtp.name").setValue();
            this.layout.getForm().findField("system.settings.email.smtp.method").setValue();
            this.layout.getForm().findField("system.settings.email.smtp.ssl").setValue();
        }
        this.smtpAuthSelected(null, null, null, true);
        pimcore.layout.refresh();

    },

    smtpAuthSelected: function(combo, record, index, forceDisable) {
        var disabled = true;
        if (index != 0 && !forceDisable) {
            disabled = false;
        }
        this.layout.getForm().findField("system.settings.email.smtp.auth.username").setDisabled(disabled);
        this.layout.getForm().findField("system.settings.email.smtp.auth.password").setDisabled(disabled);
        if (disabled) {
            this.layout.getForm().findField("system.settings.email.smtp.auth.username").setValue("");
            this.layout.getForm().findField("system.settings.email.smtp.auth.password").setValue("");
        }
    },

    getErrorPageFieldConfig: function (siteKey, labelText) {
        return {
            fieldLabel: labelText,
            name: "documents.error_pages." + siteKey,
            cls: "input_drop_target",
            value: this.getValue("documents.error_pages." + siteKey),
            width: 250,
            xtype: "textfield",
            listeners: {
                "render": function (el) {
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
                }
            }
        };
    },
    
    checkVersionInputs: function (elementType, type, field, event) {
                
        var mappingOpposite = {
            steps: "days",
            days: "steps"
        };
        
        var value = Ext.getCmp("system.settings." + elementType + ".versions." + type).getValue();
        
        if(event == "init") {
            if(!value) {
                return;
            }
        }
        
        if(value) {
            Ext.getCmp("system.settings." + elementType + ".versions." + mappingOpposite[type]).disable();
            Ext.getCmp("system.settings." + elementType + ".versions." + mappingOpposite[type]).setValue("");
        } else {
            Ext.getCmp("system.settings." + elementType + ".versions." + mappingOpposite[type]).enable();
        }
    }

});