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

pimcore.registerNS("pimcore.document.emails.settings");
pimcore.document.emails.settings = Class.create({

    initialize: function(email) {
        this.email = email;
    },


    getLayout: function () {

        if (this.layout == null) {

            var docTypeStore = new Ext.data.JsonStore({
                url: '/admin/document/get-doc-types?type=email',
                fields: ["id","name","controller","action","template","to","from","cc","bcc","subject"],
                root: "docTypes"
            });

            var docTypeValue = this.email.data.docType;
            if (docTypeValue < 1) {
                docTypeValue = "";
            }

            this.layout = new Ext.FormPanel({

                title: t('settings'),
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_settings",
                items: [
                    {
                        xtype:'fieldset',
                        title: t('email_settings'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items :[
                            {
                                fieldLabel: t('email_subject'),
                                name: 'subject',
                                value: this.email.data.subject
                            },
                            {
                                fieldLabel: t('email_from'),
                                name: 'from',
                                value: this.email.data.from
                            },
                            {
                                fieldLabel: t('email_to'),
                                name: 'to',
                                value: this.email.data.to
                            },
                            {
                                fieldLabel: t('email_cc'),
                                name: 'cc',
                                value: this.email.data.cc
                            },
                            {
                                fieldLabel: t('email_bcc'),
                                name: 'bcc',
                                value: this.email.data.bcc
                            },
                            {
                                xtype: "displayfield",
                                width: 600,
                                value: t("email_settings_receiver_description"),
                                style: "font-size: 10px;"
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
                                lazyInit: false,
                                triggerAction: 'all',
                                width: 400,
                                listWidth: 400,
                                value: docTypeValue,
                                listeners: {
                                    "select": this.setDocumentType.bind(this)
                                }
                            },
                            {
                                fieldLabel: t('module_optional'),
                                name: 'module',
                                value: this.email.data.module
                            },
                            {
                                fieldLabel: t('controller'),
                                name: 'controller',
                                value: this.email.data.controller
                            },
                            {
                                fieldLabel: t('action'),
                                name: 'action',
                                value: this.email.data.action
                            },
                            {
                                fieldLabel: t('template'),
                                name: 'template',
                                value: this.email.data.template
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
                                value: this.email.data.path,
                                disabled: true
                            },
                            {
                                fieldLabel: t('key'),
                                name: 'key',
                                value: this.email.data.key,
                                disabled: true
                            },
                            {
                                fieldLabel: t('id'),
                                name: 'id',
                                value: this.email.data.id,
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