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

pimcore.registerNS("pimcore.object.fieldcollections.field");
pimcore.object.fieldcollections.field = Class.create(pimcore.object.classes.klass, {

    allowedInType: 'fieldcollection',
    disallowedDataTypes: ["nonownerobjects","user","fieldcollections","localizedfields", "objectbricks", "objectsMetadata"],
    uploadUrl: '/admin/class/import-fieldcollection/',
    exportUrl: "/admin/class/export-fieldcollection",

    
    getId: function(){
        return  this.data.key;
    },

    getRootPanel: function () {

        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            bodyStyle: "padding: 10px;",
            layout: "pimcoreform",
            items: [{
                xtype: "textfield",
                width: 250,
                name: "parentClass",
                fieldLabel: t("parent_class"),
                value: this.data.parentClass
            }]
        });

        return this.rootPanel;
    },

    save: function () {

        this.saveCurrentNode();

        var m = Ext.encode(this.getData());
        var n = Ext.encode(this.data);

        if (this.getDataSuccess) {
            Ext.Ajax.request({
                url: "/admin/class/fieldcollection-update",
                method: "post",
                params: {
                    configuration: m,
                    values: n,
                    key: this.data.key
                },
                success: this.saveOnComplete.bind(this)
            });
        }
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getRootNode().reload();
        pimcore.helpers.showNotification(t("success"), t("fieldcollection_saved_successfully"), "success");
    },

    upload: function() {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function() {
            Ext.Ajax.request({
                url: "/admin/class/fieldcollection-get",
                params: {
                    id: this.getId()
                },
                success: function(response) {
                    this.data = Ext.decode(response.responseText);
                    this.parentPanel.getEditPanel().removeAll();
                    this.addLayout();
                    this.initLayoutFields();
                    pimcore.layout.refresh();
                }.bind(this)
            });
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    }


});