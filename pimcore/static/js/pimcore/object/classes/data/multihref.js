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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
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
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
                                        "visibleGridView","visibleSearch","style"];

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
        var i;

        var allowedClasses = [];
        if(typeof this.datax.classes == "object") {
            // this is when it comes from the server
            for(i=0; i<this.datax.classes.length; i++) {
                allowedClasses.push(this.datax.classes[i]["classes"]);
            }
        } else if(typeof this.datax.classes == "string") {
            // this is when it comes from the local store
            allowedClasses = this.datax.classes.split(",");
        }

        var allowedDocuments = [];
        if(typeof this.datax.documentTypes == "object") {
            // this is when it comes from the server
            for(i=0; i<this.datax.documentTypes.length; i++) {
                allowedDocuments.push(this.datax.documentTypes[i]["documentTypes"]);
            }
        } else if(typeof this.datax.documentTypes == "string") {
            // this is when it comes from the local store
            allowedDocuments = this.datax.documentTypes.split(",");
        }

        var allowedAssets = [];
        if(typeof this.datax.assetTypes == "object") {
            // this is when it comes from the server
            for(i=0; i<this.datax.assetTypes.length; i++) {
                allowedAssets.push(this.datax.assetTypes[i]["assetTypes"]);
            }
        } else if(typeof this.datax.assetTypes == "string") {
            // this is when it comes from the local store
            allowedAssets = this.datax.assetTypes.split(",");
        }

        var classesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/class/get-tree',
            fields: ["text"]
        });
        classesStore.load({
            "callback": function (allowedClasses) {
                Ext.getCmp('class_allowed_object_classes_' + this.uniqeFieldId).setValue(allowedClasses.join(","));
            }.bind(this, allowedClasses)
        });

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
                    },{
                        xtype: "spinnerfield",
                        fieldLabel: t("maximum_items"),
                        name: "maxItems",
                        value: this.datax.maxItems
                    },
                    {
                        xtype: "checkbox",
                        fieldLabel: t("lazy_loading"),
                        name: "lazyLoading",
                        checked: this.datax.lazyLoading,
                        disabled: this.isInCustomLayoutEditor()

                    },
                    {
                        xtype: "displayfield",
                        hideLabel: true,
                        value: t('lazy_loading_description'),
                        cls: "pimcore_extra_label_bottom",
                        style: "padding-bottom:0;"
                    },
                    {
                        xtype: "displayfield",
                        hideLabel: true,
                        value: t('lazy_loading_warning'),
                        cls: "pimcore_extra_label_bottom",
                        style: "color:red; font-weight: bold; padding-bottom:0;"
                    }
                ]
            },
            {
                xtype:'fieldset',
                title: t('document_restrictions'),
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                disabled: this.isInCustomLayoutEditor(),
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
                        store: documentTypeStore,
                        width: 300
                    })
                ]
            },
            {
                xtype:'fieldset',
                title: t('asset_restrictions'),
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                disabled: this.isInCustomLayoutEditor(),
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
                                    Ext.getCmp('class_asset_upload_path_' + this.uniqeFieldId).show();
                                } else {
                                    Ext.getCmp('class_allowed_asset_types_' + this.uniqeFieldId).hide();
                                    Ext.getCmp('class_asset_upload_path_' + this.uniqeFieldId).hide();

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
                        store: assetTypeStore,
                        width: 300
                    }), {
                        fieldLabel: t("upload_path"),
                        name: "assetUploadPath",
                        hidden: !this.datax.assetsAllowed,
                        id: 'class_asset_upload_path_' + this.uniqeFieldId,
                        cls: "input_drop_target",
                        value: this.datax.assetUploadPath,
                        width: 250,
                        xtype: "textfield",
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
                                        if (data.node.attributes.elementType == "asset") {
                                            this.setValue(data.node.attributes.path);
                                            return true;
                                        }
                                        return false;
                                    }.bind(el)
                                });
                            }
                        }
                    }
                ]
            },
            {
                xtype:'fieldset',
                title: t('object_restrictions') ,
                collapsible: false,
                autoHeight:true,
                labelWidth: 100,
                disabled: this.isInCustomLayoutEditor(),
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
                        store: classesStore,
                        width: 300
                    })
                ]
            }


        ]);

        return this.layout;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    height: source.datax.height,
                    maxItems: source.datax.maxItems,
                    assetUploadPath: source.datax.assetUploadPath,
                    relationType: source.datax.relationType,
                    objectsAllowed: source.datax.objectsAllowed,
                    assetsAllowed: source.datax.assetsAllowed,
                    assetTypes: source.datax.assetTypes,
                    documentsAllowed: source.datax.documentsAllowed,
                    documentTypes: source.datax.documentTypes,
                    remoteOwner: source.datax.remoteOwner,
                    lazyLoading: source.datax.lazyLoading,
                    classes: source.datax.classes
                });
        }
    }

});
