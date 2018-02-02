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


pimcore.registerNS("pimcore.settings.user.role.settings");
pimcore.settings.user.role.settings = Class.create({

    initialize: function (rolePanel) {
        this.rolePanel = rolePanel;
        this.data = this.rolePanel.data;
    },

    getPanel: function () {

        var generalItems = [];

        generalItems.push({
            xtype:"displayfield",
            fieldLabel:t("id"),
            value: this.data.role.id
        });

        var perspectivesStore = Ext.create('Ext.data.JsonStore', {
            data: this.data.availablePerspectives
        });

        this.perspectivesField = Ext.create('Ext.ux.form.MultiSelect', {
            name:"perspectives",
            triggerAction:"all",
            editable:false,
            fieldLabel:t("perspectives"),
            width:400,
            minHeight: 100,
            store: perspectivesStore,
            displayField: "name",
            valueField: "name",
            value:this.data.role.perspectives ? this.data.role.perspectives.join(",") : null
        });

        generalItems.push(this.perspectivesField);


        this.generalSet = new Ext.form.FieldSet({
            collapsible: true,
            title:t("general"),
            items:generalItems
        });

        var availPermsItems = [];
        // add available permissions
        for (var i = 0; i < this.data.availablePermissions.length; i++) {
            availPermsItems.push({
                xtype: "checkbox",
                fieldLabel: t(this.data.availablePermissions[i].key),
                name: "permission_" + this.data.availablePermissions[i].key,
                checked: this.data.permissions[this.data.availablePermissions[i].key],
                labelWidth: 200
            });
        }

        this.permissionsSet = new Ext.form.FieldSet({
            collapsible: true,
            title: t("permissions"),
            items: availPermsItems
        });

        this.typesSet = new Ext.form.FieldSet({
            collapsible: true,
            title:t("allowed_types_to_create") + " (" + t("defaults_to_all") + ")",
            items:[{
                xtype: "multiselect",
                name: "docTypes",
                triggerAction:"all",
                editable:false,
                fieldLabel:t("document_types"),
                width: 400,
                displayField: "name",
                valueField: "id",
                store: pimcore.globalmanager.get("document_types_store"),
                value: this.data.docTypes
            }, {
                xtype: "multiselect",
                name: "classes",
                triggerAction:"all",
                editable:false,
                fieldLabel:t("classes"),
                width: 400,
                displayField: "text",
                valueField: "id",
                store: pimcore.globalmanager.get("object_types_store"),
                value: this.data.classes
            }]
        });

        this.websiteTranslationSettings = new pimcore.settings.user.websiteTranslationSettings(this, this.data.validLanguages, this.data.role);

        this.panel = new Ext.form.FormPanel({
            title: t("settings"),
            items: [this.generalSet, this.permissionsSet, this.typesSet, this.websiteTranslationSettings.getPanel()],
            bodyStyle: "padding:10px;",
            autoScroll: true
        });

        return this.panel;
    },

    getValues: function () {
        var values = this.panel.getForm().getFieldValues();

        values.websiteTranslationLanguagesEdit = this.websiteTranslationSettings.getLanguages("edit");
        values.websiteTranslationLanguagesView = this.websiteTranslationSettings.getLanguages("view");

        return values;
    }
});