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


pimcore.registerNS("pimcore.settings.user.user.settings");
pimcore.settings.user.user.settings = Class.create({

    initialize:function (userPanel) {
        this.userPanel = userPanel;


        this.data = this.userPanel.data;
        this.currentUser = this.data.user;
        this.wsenabled = this.data.wsenabled;
    },

    getPanel:function () {

        var user = pimcore.globalmanager.get("user");
        this.forceReloadOnSave = false;

        var generalItems = [];

        generalItems.push({
            xtype:"checkbox",
            fieldLabel:t("active"),
            name:"active",
            disabled:user.id == this.currentUser.id,
            checked:this.currentUser.active
        });

        generalItems.push({
            xtype:"textfield",
            fieldLabel:t("username"),
            value:this.currentUser.name,
            width:300,
            disabled:true
        });
        generalItems.push({
            xtype:"textfield",
            fieldLabel:t("password"),
            name:"password",
            inputType:"password",
            width:300,
            enableKeyEvents: true,
            listeners: {
                keyup: function (el) {
                    if(/^(?=.*\d)(?=.*[a-zA-Z]).{6,100}$/.test(el.getValue())) {
                        el.getEl().addClass("password_valid");
                        el.getEl().removeClass("password_invalid");
                    } else {
                        el.getEl().addClass("password_invalid");
                        el.getEl().removeClass("password_valid");
                    }
                }
            }
        });

        var date = new Date();
        var image = "/admin/user/get-image?id=" + this.currentUser.id + "&_dc=" + date.getTime();

        generalItems.push({
            xtype: "fieldset",
            title: t("image"),
            items: [{
                xtype: "container",
                id: "pimcore_user_image_" + this.currentUser.id,
                html: '<img src="' + image + '" />',
                width: 45,
                height: 45,
                style: "float:left; margin-right: 10px;"
            },{
                xtype:"button",
                text: t("upload"),
                handler: function () {
                    pimcore.helpers.uploadDialog("/admin/user/upload-image?id=" + this.currentUser.id, null,
                        function () {
                            var cont = Ext.getCmp("pimcore_user_image_" + this.currentUser.id);
                            var date = new Date();
                            cont.update('<img src="/admin/user/get-image?id='
                                                    + this.currentUser.id + '&_dc=' + date.getTime() + '" />');
                        }.bind(this));
                }.bind(this)
            }]
        });

        generalItems.push({
            xtype:"textfield",
            fieldLabel:t("firstname"),
            name:"firstname",
            value:this.currentUser.firstname,
            width:300
        });
        generalItems.push({
            xtype:"textfield",
            fieldLabel:t("lastname"),
            name:"lastname",
            value:this.currentUser.lastname,
            width:300
        });
        generalItems.push({
            xtype:"textfield",
            fieldLabel:t("email"),
            name:"email",
            value:this.currentUser.email,
            width:300
        });

        generalItems.push({
            xtype:'combo',
            fieldLabel:t('language'),
            typeAhead:true,
            value:this.currentUser.language,
            mode:'local',
            listWidth:100,
            store:pimcore.globalmanager.get("pimcorelanguages"),
            displayField:'display',
            valueField:'language',
            forceSelection:true,
            triggerAction:'all',
            hiddenName:'language',
            listeners:{
                change:function () {
                    this.forceReloadOnSave = true;
                }.bind(this),
                select:function () {
                    this.forceReloadOnSave = true;
                }.bind(this)
            }
        });

        generalItems.push({
            xtype:"checkbox",
            fieldLabel:t("show_welcome_screen"),
            name:"welcomescreen",
            checked:this.currentUser.welcomescreen
        });

        generalItems.push({
            xtype:"checkbox",
            fieldLabel:t("memorize_tabs"),
            name:"memorizeTabs",
            checked:this.currentUser.memorizeTabs
        });

        generalItems.push({
            xtype:"checkbox",
            fieldLabel:t("show_close_warning"),
            name:"closeWarning",
            checked:this.currentUser.closeWarning
        });

        this.roleField = new Ext.ux.form.MultiSelect({
            name:"roles",
            triggerAction:"all",
            editable:false,
            fieldLabel:t("roles"),
            width:300,
            store:this.data.roles,
            value:this.currentUser.roles.join(","),
            hidden: this.currentUser.admin
        });

        generalItems.push(this.roleField);

        this.generalSet = new Ext.form.FieldSet({
            title:t("general"),
            items:[generalItems]
        });




        var adminItems = [];

        if(user.admin) {
            // only admins are allowed to create new admin users and to manage API related settings
            adminItems.push({
                xtype: "checkbox",
                fieldLabel: t("admin"),
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
                width: 460
            });

            this.apiKeyFieldContainer = new Ext.form.CompositeField({
                items: [this.apiKeyField, {
                    xtype: "button",
                    test: t("Generate"),
                    iconCls: "pimcore_icon_menu_clear_cache",
                    handler: function (e) {
                        this.apiKeyField.setValue(md5(uniqid()) + md5(uniqid()));
                    }.bind(this)
                }],
                hidden: !this.wsenabled
            });

            this.apiKeyDescription = new Ext.form.DisplayField({
                hideLabel: true,
                width: 600,
                value: t("user_apikey_description"),
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
                    url: "/admin/user/get-token-login-link",
                    params: {
                        id: this.currentUser.id
                    },
                    success: function (response) {
                        var res = Ext.decode(response.responseText);
                        if(res["link"]) {
                            Ext.MessageBox.alert("", t("login_as_this_user_description")
                                            + ' <br /><br /><textarea style="width:100%;height:70px;">' + res["link"] + "</textarea>");
                        }
                    }
                });
            }.bind(this)
        });

        this.adminSet = new Ext.form.FieldSet({
            title:t("admin"),
            items:[adminItems]
        });


        var availPermsItems = [];
        // add available permissions
        for (var i = 0; i < this.data.availablePermissions.length; i++) {
            availPermsItems.push({
                xtype:"checkbox",
                fieldLabel:t(this.data.availablePermissions[i].key),
                name:"permission_" + this.data.availablePermissions[i].key,
                checked:this.data.permissions[this.data.availablePermissions[i].key],
                labelStyle:"width: 200px;"
            });
        }

        this.permissionsSet = new Ext.form.FieldSet({
            title:t("permissions"),
            items:[availPermsItems],
            hidden:this.currentUser.admin
        });


        this.typesSet = new Ext.form.FieldSet({
            title:t("allowed_types_to_create") + " (" + t("defaults_to_all") + ")",
            items:[{
                xtype: "multiselect",
                name: "docTypes",
                triggerAction:"all",
                editable:false,
                fieldLabel:t("document_types"),
                width:300,
                displayField: "name",
                valueField: "id",
                store: pimcore.globalmanager.get("document_types_store"),
                value: this.currentUser.docTypes.join(",")
            }, {
                xtype: "multiselect",
                name: "classes",
                triggerAction:"all",
                editable:false,
                fieldLabel:t("classes"),
                width:300,
                displayField: "text",
                valueField: "id",
                store: pimcore.globalmanager.get("object_types_store"),
                value: this.currentUser.classes.join(",")
            }],
            hidden:this.currentUser.admin
        });

        this.panel = new Ext.form.FormPanel({
            title:t("settings"),
            items:[this.generalSet, this.adminSet, this.permissionsSet, this.typesSet],
            bodyStyle:"padding:10px;",
            autoScroll:true
        });

        return this.panel;
    },

    getValues:function () {

        var values = this.panel.getForm().getFieldValues();
        if(values["password"]) {
            if(!/^(?=.*\d)(?=.*[a-zA-Z]).{6,100}$/.test(values["password"])) {
                delete values["password"];
                Ext.MessageBox.alert(t('error'), t("password_was_not_changed"));
            }
        }

        return values;
    }
});