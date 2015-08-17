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


pimcore.registerNS("pimcore.settings.user.role.settings");
pimcore.settings.user.role.settings = Class.create({

    initialize: function (rolePanel) {
        this.rolePanel = rolePanel;
        this.data = this.rolePanel.data;
    },

    getPanel: function () {

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

        this.panel = new Ext.form.FormPanel({
            title: t("settings"),
            items: [this.permissionsSet, this.typesSet],
            bodyStyle: "padding:10px;",
            autoScroll: true
        });

        return this.panel;
    },

    getValues: function () {
        return this.panel.getForm().getFieldValues();
    }
});