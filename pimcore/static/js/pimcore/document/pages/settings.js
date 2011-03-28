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

pimcore.registerNS("pimcore.document.pages.settings");
pimcore.document.pages.settings = Class.create({

    initialize: function(page) {
        this.page = page;
    },


    getLayout: function () {

        if (this.layout == null) {

            var docTypeStore = new Ext.data.JsonStore({
                url: '/admin/document/get-doc-types?type=page',
                fields: ["id","name","controller","action","template"],
                root: "docTypes"
            });

            this.layout = new Ext.FormPanel({
                title: t('settings'),
                bodyStyle:'padding:20px 5px 20px 5px;',
                layout: "pimcoreform",
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_settings",
                items: [
                    {
                        xtype:'fieldset',
                        title: t('name_and_meta_data'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaults: {width: 500},
                        defaultType: 'textarea',
                        items :[
                            

                            {
                                fieldLabel: t('title'),
                                name: 'title',
                                maxLength: 255,
                                height: 51,
                                value: this.page.data.title
                            },
                            {
                                fieldLabel: t('description'),
                                maxLength: 255,
                                height: 51,
                                name: 'description',
                                value: this.page.data.description
                            },
                            {
                                fieldLabel: t('keywords'),
                                name: 'keywords',
                                maxLength: 255,
                                height: 51,
                                value: this.page.data.keywords
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('controller_and_view_settings'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('predefined_document_type'),
                                name: 'docType',
                                xtype: "combo",
                                displayField:'name',
                                valueField: "id",
                                store: docTypeStore,
                                editable: false,
                                triggerAction: 'all',
                                width: 400,
                                listWidth: 400,
                                listeners: {
                                    "select": this.setDocumentType.bind(this)
                                }
                            },
                            {
                                fieldLabel: t('controller'),
                                name: 'controller',
                                value: this.page.data.controller
                            },
                            {
                                fieldLabel: t('action'),
                                name: 'action',
                                value: this.page.data.action
                            },
                            {
                                fieldLabel: t('template'),
                                name: 'template',
                                value: this.page.data.template
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('path_and_key_settings'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items :[
                            {
                                fieldLabel: t('path'),
                                name: 'path',
                                value: this.page.data.path,
                                disabled: true
                            },
                            {
                                fieldLabel: t('key'),
                                name: 'key',
                                value: this.page.data.key,
                                disabled: true
                            },
                            {
                                fieldLabel: t('id'),
                                name: 'id',
                                value: this.page.data.id,
                                disabled: true
                            }
                        ]
                    }
                ]
            });
        }

        return this.layout;
    },

    setDocumentType: function (field, newValue, oldValue) {
        var allowedFields = ["controller","action","template"];
        var form = this.getLayout().getForm();
        var element = null;

        for (var i = 0; i < allowedFields.length; i++) {
            element = form.findField(allowedFields[i]);
            if (element) {
                if (newValue.data.id > 0) {
                    element.setValue(newValue.data[allowedFields[i]]);
                }
            }
        }
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        var fields = ["controller","action","template"];
        var form = this.getLayout().getForm();
        var element = null;


        // get values
        var settings = this.getLayout().getForm().getFieldValues();
        return settings;
    }

});