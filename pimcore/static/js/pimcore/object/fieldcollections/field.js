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


        this.uploadWindow = new Ext.Window({
            layout: 'fit',
            title: t('import_fieldcollection_definition'),
            closeAction: 'close',
            width:400,
            height:400,
            modal: true
        });

        var uploadPanel = new Ext.ux.SwfUploadPanel({
            border: false,
            upload_url: this.getUploadUrl(),
            debug: false,
            post_params: {
                id:this.getId()
            },
            flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
            single_select: false,
            file_queue_limit: 1,
            file_types: "*.xml",
            single_file_select: true,
            confirm_delete: false,
            remove_completed: true, 
            listeners: {
                "fileUploadComplete": function (win) {

                    Ext.Ajax.request({
                        url: "/admin/class/fieldcollection-get",
                        params: {
                            id: this.getId()
                        },
                        success: function(win, response) {
                            this.data = Ext.decode(response.responseText);
                            this.parentPanel.getEditPanel().removeAll();
                            this.addLayout();
                            this.initLayoutFields();
                            pimcore.layout.refresh();
                            win.hide();
                        }.bind(this, win)
                    });


                }.bind(this, this.uploadWindow)
            }
        });

        this.uploadWindow.add(uploadPanel);


        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();


    }


});