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
        //Allow following prefixes to be sorted in sections -> rest will be sorted in a general group
        const allowedSectionPrefix = ["cp", "custom", "cust", "c", "app"];

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

        var itemsPerSection = [];
        var sectionArray = [];

        // add available permissions
        for (var i = 0; i < this.data.availablePermissions.length; i++) {
            let sectionSplit = this.data.availablePermissions[i].key.split("_", 2)
            if (sectionSplit && Array.isArray(sectionSplit) && allowedSectionPrefix.includes(sectionSplit[0])) {
                section = sectionSplit[1];
            } else {
                section = "permissions";
            }

            if (!itemsPerSection[section]) {
                itemsPerSection[section] = [];
            }
            itemsPerSection[section].push({
                xtype: "checkbox",
                fieldLabel: t(this.data.availablePermissions[i].key),
                name: "permission_" + this.data.availablePermissions[i].key,
                checked: this.data.permissions[this.data.availablePermissions[i].key],
                labelWidth: 200
            });
        }

        for (var key in itemsPerSection) {
            let title = "permissions";
            if (key !== title) {
                title = "settings_permissions_" + key;
            }

            sectionArray.push(new Ext.form.FieldSet({
                collapsible: true,
                title: t(title),
                items: itemsPerSection[key],
                collapsed: true,
            }));
        }

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

        let items = array_merge([this.generalSet], sectionArray, [this.typesSet, this.websiteTranslationSettings.getPanel()])

        this.panel = new Ext.form.FormPanel({
            title: t("settings"),
            items: items,
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