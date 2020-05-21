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

/*global google */
pimcore.registerNS("pimcore.settings.targeting.targetGroups.item");
pimcore.settings.targeting.targetGroups.item = Class.create({

    initialize: function(parent, data) {
        this.parent = parent;
        this.data = data;
        this.currentIndex = 0;

        var panel = this.getSettings();

        this.parent.panel.add(panel);
        this.parent.panel.setActiveTab(panel);
        this.parent.panel.updateLayout();
    },

    getSettings: function () {
        this.settingsForm = new Ext.form.FormPanel({
            id: "pimcore_target_groups_panel_" + this.data.id,
            title: this.data.name,
            closable: true,
            deferredRender: false,
            forceLayout: true,
            bodyStyle: "padding:10px;",
            autoScroll: true,
            border:false,
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: this.save.bind(this)
            }],
            items: [{
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                width: 350,
                disabled: true,
                value: this.data.name
            }, {
                name: "description",
                fieldLabel: t("description"),
                xtype: "textarea",
                width: 500,
                height: 100,
                value: this.data.description
            }, {
                name: "threshold",
                fieldLabel: t("threshold"),
                xtype: "numberfield",
                value: this.data["threshold"],
                minValue: 1,
                allowDecimals: false
            }, {
                name: "active",
                fieldLabel: t("active"),
                xtype: "checkbox",
                checked: this.data["active"]
            }]
        });

        return this.settingsForm;
    },

    save: function () {
        var saveData = {
            settings: this.settingsForm.getForm().getFieldValues()
        };

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_targeting_targetgroupsave'),
            method: 'PUT',
            params: {
                id: this.data.id,
                data: Ext.encode(saveData)
            },
            success: function () {
                this.parent.getTree().getStore().load();
                pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
            }.bind(this)
        });
    }
});

