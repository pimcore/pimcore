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

pimcore.registerNS("pimcore.object.fieldcollections.field");
pimcore.object.fieldcollections.field = Class.create(pimcore.object.classes.klass, {

    allowedInType: 'fieldcollection',
    disallowedDataTypes: ["nonownerobjects","user","fieldcollections","localizedfields", "objectbricks",
                                                                "objectsMetadata"],
    uploadUrl: '/admin/class/import-fieldcollection',
    exportUrl: "/admin/class/export-fieldcollection",

    
    getId: function(){
        return  this.data.key;
    },

    getRootPanel: function () {

        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            bodyStyle: "padding: 10px;",
            //id: "pimcore_fieldcollection_editor_panel_" + this.getId(),
            items: [{
                xtype: "textfield",
                width: 400,
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
        this.parentPanel.tree.getStore().load();
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