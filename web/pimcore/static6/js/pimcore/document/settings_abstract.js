/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.settings_abstract");
pimcore.document.settings_abstract = Class.create({

    initialize: function(document) {
        this.document = document;
    },

    setDocumentType: function (field, newValue, oldValue) {
        var allowedFields = ["module","controller","action","template","legacy"];
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

    getContentMasterFields: function () {
        return {
            xtype:'fieldset',
            title: t('content_master_document'),
            collapsible: true,
            autoHeight:true,
            labelWidth: 200,
            defaultType: 'textfield',
            defaults: {width: 700},
            items :[
                {
                    fieldLabel: t("document"),
                    name: "contentMasterDocumentPath",
                    value: this.document.data.contentMasterDocumentPath,
                    cls: "input_drop_target",
                    id: "contentMasterDocumentPath_" + this.document.id,
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
                                    data = data.records[0].data;
                                    if (data.elementType == "document") {
                                        this.setValue(data.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                },
                {
                    xtype: "toolbar",
                    width: 700,
                    items: ["->", {
                        text:t("delete_master_document"),
                        iconCls:"pimcore_icon_delete",
                        autoWidth:true,
                        handler:function () {
                            Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"),
                                function (buttonValue) {
                                    if (buttonValue == "yes") {
                                        Ext.getCmp("contentMasterDocumentPath_"
                                            + this.document.id).setValue("");
                                        Ext.Ajax.request({
                                            url:"/admin/page/change-master-document/id/"
                                            + this.document.id,
                                            params:{
                                                contentMasterDocumentPath:""
                                            },
                                            success:function () {
                                                this.document.reload();
                                            }.bind(this)
                                        });
                                    }
                                }.bind(this));
                        }.bind(this)
                    }, {
                        text: t("open_master_document"),
                        iconCls: "pimcore_icon_edit",
                        autoWidth: true,
                        handler: function () {
                            var masterPath = Ext.getCmp("contentMasterDocumentPath_" + this.document.id).getValue();
                            pimcore.helpers.openDocumentByPath(masterPath);
                        }.bind(this)
                    },{
                        text:t("apply_new_master_document"),
                        iconCls:"pimcore_icon_apply",
                        autoWidth:true,
                        handler:function () {
                            Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"),
                                function (buttonValue) {
                                    if (buttonValue == "yes") {
                                        Ext.Ajax.request({
                                            url:"/admin/page/change-master-document/id/" + this.document.id,
                                            params:{
                                                contentMasterDocumentPath:Ext.getCmp(
                                                    "contentMasterDocumentPath_" + this.document.id).getValue()
                                            },
                                            success:function () {
                                                this.document.reload();
                                            }.bind(this)
                                        });
                                    }
                                }.bind(this));
                        }.bind(this)
                    }]
                }
            ]
        };
    },

    getPathAndKeyFields: function () {
        return {
            xtype:'fieldset',
            title: t('path_and_key_settings'),
            collapsible: true,
            autoHeight:true,
            defaultType: 'textfield',
            defaults: {
                width: 700,
                labelWidth: 200
            },
            items :[
                {
                    fieldLabel: t('path'),
                    name: 'path',
                    value: this.document.data.path,
                    disabled: true
                },
                {
                    fieldLabel: t('key'),
                    name: 'key',
                    value: this.document.data.key,
                    disabled: true
                },
                {
                    fieldLabel: t('id'),
                    name: 'id',
                    value: this.document.data.id,
                    disabled: true
                }
            ]
        };
    },

    getControllerViewFields: function () {

        var docTypeStore = new Ext.data.Store({
            proxy: {
                url: '/admin/document/get-doc-types?type=' + this.document.getType(),
                type: 'ajax',
                reader: {
                    type: 'json',
                    rootProperty: "docTypes"
                }
            },
            fields: ["id","name","module","controller","action","template"]

        });

        var docTypeValue = this.document.data.docType;
        if (docTypeValue < 1) {
            docTypeValue = "";
        }

        var fieldSet = new Ext.form.FieldSet({
            title: t('controller_and_view_settings'),
            collapsible: true,
            autoHeight:true,
            defaults: {
                labelWidth: 320,
                width: 700
            },
            defaultType: 'textfield',
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
                    value: docTypeValue,
                    listeners: {
                        "select": this.setDocumentType.bind(this)
                    }
                },
                {
                    xtype:'combo',
                    fieldLabel: this.document.data.legacy ? t('module_optional') : t('bundle_optional'),
                    itemId: "bundle",
                    displayField: 'name',
                    valueField: 'name',
                    name: "module",
                    disableKeyFilter: true,
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: "/admin/misc/get-available-modules",
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    triggerAction: "all",
                    mode: "local",
                    id: "pimcore_document_settings_module_" + this.document.id,
                    value: this.document.data.module,
                    listeners: {
                        afterrender: function (el) {
                            el.getStore().load();
                        }
                    }
                },
                {
                    xtype:'combo',
                    fieldLabel: t('controller'),
                    displayField: 'name',
                    valueField: 'name',
                    name: "controller",
                    disableKeyFilter: true,
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: "/admin/misc/get-available-controllers",
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    triggerAction: "all",
                    mode: "local",
                    id: "pimcore_document_settings_controller_" + this.document.id,
                    value: this.document.data.controller,
                    listeners: {
                        "focus": function (el) {
                            el.getStore().reload({
                                params: {
                                    moduleName: Ext.getCmp("pimcore_document_settings_module_"
                                        + this.document.id).getValue()
                                },
                                callback: function() {
                                    el.expand();
                                }
                            });
                        }.bind(this),
                    }
                },
                {
                    xtype:'combo',
                    fieldLabel: t('action'),
                    displayField: 'name',
                    valueField: 'name',
                    name: "action",
                    disableKeyFilter: true,
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: "/admin/misc/get-available-actions",
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    triggerAction: "all",
                    queryMode: "local",
                    value: this.document.data.action,
                    listeners: {
                        "focus": function (el) {
                            el.getStore().reload({
                                params: {
                                    moduleName: Ext.getCmp("pimcore_document_settings_module_"
                                        + this.document.id).getValue(),
                                    controllerName: Ext.getCmp("pimcore_document_settings_controller_"
                                        + this.document.id).getValue()
                                },
                                callback: function() {
                                    el.expand();
                                }
                            });
                        }.bind(this),
                    }
                },
                {
                    xtype:'combo',
                    fieldLabel: t('template'),
                    displayField: 'path',
                    valueField: 'path',
                    name: "template",
                    disableKeyFilter: true,
                    queryMode: "local",
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: "/admin/misc/get-available-templates",
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["path"]
                    }),
                    triggerAction: "all",
                    mode: "local",
                    value: this.document.data.template,
                    listeners: {
                        afterrender: function (el) {
                            el.getStore().load();
                        }
                    }
                }
            ]
        });

        fieldSet.add({
            xtype: "checkbox",
            fieldLabel: t("legacy_mode"),
            name: "legacy",
            checked: this.document.data.legacy,
            hidden: !pimcore.settings.isLegacyModeAvailable,
            listeners: {
                change: function (el, newValue, oldValue) {

                    var text = t("bundle_optional");
                    if(newValue == true) {
                        text = t("module_optional");
                    }

                    fieldSet.getComponent("bundle").setFieldLabel(text);
                }
            }
        });

        return fieldSet;
    }
});
