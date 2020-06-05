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

pimcore.registerNS("pimcore.settings.profile.panel");
pimcore.settings.profile.panel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "my_profile",
                title: t("my_profile"),
                iconCls: "pimcore_icon_user",
                border: false,
                closable: true,
                layout: "fit",
                bodyStyle: "padding: 10px;",
                items: [this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("my_profile");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("profile");
            }.bind(this));


            pimcore.layout.refresh();

        }

        return this.panel;
    },

    getEditPanel: function () {
        this.forceReloadOnSave = false;
        this.currentUser = pimcore.currentuser;

        var passwordCheck = function (el) {
            if (/^(?=.*\d)(?=.*[a-zA-Z]).{6,100}$/.test(el.getValue())) {
                el.getEl().addCls("password_valid");
                el.getEl().removeCls("password_invalid");
            } else {
                el.getEl().addCls("password_invalid");
                el.getEl().removeCls("password_valid");
            }
        };

        var generalItems = [],
            baseItems = [];

        baseItems.push({
            xtype: "textfield",
            fieldLabel: t("firstname"),
            name: "firstname",
            value: this.currentUser.firstname,
            width: 400
        });

        baseItems.push({
            xtype: "textfield",
            fieldLabel: t("lastname"),
            name: "lastname",
            value: this.currentUser.lastname,
            width: 400
        });

        baseItems.push({
            xtype: "textfield",
            fieldLabel: t("email"),
            name: "email",
            value: this.currentUser.email,
            width: 400
        });

        baseItems.push({
            xtype: 'combo',
            fieldLabel: t('language'),
            typeAhead: true,
            value: this.currentUser.language,
            mode: 'local',
            name: "language",
            listWidth: 100,
            store: pimcore.globalmanager.get("pimcorelanguages"),
            displayField: 'display',
            valueField: 'language',
            forceSelection: true,
            triggerAction: 'all',
            hiddenName: 'language',
            listeners: {
                change: function () {
                    this.forceReloadOnSave = true;
                }.bind(this),
                select: function () {
                    this.forceReloadOnSave = true;
                }.bind(this)
            }
        });

        baseItems.push({
            xtype: "checkbox",
            boxLabel: t("show_welcome_screen"),
            name: "welcomescreen",
            checked: this.currentUser.welcomescreen
        });

        baseItems.push({
            xtype: "checkbox",
            boxLabel: t("memorize_tabs"),
            name: "memorizeTabs",
            checked: this.currentUser.memorizeTabs
        });

        generalItems.push({
            xtype: "fieldset",
            title: t('general_settings'),
            items: baseItems
        });

        var passwordField = new Ext.form.field.Text({
            fieldLabel: t("new_password"),
            name: "new_password",
            inputType: "password",
            width: 400,
            enableKeyEvents: true,
            listeners: {
                keyup: passwordCheck,
                afterrender: function (cmp) {
                    cmp.inputEl.set({
                        autocomplete: 'new-password'
                    });
                }
            }
        });

        var retypePasswordField = new Ext.form.field.Text({
            xtype: "textfield",
            fieldLabel: t("retype_password"),
            name: "retype_password",
            inputType: "password",
            width: 400,
            style: "margin-bottom: 20px;",
            enableKeyEvents: true,
            listeners: {
                keyup: passwordCheck,
                afterrender: function (cmp) {
                    cmp.inputEl.set({
                        autocomplete: 'new-password'
                    });
                }
            }
        });

        generalItems.push({
            xtype: "fieldset",
            title: t("change_password"),
            items: [{
                xtype: "textfield",
                fieldLabel: t("old_password"),
                name: "old_password",
                inputType: "password",
                width: 400,
                hidden: this.currentUser.isPasswordReset,
                listeners: {
                    afterrender: function (cmp) {
                        cmp.inputEl.set({
                            autocomplete: 'current-password'
                        });
                    }
                }
            }, {
                xtype: "fieldcontainer",
                layout: 'hbox',
                items: [

                    passwordField,
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
                            retypePasswordField.setValue(pass);

                            passwordCheck(passwordField);
                            passwordCheck(retypePasswordField);
                        }.bind(this)
                    }
                ]
            }, retypePasswordField]
        });

        var twoFactorSettings = new pimcore.settings.profile.twoFactorSettings(this.currentUser.twoFactorAuthentication);
        generalItems.push(twoFactorSettings.getPanel());

        generalItems.push({
            xtype: "fieldset",
            title: t("image"),
            width: '100%',
            items: [
                {
                    xtype: "container",
                    items: [{
                        xtype: "image",
                        id: "pimcore_profile_image_" + this.currentUser.id,
                        src: Routing.generate('pimcore_admin_user_getimage', {id: this.currentUser.id, '_dc': Ext.Date.now()}),
                        width: 45,
                        height: 45
                    }],
                    style: "float:left; margin-right: 10px;max-width:45px;"
                },
                {
                    xtype: "button",
                    text: t("upload"),
                    handler: function () {
                        pimcore.helpers.uploadDialog(
                            Routing.generate('pimcore_admin_user_uploadcurrentuserimage', {id: this.currentUser.id}),
                            null,
                            function () {
                                Ext.getCmp("pimcore_profile_delete_image_" + this.currentUser.id).setVisible(true);
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
                    id: "pimcore_profile_delete_image_" + this.currentUser.id,
                    hidden: !this.currentUser.hasImage,
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_user_deleteimage', {id: this.currentUser.id}),
                            method: 'DELETE',
                            success: function() {
                                Ext.getCmp("pimcore_profile_delete_image_" + this.currentUser.id).setVisible(false);
                                pimcore.helpers.reloadUserImage(this.currentUser.id);
                                this.currentUser.hasImage = false;
                            }.bind(this)
                        });
                    }.bind(this)
                }
            ]
        });

        this.editorSettings = new pimcore.settings.user.editorSettings(this, this.currentUser.contentLanguages);

        this.basicPanel = new Ext.form.FormPanel({
            border: false,
            items: [{items: generalItems}, this.editorSettings.getPanel()],
            labelWidth: 130
        });


        this.keyBindings = new pimcore.settings.user.user.keyBindings(this, true);

        this.userPanel = new Ext.Panel({
            autoScroll: true,
            items: [this.basicPanel, {
                xtype: "fieldset",
                collapsible: true,
                title: t("key_bindings"),
                items: [this.keyBindings.getPanel()]
            }],
            buttons: [
                {
                    text: t("save"),
                    iconCls: "pimcore_icon_apply",
                    handler: this.saveCurrentUser.bind(this)
                }
            ]
        });


        return this.userPanel;
    },

    saveCurrentUser: function () {
        var values = this.basicPanel.getForm().getFieldValues();
        var contentLanguages = this.editorSettings.getContentLanguages();
        values.contentLanguages = contentLanguages;

        if (values["new_password"]) {
            if (!pimcore.helpers.isValidPassword(values["new_password"]) || values["new_password"] != values["retype_password"]) {
                delete values["new_password"];
                delete values["retype_password"];
                Ext.MessageBox.alert(t('error'), t("password_was_not_changed"));
            }
        }

        try {
            var keyBindings = Ext.encode(this.keyBindings.getValues());
        } catch (e3) {
            console.log(e3);
        }



        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_user_updatecurrentuser'),
            method: "PUT",
            params: {
                id: this.currentUser.id,
                data: Ext.encode(values),
                keyBindings: keyBindings
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {

                        if (this.forceReloadOnSave) {
                            this.forceReloadOnSave = false;

                            Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                                if (buttonValue == "yes") {
                                    window.location.reload();
                                }
                            }.bind(this));
                        }

                        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                        if (contentLanguages) {
                            pimcore.settings.websiteLanguages = contentLanguages;
                            pimcore.currentuser.contentLanguages = contentLanguages.join(',');
                        }
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error", t(res.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this)
        });
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("my_profile");
    }
});
