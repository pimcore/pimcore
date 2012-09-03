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

pimcore.registerNS("pimcore.document.pages.target.item");
pimcore.document.pages.target.item = Class.create({

    initialize: function(parent, data) {
        this.parent = parent;
        this.data = data;

        this.parent.panel.setTitle(this.data.name);

        this.tabPanel = new Ext.TabPanel({
            activeTab: 0,
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: this.save.bind(this)
            }],
            items: [this.getSettings(), {
                title: t("edit")
            }]
        });

        this.parent.panel.add(this.tabPanel);
        this.parent.panel.doLayout();
    },

    getSettings: function () {

        this.settings = new Ext.form.FormPanel({
            title: this.data.name + " | " + t("settings"),
            layout: "pimcoreform",
            bodyStyle: "padding: 10px;",
            items: [{
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                width: 250,
                disabled: true,
                value: this.data.name
            }, {
                name: "description",
                fieldLabel: t("description"),
                xtype: "textarea",
                width: 400,
                height: 100,
                value: this.data.description
            }]
        });

        return this.settings;
    },

    save: function () {

        var saveData = this.settings.getForm().getFieldValues();
        saveData["id"] = this.data.id;

        Ext.Ajax.request({
            url: "/admin/page/targeting-save",
            params: saveData,
            method: "post",
            success: function () {

            }.bind(this)
        });
    }

});
