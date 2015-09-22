/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.object.fieldcollections.field");
pimcore.object.fieldcollections.field = Class.create(pimcore.object.classes.klass, {

    allowedInType: 'fieldcollection',
    disallowedDataTypes: ["nonownerobjects","user","fieldcollections","localizedfields", "objectbricks",
                                                                "objectsMetadata", "keyValue"],
    uploadUrl: '/admin/class/import-fieldcollection/',
    exportUrl: "/admin/class/export-fieldcollection",

    
    getId: function(){
        return  this.data.key;
    },

    getRootPanel: function () {

        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            bodyStyle: "padding: 10px;",
            id: "pimcore_fieldcollection_editor_panel_" + this.getId(),
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