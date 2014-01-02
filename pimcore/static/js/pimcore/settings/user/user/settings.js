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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
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
                    if(/^(?=.*\d)(?=.*[a-zA-Z]).{6,50}$/.test(el.getValue())) {
                        el.getEl().addClass("password_valid");
                        el.getEl().removeClass("password_invalid");
                    } else {
                        el.getEl().addClass("password_invalid");
                        el.getEl().removeClass("password_valid");
                    }
                }
            }
        });

        this.apiPasswordHint = new Ext.form.DisplayField({
            xtype:"displayfield",
            hideLabel:true,
            width:600,
            value:t("user_apikey_change_warning"),
            cls:"pimcore_extra_label_bottom",
            hidden:true
        });
        generalItems.push(this.apiPasswordHint);

        if (this.wsenabled) {
            this.apiPasswordHint.show();
        }

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
                    pimcore.helpers.uploadDialog("/admin/user/upload-image?id=" + this.currentUser.id, null, function () {
                        var cont = Ext.getCmp("pimcore_user_image_" + this.currentUser.id);
                        var date = new Date();
                        cont.update('<img src="/admin/user/get-image?id=' + this.currentUser.id + '&_dc=' + date.getTime() + '" />');
                    }.bind(this))
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
            fieldLabel:t("admin"),
            name:"admin",
            disabled:user.id == this.currentUser.id,
            checked:this.currentUser.admin,
            handler:function (box, checked) {

                // enable / disable the permission fieldset
                var pfs = this.permissionsSet;
                var childs = pfs.findByType("checkbox");
                if (checked == true) {
                    pfs.disable();
                }
                else {
                    pfs.enable();
                }

                for (var i = 0; i < childs.length; i++) {
                    childs[i].setValue(checked);
                }

                if (checked == true) {
                    //this.apiKeyField.show();
                    //this.apiKeyDescription.show();
                    //this.apiPasswordHint.show();
                    this.roleField.hide();
                    this.typesSet.hide();
                    this.userPanel.workspaces.disable();
                } else {
                    //this.apiKeyField.hide();
                    //this.apiKeyDescription.hide();
                    //this.apiPasswordHint.hide();
                    this.roleField.show();
                    this.typesSet.show();
                    this.userPanel.workspaces.enable();
                }
            }.bind(this)
        });

        generalItems.push({
            xtype:"displayfield",
            hideLabel:true,
            width:600,
            value:t("user_admin_description"),
            cls:"pimcore_extra_label_bottom"
        });

        this.apiKeyField = new Ext.form.DisplayField({
            xtype:"displayfield",
            fieldLabel:t("apikey"),
            name:"apikey",
            value:this.currentUser.password,
            width:300
        });

        this.apiKeyDescription = new Ext.form.DisplayField({
            xtype:"displayfield",
            hideLabel:true,
            width:600,
            value:t("user_apikey_description"),
            cls:"pimcore_extra_label_bottom"
        });

        generalItems.push(this.apiKeyField);
        generalItems.push(this.apiKeyDescription);

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
            value:this.currentUser.roles.join(",")
        });

        generalItems.push(this.roleField);

        if (this.wsenabled && this.currentUser.admin) {
            this.apiKeyField.show();
            this.apiKeyDescription.show();
            this.roleField.hide();
        }

        this.generalSet = new Ext.form.FieldSet({
            title:t("general"),
            items:[generalItems]
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
            disabled:this.currentUser.admin
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
            disabled:this.currentUser.admin
        });

        this.panel = new Ext.form.FormPanel({
            title:t("settings"),
            items:[this.generalSet, this.permissionsSet, this.typesSet],
            bodyStyle:"padding:10px;",
            autoScroll:true
        });

        return this.panel;
    },

    getValues:function () {

        var values = this.panel.getForm().getFieldValues();
        if(values["password"]) {
            if(!/^(?=.*\d)(?=.*[a-zA-Z]).{6,50}$/.test(values["password"])) {
                delete values["password"];
                Ext.MessageBox.alert(t('error'), t("password_was_not_changed"));
            }
        }

        return values;
    }
});