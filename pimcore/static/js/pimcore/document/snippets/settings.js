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

pimcore.registerNS("pimcore.document.snippets.settings");
pimcore.document.snippets.settings = Class.create({

    initialize: function(snippet) {
        this.snippet = snippet;
    },


    getLayout: function () {

        if (this.layout == null) {

            var docTypeStore = new Ext.data.JsonStore({
                url: '/admin/document/get-doc-types?type=snippet',
                fields: ["id","name","module","controller","action","template"],
                root: "docTypes"
            });

            var docTypeValue = this.snippet.data.docType;
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
                                value: this.snippet.data.module
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('controller'),
                                displayField: 'name',
                                valueField: 'name',
                                name: "controller",
                                disableKeyFilter: true,
                                store: new Ext.data.JsonStore({
                                    autoDestroy: true,
                                    url: "/admin/document/get-available-controllers",
                                    root: "data",
                                    fields: ["name"]
                                }),
                                triggerAction: "all",
                                mode: "local",
                                id: "pimcore_document_settings_controller_" + this.snippet.id,
                                value: this.snippet.data.controller,
                                width: 250,
                                listeners: {
                                    afterrender: function (el) {
                                        el.getStore().load();
                                    }
                                }
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('action'),
                                displayField: 'name',
                                valueField: 'name',
                                name: "action",
                                disableKeyFilter: true,
                                store: new Ext.data.JsonStore({
                                    autoDestroy: true,
                                    url: "/admin/document/get-available-actions",
                                    root: "data",
                                    fields: ["name"]
                                }),
                                triggerAction: "all",
                                mode: "local",
                                value: this.snippet.data.action,
                                width: 250,
                                listeners: {
                                    "focus": function (el) {
                                        el.getStore().reload({
                                            params: {
                                                controllerName: Ext.getCmp("pimcore_document_settings_controller_" + this.snippet.id).getValue()
                                            }
                                        });
                                    }.bind(this)
                                }
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('template'),
                                displayField: 'path',
                                valueField: 'path',
                                name: "template",
                                disableKeyFilter: true,
                                store: new Ext.data.JsonStore({
                                    autoDestroy: true,
                                    url: "/admin/document/get-available-templates",
                                    root: "data",
                                    fields: ["path"]
                                }),
                                triggerAction: "all",
                                mode: "local",
                                value: this.snippet.data.template,
                                width: 250,
                                listeners: {
                                    afterrender: function (el) {
                                        el.getStore().load();
                                    }
                                }
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
                                value: this.snippet.data.path,
                                disabled: true
                            },
                            {
                                fieldLabel: t('key'),
                                name: 'key',
                                value: this.snippet.data.key,
                                disabled: true
                            },
                            {
                                fieldLabel: t('id'),
                                name: 'id',
                                value: this.snippet.data.id,
                                disabled: true
                            }
                        ]
                    },{
                        xtype:'fieldset',
                        title: t('content_master_document'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items :[
                            {
                                fieldLabel: t("document"),
                                name: "contentMasterDocumentPath",
                                value: this.snippet.data.contentMasterDocumentPath,
                                cls: "input_drop_target",
                                id: "contentMasterDocumentPath_" + this.snippet.id,
                                listeners: {
                                    "render": function (el) {
                                        new Ext.dd.DropZone(el.getEl(), {
                                            reference: this,
                                            ddGroup: "element",
                                            getTargetFromEvent: function(e) {
                                                return this.getEl();
                                            }.bind(el),

                                            onNodeOver : function(target, dd, e, data) {
                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                            },

                                            onNodeDrop : function (target, dd, e, data) {
                                                if (data.node.attributes.elementType == "document") {
                                                    this.setValue(data.node.attributes.path);
                                                    return true;
                                                }
                                                return false;
                                            }.bind(el)
                                        });
                                    }
                                }
                            }, {
                                xtype:"toolbar",
                                width:605,
                                items:[
                                    {
                                        text:t("apply_new_master_document"),
                                        iconCls:"pimcore_icon_apply",
                                        autoWidth:true,
                                        handler:function () {
                                            Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"), function (buttonValue) {
                                                if (buttonValue == "yes") {
                                                    Ext.Ajax.request({
                                                        url:"/admin/snippet/change-master-document/id/" + this.snippet.id,
                                                        params:{
                                                            contentMasterDocumentPath:Ext.getCmp("contentMasterDocumentPath_" + this.snippet.id).getValue()
                                                        },
                                                        success:function () {
                                                            this.snippet.reload();
                                                        }.bind(this)
                                                    });
                                                }
                                            }.bind(this));
                                        }.bind(this)
                                    },
                                    {
                                        text:t("delete_master_document"),
                                        iconCls:"pimcore_icon_delete",
                                        autoWidth:true,
                                        handler:function () {
                                            Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"), function (buttonValue) {
                                                if (buttonValue == "yes") {
                                                    Ext.getCmp("contentMasterDocumentPath_" + this.snippet.id).setValue("");
                                                    Ext.Ajax.request({
                                                        url:"/admin/snippet/change-master-document/id/" + this.snippet.id,
                                                        params:{
                                                            contentMasterDocumentPath:""
                                                        },
                                                        success:function () {
                                                            this.snippet.reload();
                                                        }.bind(this)
                                                    });
                                                }
                                            }.bind(this));
                                        }.bind(this)
                                    }
                                ]
                            }
                        ]
                    }
                ]
            });
        }

        return this.layout;
    },

    setDocumentType: function (field, newValue, oldValue) {
        var allowedFields = ["module","controller","action","template"];
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

        var fields = ["module","controller","action","template"];
        var form = this.getLayout().getForm();
        var element = null;

        // get values
        var settings = this.getLayout().getForm().getFieldValues();

        return settings;
    }

});