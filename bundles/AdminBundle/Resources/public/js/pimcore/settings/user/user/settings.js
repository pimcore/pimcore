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


pimcore.registerNS("pimcore.settings.user.user.settings");
pimcore.settings.user.user.settings = Class.create({

    initialize: function (userPanel) {
        this.userPanel = userPanel;

        this.data = this.userPanel.data;
        this.currentUser = this.data.user;
        this.wsenabled = this.data.wsenabled;
    },

    getPanel: function () {
        var user = pimcore.globalmanager.get("user");
        this.forceReloadOnSave = false;

        var generalItems = [];


        generalItems.push({
            xtype: 'panel',
            border: false,
            layout: 'hbox',
            items: [
                {
                    xtype: "displayfield",
                    fieldLabel: t("id"),
                    value: this.currentUser.id,
                    flex: 0.3
                },
                {
                    xtype: "displayfield",
                    fieldLabel: t("last_login"),
                    value: (this.currentUser.lastLogin ? new Date(this.currentUser.lastLogin * 1000) : ''),
                    flex: 0.7
                }
            ]
        });

        generalItems.push({
            xtype: "checkbox",
            boxLabel: t("active"),
            name: "active",
            disabled: user.id == this.currentUser.id,
            checked: this.currentUser.active
        });

        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("username"),
            value: this.currentUser.name,
            width: 400,
            disabled: true
        });

        var passwordField = new Ext.form.field.Text({
            fieldLabel: t("password"),
            name: "password",
            inputType: "password",
            width: 400,
            enableKeyEvents: true,
            listeners: {
                keyup: function (el) {
                    this.validatePassword(el);
                }.bind(this),
                afterrender: function (cmp) {
                    cmp.inputEl.set({
                        autocomplete: 'new-password'
                    });
                }
            }
        });


        generalItems.push({
            xtype: "fieldcontainer",
            layout: 'hbox',

            items: [passwordField,
                {
                    xtype: "button",
                    width: 32,
                    style: "margin-left: 8px",
                    iconCls: "pimcore_icon_clear_cache",
                    handler: function () {

                        var pass;

                        while (true) {
                            pass = pimcore.helpers.generatePassword(15);
                            if (pimcore.helpers.isValidPassword(pass)) {
                                break;
                            }
                        }

                        passwordField.getEl().down('input').set({type: 'text'});
                        passwordField.setValue(pass);
                        this.validatePassword(passwordField);
                    }.bind(this)
                }
            ]
        });

        generalItems.push({
            xtype: "container",
            itemId: "password_hint",
            html: t("password_hint"),
            style: "color: red;",
            hidden: true
        });


        generalItems.push({
            xtype: "fieldset",
            title: t("two_factor_authentication"),
            items: [{
                xtype: "checkbox",
                boxLabel: t("2fa_required"),
                name: "2fa_required",
                checked: this.currentUser["twoFactorAuthentication"]['required']
            }, {
                xtype: "button",
                text: t("2fa_reset_secret"),
                hidden: !this.currentUser['twoFactorAuthentication']['isActive'],
                handler: function () {
                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_user_reset2fasecret'),
                        method: 'PUT',
                        params: {
                            id: this.currentUser.id
                        },
                        success: function (response) {
                            Ext.MessageBox.alert(t("2fa_reset_secret"), t("2fa_reset_done"));
                        }.bind(this)
                    });
                }.bind(this)
            }]
        });

        generalItems.push({
            xtype: "fieldset",
            title: t("image"),
            items: [
                {
                    xtype: "container",
                    items: [{
                        xtype: "image",
                        id: "pimcore_user_image_" + this.currentUser.id,
                        src: Routing.generate(
                            'pimcore_admin_user_getimage',
                            {id: this.currentUser.id, '_dc': Ext.Date.now()}
                        ),
                        width: 45,
                        height: 45
                    }],
                },
                {
                    xtype: "button",
                    text: t("upload"),
                    handler: function () {
                        pimcore.helpers.uploadDialog(
                            Routing.generate('pimcore_admin_user_uploadimage', {id: this.currentUser.id}),
                            null,
                            function () {
                                Ext.getCmp("pimcore_user_delete_image_" + this.currentUser.id).setVisible(true);
                                pimcore.helpers.reloadUserImage(this.currentUser.id);
                                this.currentUser.hasImage = true;
                            }.bind(this)
                        );
                    }.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_cancel",
                    tooltip: t("remove"),
                    id: "pimcore_user_delete_image_" + this.currentUser.id,
                    hidden: !this.currentUser.hasImage,
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_user_deleteimage', {id: this.currentUser.id}),
                            method: 'DELETE',
                            success: function() {
                                Ext.getCmp("pimcore_user_delete_image_" + this.currentUser.id).setVisible(false);
                                pimcore.helpers.reloadUserImage(this.currentUser.id);
                                this.currentUser.hasImage = false;
                            }.bind(this)
                        });
                    }.bind(this)
                }
            ]
        });

        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("firstname"),
            name: "firstname",
            value: this.currentUser.firstname,
            width: 400
        });
        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("lastname"),
            name: "lastname",
            value: this.currentUser.lastname,
            width: 400
        });

        var emailField = new Ext.form.field.Text({
            xtype: "textfield",
            fieldLabel: t("email"),
            name: "email",
            value: this.currentUser.email,
            width: 400
        });

        generalItems.push({
            xtype: "fieldcontainer",
            layout: 'hbox',

            items: [emailField,
                {
                    text: t("send_invitation_link"),
                    xtype: "button",
                    style: "margin-left: 8px",
                    iconCls: "pimcore_nav_icon_email",
                    hidden: (this.currentUser.lastLogin > 0) || (user.id == this.currentUser.id),
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_user_invitationlink'),
                            method: 'POST',
                            ignoreErrors: true,
                            params: {
                                username: this.currentUser.name
                            },
                            success: function (response) {
                                var res = Ext.decode(response.responseText);
                                if (res.success) {
                                    Ext.MessageBox.alert(t('invitation_sent'), res.message);
                                } else {
                                    Ext.MessageBox.alert(t('error'), res.message);
                                }
                            }.bind(this),
                            failure: function (response) {
                                var message = t("error_general");

                                try {
                                    var json = Ext.decode(response.responseText);
                                    if (json.message) {

                                        message = json.message;
                                    }
                                } catch (e) {
                                }

                                pimcore.helpers.showNotification(t("error"), message, "error");
                            }
                        });
                    }.bind(this)
                }
            ]
        });

        generalItems.push({
            xtype: 'combo',
            fieldLabel: t('language'),
            typeAhead: true,
            value: this.currentUser.language,
            mode: 'local',
            listWidth: 100,
            store: pimcore.globalmanager.get("pimcorelanguages"),
            displayField: 'display',
            valueField: 'language',
            forceSelection: true,
            triggerAction: 'all',
            name: 'language',
            listeners: {
                change: function () {
                    this.forceReloadOnSave = true;
                }.bind(this),
                select: function () {
                    this.forceReloadOnSave = true;
                }.bind(this)
            }
        });

        var rolesStore = Ext.create('Ext.data.ArrayStore', {
            fields: ["id", "name"],
            data: this.data.roles
        });

        this.roleField = Ext.create('Ext.ux.form.MultiSelect', {
            name: "roles",
            triggerAction: "all",
            editable: false,
            fieldLabel: t("roles"),
            width: 400,
            minHeight: 100,
            store: rolesStore,
            displayField: "name",
            valueField: "id",
            value: this.currentUser.roles.join(","),
            hidden: this.currentUser.admin
        });

        generalItems.push(this.roleField);

        var perspectivesStore = Ext.create('Ext.data.JsonStore', {
            fields: [
                "name",
                {
                    name:"translatedName",
                    convert: function (v, rec) {
                        return t(rec.data.name);
                    },
                    depends : ['name']
                }
            ],
            data: this.data.availablePerspectives
        });

        this.perspectivesField = Ext.create('Ext.ux.form.MultiSelect', {
            name: "perspectives",
            triggerAction: "all",
            editable: false,
            fieldLabel: t("perspectives"),
            width: 400,
            minHeight: 100,
            store: perspectivesStore,
            displayField: "translatedName",
            valueField: "name",
            value: this.currentUser.perspectives ? this.currentUser.perspectives.join(",") : null,
            hidden: this.currentUser.admin
        });

        generalItems.push(this.perspectivesField);


        generalItems.push({
            xtype: "checkbox",
            boxLabel: t("show_welcome_screen"),
            name: "welcomescreen",
            checked: this.currentUser.welcomescreen
        });

        generalItems.push({
            xtype: "checkbox",
            boxLabel: t("memorize_tabs"),
            name: "memorizeTabs",
            checked: this.currentUser.memorizeTabs
        });

        generalItems.push({
            xtype: "checkbox",
            boxLabel: t("allow_dirty_close"),
            name: "allowDirtyClose",
            checked: this.currentUser.allowDirtyClose
        });

        generalItems.push({
            xtype: "checkbox",
            boxLabel: t("show_close_warning"),
            name: "closeWarning",
            checked: this.currentUser.closeWarning
        });


        this.generalSet = new Ext.form.FieldSet({
            collapsible: true,
            title: t("general"),
            items: generalItems
        });


        var adminItems = [];

        if (user.admin) {
            // only admins are allowed to create new admin users and to manage API related settings
            adminItems.push({
                xtype: "checkbox",
                boxLabel: t("admin"),
                name: "admin",
                disabled: user.id == this.currentUser.id,
                checked: this.currentUser.admin,
                handler: function (box, checked) {
                    if (checked == true) {
                        this.roleField.hide();
                        this.typesSet.hide();
                        this.permissionsSet.hide();
                        this.userPanel.workspaces.disable();
                    } else {
                        this.roleField.show();
                        this.typesSet.show();
                        this.permissionsSet.show();
                        this.userPanel.workspaces.enable();
                    }
                }.bind(this)
            });

            adminItems.push({
                xtype: "displayfield",
                hideLabel: true,
                width: 600,
                value: t("user_admin_description"),
                cls: "pimcore_extra_label_bottom"
            });

            this.apiKeyField = new Ext.form.TextField({
                xtype: "textfield",
                fieldLabel: t("apikey"),
                name: "apiKey",
                style: "font-family: courier;",
                value: this.currentUser.apiKey,
                width: 560
            });

            this.apiKeyFieldContainer = new Ext.form.FieldSet({
                border: false,
                layout: 'hbox',
                style: "padding:10px 0 0 0; ",
                items: [this.apiKeyField,
                    {
                        xtype: "button",
                        test: t("Generate"),
                        iconCls: "pimcore_icon_clear_cache",
                        handler: function (e) {
                            this.apiKeyField.setValue(md5(uniqid()) + md5(uniqid()));
                        }.bind(this)
                    }],
                hidden: !this.wsenabled
            });

            this.apiKeyDescription = new Ext.form.DisplayField({
                hideLabel: true,
                width: 600,
                value: "<b>DEPRECATED! Will be removed in 7.0!</b>  " +  t("user_apikey_description"),
                cls: "pimcore_extra_label_bottom",
                hidden: !this.wsenabled
            });

            adminItems.push(this.apiKeyFieldContainer);
            adminItems.push(this.apiKeyDescription);
        }

        adminItems.push({
            xtype: "button",
            text: t("login_as_this_user"),
            iconCls: "pimcore_icon_user",
            disabled: user.id == this.currentUser.id,
            handler: function () {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_user_gettokenloginlink'),
                    ignoreErrors: true,
                    params: {
                        id: this.currentUser.id
                    },
                    success: function (response) {
                        var res = Ext.decode(response.responseText);
                        if (res["link"]) {
                            Ext.MessageBox.show({
                                title: t("login_as_this_user"),
                                msg: t("login_as_this_user_description")
                                    + '<br /><br /><textarea style="width:100%;height:90px;" readonly="readonly">' + res["link"] + "</textarea>",
                                buttons: Ext.MessageBox.YESNO,
                                buttonText: {
                                    yes: t("copy") + ' & ' + t("close"),
                                    no: t("close")
                                },
                                scope: this,
                                fn: function (result) {
                                    if (result === 'yes') {
                                        pimcore.helpers.copyStringToClipboard(res["link"]);
                                    }
                                }
                            });
                        }
                    },
                    failure: function (response) {
                        var message = t("error_general");

                        try {
                            var json = Ext.decode(response.responseText);
                            if (json.message) {

                                message = json.message;
                            }
                        } catch (e) {
                        }

                        pimcore.helpers.showNotification(t("error"), message, "error");
                    }
                });
            }.bind(this)
        });

        this.adminSet = new Ext.form.FieldSet({
            collapsible: true,
            title: t("admin"),
            items: adminItems
        });

        var itemsPerSection = [];
        var sectionArray = [];
        for (var i = 0; i < this.data.availablePermissions.length; i++) {
            let section = this.data.availablePermissions[i].category;
            if (!section) {
                section = "default";
            }
            if (!itemsPerSection[section]) {
                itemsPerSection[section] = [];
            }
            itemsPerSection[section].push({
                xtype: "checkbox",
                boxLabel: t(this.data.availablePermissions[i].key),
                name: "permission_" + this.data.availablePermissions[i].key,
                checked: this.data.permissions[this.data.availablePermissions[i].key],
                labelWidth: 200
            });
        }
        for (var key in itemsPerSection) {
            let title = t("permissions");
            if (key && key != "default") {
                title += " " + t(key);
            }

            sectionArray.push(new Ext.form.FieldSet({
                collapsible: true,
                title: title,
                items: itemsPerSection[key],
                collapsed: true,
            }));
        }

        this.permissionsSet = new Ext.container.Container({
            items: sectionArray,
            hidden: this.currentUser.admin
        });

        this.typesSet = new Ext.form.FieldSet({
            collapsible: true,
            title: t("allowed_types_to_create") + " (" + t("defaults_to_all") + ")",
            items: [
                Ext.create('Ext.ux.form.MultiSelect', {
                    name: "docTypes",
                    triggerAction: "all",
                    editable: false,
                    fieldLabel: t("document_types"),
                    width: 400,
                    valueField: "id",
                    store: pimcore.globalmanager.get("document_types_store"),
                    value: this.currentUser.docTypes,
                    listConfig: {
                        itemTpl: new Ext.XTemplate('{[this.sanitize(values.translatedName)]}',
                            {
                                sanitize: function (name) {
                                    return Ext.util.Format.htmlEncode(name);
                                }
                            }
                        )
                    }
                }),
                Ext.create('Ext.ux.form.MultiSelect', {
                    name: "classes",
                    triggerAction: "all",
                    editable: false,
                    fieldLabel: t("classes"),
                    width: 400,
                    displayField: "text",
                    valueField: "id",
                    store: pimcore.globalmanager.get("object_types_store"),
                    value: this.currentUser.classes
                })],
            hidden: this.currentUser.admin
        });

        this.editorSettings = new pimcore.settings.user.editorSettings(this, this.data.user.contentLanguages);
        this.websiteTranslationSettings = new pimcore.settings.user.websiteTranslationSettings(this, this.data.validLanguages, this.data.user);

        var websiteSettingsPanel = this.websiteTranslationSettings.getPanel();
        if (this.currentUser.admin) {
            websiteSettingsPanel.hide();
        }

        this.panel = new Ext.form.FormPanel({
            title: t("settings"),
            items: [this.generalSet, this.adminSet, this.permissionsSet, this.typesSet, this.editorSettings.getPanel(), websiteSettingsPanel],
            bodyStyle: "padding:10px;",
            autoScroll: true
        });

        return this.panel;
    },

    getValues: function () {

        var values = this.panel.getForm().getFieldValues();
        if (values["password"]) {
            if (!pimcore.helpers.isValidPassword(values["password"])) {
                delete values["password"];
                Ext.MessageBox.alert(t('error'), t("password_was_not_changed"));
            }
        }

        values.contentLanguages = this.editorSettings.getContentLanguages();
        values.websiteTranslationLanguagesEdit = this.websiteTranslationSettings.getLanguages("edit");
        values.websiteTranslationLanguagesView = this.websiteTranslationSettings.getLanguages("view");

        return values;
    },

    validatePassword: function (el) {

        var theEl = el.getEl();
        var hintItem = this.generalSet.getComponent("password_hint");

        if (pimcore.helpers.isValidPassword(el.getValue())) {
            theEl.addCls("password_valid");
            theEl.removeCls("password_invalid");
            hintItem.hide();
        } else {
            theEl.addCls("password_invalid");
            theEl.removeCls("password_valid");
            hintItem.show();
        }

        if (el.getValue().length < 1) {
            theEl.removeCls("password_valid");
            theEl.removeCls("password_invalid");
            hintItem.hide();
        }

        this.generalSet.updateLayout();
    }

});
