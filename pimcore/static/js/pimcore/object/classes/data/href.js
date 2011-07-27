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

pimcore.registerNS("pimcore.object.classes.data.href");
pimcore.object.classes.data.href = Class.create(pimcore.object.classes.data.data, {

    type: "href",

    initialize: function (treeNode, initData) {
        this.type = "href";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("href");
    },

    getGroup: function () {
        return "relation";
    },

    getIconClass: function () {
        return "pimcore_icon_href";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();


        this.specificPanel.add([
            {
                xtype:'fieldset',
                title: t('layout'),
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                items :[
                    {
                        xtype: "spinnerfield",
                        fieldLabel: t("width"),
                        name: "width",
                        value: this.datax.width
                    } ,
                    {
                        xtype: "checkbox",
                        fieldLabel: t("lazy_loading"),
                        name: "lazyLoading",
                        checked: this.datax.lazyLoading
                    },
                    {
                        xtype: "displayfield",
                        hideLabel: true,
                        value: t('lazy_loading_description'),
                        cls: "pimcore_extra_label_bottom"
                    }
                ]
            },
            {
                xtype:'fieldset',
                title: t('document_restrictions'),
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                items :[
                    {
                        xtype: "checkbox",
                        name: "documentsAllowed",
                        fieldLabel: t("allow_documents"),
                        checked: this.datax.documentsAllowed,
                        listeners:{
                            check:function(cbox, checked) {
                                if (checked) {
                                    Ext.getCmp('class_allowed_document_types').show();
                                } else {
                                    Ext.getCmp('class_allowed_document_types').hide();

                                }
                            }
                        }
                    },
                    new Ext.ux.form.SuperField({
                        allowEdit: this.datax.documentsAllowed,
                        hidden: !this.datax.documentsAllowed,
                        id: "class_allowed_document_types",
                        name: "documentTypes",
                        values:this.datax.documentTypes,
                        stripeRows:false,
                        items: [
                            new Ext.form.ComboBox({
                                fieldLabel: t("allowed_document_types") + ' ' + t('allowed_types_hint'),
                                name: "documentTypes",
                                triggerAction: 'all',
                                editable: false,
                                listWidth: 'auto',
                                store: new Ext.data.JsonStore({
                                    url: '/admin/class/get-document-types',
                                    fields: ["text"]
                                }),
                                displayField: "text",
                                valueField: "text",
                                summaryDisplay:true
                            })
                        ]
                    })
                ]
            }, 
            {
                xtype:'fieldset',
                title: t('asset_restrictions'),
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                items :[
                    {
                        xtype: "checkbox",
                        fieldLabel: t("allow_assets"),
                        name: "assetsAllowed",
                        checked: this.datax.assetsAllowed,
                        listeners:{
                            check:function(cbox, checked) {
                                if (checked) {
                                    Ext.getCmp('class_allowed_asset_types').show();
                                } else {
                                    Ext.getCmp('class_allowed_asset_types').hide();

                                }
                            }
                        }
                    },
                    new Ext.ux.form.SuperField({
                        allowEdit: this.datax.assetsAllowed,
                        hidden: !this.datax.assetsAllowed,
                        id: "class_allowed_asset_types",
                        name: "assetTypes",
                        values:this.datax.assetTypes,
                        stripeRows:false,
                        items: [
                            new Ext.form.ComboBox({
                                fieldLabel: t("allowed_asset_types") + ' ' + t('allowed_types_hint'),
                                name: "assetTypes",
                                triggerAction: 'all',
                                listWidth: 'auto',
                                editable: false,
                                store: new Ext.data.JsonStore({
                                    url: '/admin/class/get-asset-types',
                                    fields: ["text"]
                                }),
                                displayField: "text",
                                valueField: "text",
                                summaryDisplay:true
                            })
                        ]
                    })
                ]
            },
            {
                xtype:'fieldset',
                title: t('object_restrictions') ,
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                items :[
                    {
                        xtype: "checkbox",
                        fieldLabel: t("allow_objects"),
                        name: "objectsAllowed",
                        checked: this.datax.objectsAllowed,
                        listeners:{
                            check:function(cbox, checked) {
                                if (checked) {
                                    Ext.getCmp('class_allowed_object_classes').show();
                                } else {
                                    Ext.getCmp('class_allowed_object_classes').hide();

                                }
                            }
                        }
                    },
                    new Ext.ux.form.SuperField({
                        allowEdit: this.datax.objectsAllowed,
                        hidden: !this.datax.objectsAllowed,
                        id: "class_allowed_object_classes",
                        name: "classes",
                        values:this.datax.classes,
                        stripeRows:false,
                        items: [
                            new Ext.form.ComboBox({
                                fieldLabel: t("allowed_classes") + ' ' + t('allowed_types_hint'),
                                name: "classes",
                                triggerAction: 'all',
                                listWidth: 'auto',
                                editable: false,
                                store: new Ext.data.JsonStore({
                                    url: '/admin/class/get-tree',
                                    fields: ["text","id"]
                                }),
                                displayField: "text",
                                valueField: "text",
                                summaryDisplay:true
                            })
                        ]
                    })
                ]
            }


        ]);


        return this.layout;
    }

});
