/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in 
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
                labelStyle: "width: 200px;"
            });
        }

        this.permissionsSet = new Ext.form.FieldSet({
            title: t("permissions"),
            items: [availPermsItems]
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
                value: this.data.docTypes.join(",")
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
                value: this.data.classes.join(",")
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