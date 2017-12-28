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

pimcore.registerNS("pimcore.object.helpers.import.saveAndShareTab");
pimcore.object.helpers.import.saveAndShareTab = Class.create({

    initialize: function (config, callback) {

        this.config = config;
        this.callback = callback;
        this.config.shareSettings = this.config.shareSettings || {};
    },

    getPanel: function () {

        var data = this.config;

        var user = pimcore.globalmanager.get("user");

        if (!this.saveAndShareForm) {

            this.saveAndShareForm = Ext.create('Ext.form.FormPanel', {
                defaults: {
                    labelWidth: 200
                },
                bodyStyle: "padding:10px;",
                autoScroll: true,
                border: false,
                iconCls: "pimcore_icon_save_and_share",
                title: t("save_and_share"),
                items: []
            });

            this.rebuildPanel();
        }
        return this.saveAndShareForm;
    },

    rebuildPanel: function () {
        this.saveAndShareForm.removeAll(true);

        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("share_configurations")) {

            this.userStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/user/get-users',
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['id', 'label']
            });

            this.rolesStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/user/get-roles',
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['id', 'label']
            });
        }

        this.nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            name: 'configName',
            length: 50,
            allowBlank: false,
            width: '100%',
            value: this.config.shareSettings ? this.config.shareSettings.configName : ""
        });

        this.descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            name: 'configDescription',
            height: 200,
            width: '100%',
            value: this.config.shareSettings ? this.config.shareSettings.configDescription : ""
        });

        this.saveAndShareForm.add(this.nameField, this.descriptionField);


        if (user.admin) {
            this.shareGlobally = new Ext.form.field.Checkbox(
                {
                    fieldLabel: t("share_globally"),
                    inputValue: true,
                    name: "shareGlobally",
                    value: this.config.shareSettings ? this.config.shareSettings.shareGlobally : false
                }
            );

            this.saveAndShareForm.add(this.shareGlobally);
        }

        if (user.isAllowed("share_configurations")) {
            this.userSharingField = Ext.create('Ext.form.field.Tag', {
                name: "sharedUserIds",
                width: '100%',
                height: 100,
                fieldLabel: t("shared_users"),
                queryDelay: 0,
                resizable: true,
                queryMode: 'local',
                minChars: 1,
                store: this.userStore,
                displayField: 'label',
                valueField: 'id',
                forceSelection: true,
                filterPickList: true,
                value: this.config.shareSettings.sharedUserIds ? this.config.shareSettings.sharedUserIds : ""
            });

            this.rolesSharingField = Ext.create('Ext.form.field.Tag', {
                name: "sharedRoleIds",
                width: '100%',
                height: 100,
                fieldLabel: t("shared_roles"),
                queryDelay: 0,
                resizable: true,
                queryMode: 'local',
                minChars: 1,
                store: this.rolesStore,
                displayField: 'label',
                valueField: 'id',
                forceSelection: true,
                filterPickList: true,
                value: this.config.shareSettings.sharedRoleIds ? this.config.shareSettings.sharedRoleIds : ""
            });

            this.saveAndShareForm.add(this.userSharingField, this.rolesSharingField);
        }
    },

    commitData: function () {
        var form = this.saveAndShareForm.getForm();
        var data = form.getFieldValues();
        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("share_configurations")) {
            if (data.sharedUserIds) {
                data.sharedUserIds = data.sharedUserIds.join();
            }

            if (data.sharedRoleIds) {
                data.sharedRoleIds = data.sharedRoleIds.join();
            }

            this.config.shareSettings = data;
        }

    }
});
