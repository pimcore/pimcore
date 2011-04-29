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

pimcore.registerNS("pimcore.element.exporter");
pimcore.element.exporter = Class.create({

    initialize: function(type, id) {

        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:310,
            closeAction:'close',
            modal: true
        });

        pimcore.viewport.add(this.window);

        this.exportForm = new Ext.form.FormPanel({
            title: t('export'),
            bodyStyle: "padding: 20px;",
            layout: "pimcoreform",
            labelWidth: 200,
            items:[
                {
                    xtype: "checkbox",
                    fieldLabel: t('element_export_include_relations'),
                    name: 'includeRelations'
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t('element_export_recursive'),
                    name: 'recursive'
                },
                {
                    xtype: "hidden",
                    name: 'type',
                    value: type
                },
                {
                    xtype: "hidden",
                    name: 'id',
                    value: id
                }
            ],
            buttons:["->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_apply",
                    text: t('export'),
                    handler: this.exportStart.bind(this, type, id)
                }
            ]
        });

        this.window.add(this.exportForm);

        this.window.show();


    },

    exportStart: function() {

        this.exportFormValues = this.exportForm.getForm().getValues();
        Ext.Ajax.request({
            url: "/admin/export/do-export-jobs",
            params: this.exportFormValues,
            method: 'post',
            success: this.detectElements.bind(this)
        });
    },

    detectElements: function(response){
        var r = Ext.decode(response.responseText);
        this.totalElementsFound = r.totalElementsFound;
        this.totalElementsDone = r.totalElementsDone;
        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: t('exporting_elements'),
            bodyStyle: "padding: 20px;",
            html: t('export_items_finished') +': '+ this.totalElementsDone+"<br /><br />"
        }));
        this.window.doLayout();
        if(r.more){
            Ext.Ajax.request({
                url: "/admin/export/do-export-jobs",
                params: this.exportFormValues,
                method: 'post',
                success: this.detectElements.bind(this)
            });
        } else {
            this.startDownload();

        }
    },


    startDownload: function() {
         this.window.hide();
         window.location.href = "/admin/export/get-export-file";

    }


});
