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

pimcore.registerNS("pimcore.object.classes.data.multihref");
pimcore.object.classes.data.multihref = Class.create(pimcore.object.classes.data.data, {

    type: "multihref",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "multihref";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible","visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("multihref");
    },

    getGroup: function () {
        return "relation";
    },

    getIconClass: function () {
        return "pimcore_icon_multihref";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();

        this.uniqeFieldId = uniqid();

        var allowedClasses = [];
        for(var i=0; i<this.datax.classes.length; i++) {
            allowedClasses.push(this.datax.classes[i]["classes"]);
        }

        var allowedDocuments = [];
        for(var i=0; i<this.datax.documentTypes.length; i++) {
            allowedDocuments.push(this.datax.documentTypes[i]["documentTypes"]);
        }

        var allowedAssets = [];
        for(var i=0; i<this.datax.assetTypes.length; i++) {
            allowedAssets.push(this.datax.assetTypes[i]["assetTypes"]);
        }

        var documentTypeStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/class/get-document-types',
            fields: ["text"]
        });
        documentTypeStore.load({
            "callback": function (allowedDocuments) {
                Ext.getCmp('class_allowed_document_types_' + this.uniqeFieldId).setValue(allowedDocuments.join(","));
            }.bind(this, allowedDocuments)
        });

        var assetTypeStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/class/get-asset-types',
            fields: ["text"]
        });
        assetTypeStore.load({
            "callback": function (allowedAssets) {
                Ext.getCmp('class_allowed_asset_types_' + this.uniqeFieldId).setValue(allowedAssets.join(","));
            }.bind(this, allowedAssets)
        });


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
                    },
                    {
                        xtype: "spinnerfield",
                        fieldLabel: t("height"),
                        name: "height",
                        value: this.datax.height
                    },
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
                    },{
                        xtype: "spinnerfield",
                        fieldLabel: t("maximum_items"),
                        name: "maxItems",
                        value: this.datax.maxItems
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
                                    Ext.getCmp('class_allowed_document_types_' + this.uniqeFieldId).show();
                                } else {
                                    Ext.getCmp('class_allowed_document_types_' + this.uniqeFieldId).hide();

                                }
                            }.bind(this)
                        }
                    },
                    new Ext.ux.form.MultiSelect({
                        fieldLabel: t("allowed_document_types") + '<br />' + t('allowed_types_hint'),
                        name: "documentTypes",
                        id: 'class_allowed_document_types_' + this.uniqeFieldId,
                        hidden: !this.datax.documentsAllowed,
                        allowEdit: this.datax.documentsAllowed,
                        value: allowedDocuments.join(","),
                        displayField: "text",
                        valueField: "text",
                        store: documentTypeStore
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
                                    Ext.getCmp('class_allowed_asset_types_' + this.uniqeFieldId).show();
                                } else {
                                    Ext.getCmp('class_allowed_asset_types_' + this.uniqeFieldId).hide();

                                }
                            }.bind(this)
                        }
                    },
                    new Ext.ux.form.MultiSelect({
                        fieldLabel: t("allowed_asset_types") + '<br />' + t('allowed_types_hint'),
                        name: "assetTypes",
                        id: 'class_allowed_asset_types_' + this.uniqeFieldId,
                        hidden: !this.datax.assetsAllowed,
                        allowEdit: this.datax.assetsAllowed,
                        value: allowedAssets.join(","),
                        displayField: "text",
                        valueField: "text",
                        store: assetTypeStore
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
                                    Ext.getCmp('class_allowed_object_classes_' + this.uniqeFieldId).show();
                                } else {
                                    Ext.getCmp('class_allowed_object_classes_' + this.uniqeFieldId).hide();

                                }
                            }.bind(this)
                        }
                    },
                    new Ext.ux.form.MultiSelect({
                        fieldLabel: t("allowed_classes") + '<br />' + t('allowed_types_hint'),
                        name: "classes",
                        id: 'class_allowed_object_classes_' + this.uniqeFieldId,
                        hidden: !this.datax.objectsAllowed,
                        allowEdit: this.datax.objectsAllowed,
                        value: allowedClasses.join(","),
                        displayField: "text",
                        valueField: "text",
                        store: pimcore.globalmanager.get("object_types_store")
                    })
                ]
            }


        ]);

        return this.layout;
    }

});
