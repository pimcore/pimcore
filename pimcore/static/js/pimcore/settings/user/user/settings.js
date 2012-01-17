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
            xtype: "checkbox",
            fieldLabel: t("active"),
            name: "active",
            checked: this.currentUser.active
        });

        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("username"),
            value: this.currentUser.name,
            width: 300,
            disabled: true
        });
        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("password"),
            name: "password",
            inputType: "password",
            width: 300
        });

        if(this.wsenabled && this.currentUser.admin){
            generalItems.push({
                xtype: "displayfield",
                hideLabel: true,
                width: 600,
                value: t("user_apikey_change_warning"),
                cls: "pimcore_extra_label_bottom"
            });
        }

        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("firstname"),
            name: "firstname",
            value: this.currentUser.firstname,
            width: 300
        });
        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("lastname"),
            name: "lastname",
            value: this.currentUser.lastname,
            width: 300
        });
        generalItems.push({
            xtype: "textfield",
            fieldLabel: t("email"),
            name: "email",
            value: this.currentUser.email,
            width: 300
        });

        generalItems.push({
            xtype:'combo',
            fieldLabel: t('language'),
            typeAhead:true,
            value: this.currentUser.language,
            mode: 'local',
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

        generalItems.push({
            xtype: "checkbox",
            fieldLabel: t("admin"),
            name: "admin",
            checked: this.currentUser.admin,
            handler: function (box, checked) {
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
            }.bind(this)
        });

        generalItems.push({
            xtype: "displayfield",
            hideLabel: true,
            width: 600,
            value: t("user_admin_description"),
            cls: "pimcore_extra_label_bottom"
        });

         if(this.wsenabled && this.currentUser.admin){

            generalItems.push({
                xtype: "displayfield",
                fieldLabel: t("apikey"),
                name: "apikey",
                value: this.currentUser.password,
                width: 300
            });

            generalItems.push({
                xtype: "displayfield",
                hideLabel: true,
                width: 600,
                value: t("user_apikey_description"),
                cls: "pimcore_extra_label_bottom"
            });
        }

        this.generalSet = new Ext.form.FieldSet({
            title: t("general"),
            items: [generalItems]
        });


        var availPermsItems = [];
        // add available permissions
        for (var i = 0; i < this.data.availablePermissions.length; i++) {
            availPermsItems.push({
                xtype: "checkbox",
                fieldLabel: t(this.data.availablePermissions[i].translation),
                name: "permission_" + this.data.availablePermissions[i].key,
                checked: this.data.permissions[this.data.availablePermissions[i].key],
                labelStyle: "width: 200px;"
            });
        }

        this.permissionsSet = new Ext.form.FieldSet({
            title: t("permissions"),
            items: [availPermsItems],
            disabled: this.currentUser.admin
        });

        this.panel = new Ext.form.FormPanel({
            title: t("settings"),
            items: [this.generalSet, this.permissionsSet],
            bodyStyle: "padding:10px;",
            autoScroll: true
        });

        return this.panel;
    },

    getValues: function () {
        return this.panel.getForm().getFieldValues();
    }
});