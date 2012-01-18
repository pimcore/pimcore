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

        this.panel = new Ext.form.FormPanel({
            title: t("settings"),
            items: [this.permissionsSet],
            bodyStyle: "padding:10px;",
            autoScroll: true
        });

        return this.panel;
    },

    getValues: function () {
        return this.panel.getForm().getFieldValues();
    }
});