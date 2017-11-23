/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.classes.data.multihrefMetadata");
pimcore.object.classes.data.multihrefMetadata = Class.create(pimcore.object.classes.data.data, {

    type: "multihrefMetadata",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : false,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "multihrefMetadata";

        this.initData(initData);

        if (typeof this.datax.lazyLoading == "undefined") {
            this.datax.lazyLoading = true;
        }

        pimcore.helpers.sanitizeAllowedTypes(this.datax, "classes");
        pimcore.helpers.sanitizeAllowedTypes(this.datax, "assetTypes");
        pimcore.helpers.sanitizeAllowedTypes(this.datax, "documentTypes");

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
            "visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "relation";
    },

    getTypeName: function () {
        return t("multihrefMetadata");
    },

    getIconClass: function () {
        return "pimcore_icon_multihrefMetadata";
    },

    getLayout: function ($super) {

        $super();

        this.uniqeFieldId = uniqid();
        this.specificPanel.removeAll();

        var i;

        var allowedClasses = [];
        if(this.datax.classes && typeof this.datax.classes == "object") {
            // this is when it comes from the server
            for(i=0; i<this.datax.classes.length; i++) {
                allowedClasses.push(this.datax.classes[i]);
            }
        } else if(typeof this.datax.classes == "string") {
            // this is when it comes from the local store
            allowedClasses = this.datax.classes.split(",");
        }

        var allowedDocuments = [];
        if(this.datax.documentTypes && typeof this.datax.documentTypes == "object") {
            // this is when it comes from the server
            for(i=0; i<this.datax.documentTypes.length; i++) {
                allowedDocuments.push(this.datax.documentTypes[i]);
            }
        } else if(typeof this.datax.documentTypes == "string") {
            // this is when it comes from the local store
            allowedDocuments = this.datax.documentTypes.split(",");
        }

        var allowedAssets = [];
        if(this.datax.assetTypes && typeof this.datax.assetTypes == "object") {
            // this is when it comes from the server
            for(i=0; i<this.datax.assetTypes.length; i++) {
                allowedAssets.push(this.datax.assetTypes[i]);
            }
        } else if(typeof this.datax.assetTypes == "string") {
            // this is when it comes from the local store
            allowedAssets = this.datax.assetTypes.split(",");
        }

        var classesStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: '/admin/class/get-tree'
            },
            autoDestroy: true,
            fields: ["text"]
        });
        classesStore.load({
            "callback": function (allowedClasses, success) {
                if (success) {
                    Ext.getCmp('class_allowed_object_classes_' + this.uniqeFieldId).setValue(allowedClasses.join(","));
                }
            }.bind(this, allowedClasses)
        });

        var documentTypeStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: '/admin/class/get-document-types'
            },
            autoDestroy: true,
            fields: ["text"]
        });
        documentTypeStore.load({
            "callback": function (allowedDocuments, success) {
                if (success) {
                    Ext.getCmp('class_allowed_document_types_' + this.uniqeFieldId).setValue(allowedDocuments.join(","));
                }
            }.bind(this, allowedDocuments)
        });

        var assetTypeStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: '/admin/class/get-asset-types'
            },
            autoDestroy: true,
            fields: ["text"]
        });
        assetTypeStore.load({
            "callback": function (allowedAssets, success) {
                if (success) {
                    Ext.getCmp('class_allowed_asset_types_' + this.uniqeFieldId).setValue(allowedAssets.join(","));
                }
            }.bind(this, allowedAssets)
        });



        this.specificPanel.add([
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },{
                xtype: "numberfield",
                fieldLabel: t("maximum_items"),
                name: "maxItems",
                value: this.datax.maxItems,
                disabled: this.isInCustomLayoutEditor(),
                minValue: 0
            },
            {
                xtype: "checkbox",
                fieldLabel: t("lazy_loading"),
                name: "lazyLoading",
                checked: this.datax.lazyLoading && !this.ladyLoadingNotPossible(),
                disabled: this.isInCustomLayoutEditor() || this.ladyLoadingNotPossible()
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
                style: "color:red; font-weight: bold;"
            },
            {
                xtype: 'textfield',
                width: 600,
                fieldLabel: t("path_formatter_class"),
                name: 'pathFormatterClass',
                value: this.datax.pathFormatterClass
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
                            change:function(cbox, checked) {
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
                        width: 400
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
                            change:function(cbox, checked) {
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
                        width: 400
                    })
                    ,
                    {
                        fieldLabel: t("upload_path"),
                        name: "assetUploadPath",
                        hidden: !this.datax.assetsAllowed,
                        id: 'class_asset_upload_path_' + this.uniqeFieldId,
                        cls: "input_drop_target",
                        value: this.datax.assetUploadPath,
                        width: 500,
                        xtype: "textfield",
                        listeners: {
                            "render": function (el) {
                                new Ext.dd.DropZone(el.getEl(), {
                                    //reference: this,
                                    ddGroup: "element",
                                    getTargetFromEvent: function(e) {
                                        return this.getEl();
                                    }.bind(el),

                                    onNodeOver : function(target, dd, e, data) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    },

                                    onNodeDrop : function (target, dd, e, data) {
                                        data = data.records[0].data;
                                        if (data.elementType == "asset") {
                                            this.setValue(data.path);
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
                            change:function(cbox, checked) {
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
                        width: 400
                    })
                ]
            }

        ]);


        this.stores = {};
        this.grids = {};
        this.specificPanel.add(this.getGrid("cols", this.datax.columns, true));


        return this.layout;
    },


    getGrid: function (title, data, hasType) {

        var fields = [
            'position',
            'key',
            'label'
        ];

        if(hasType) {
            fields.push('type');
            fields.push('value');
            fields.push('width');
        }

        this.stores[title] = new Ext.data.JsonStore({
            autoDestroy: false,
            autoSave: false,
            idIndex: 1,
            fields: fields
        });

        if(!data || data.length < 1) {
            data = [];
        }

        if(data) {
            this.stores[title].loadData(data);
        }

        var keyTextField = new Ext.form.TextField({
            //validationEvent: false,
            validator: function(value) {
                value = trim(value);
                var regresult = value.match(/[a-zA-Z0-9_]+/);

                if (value.length > 1 && regresult == value
                    && in_array(value.toLowerCase(), ["id","key","path","type","index","classname",
                        "creationdate","userowner","value","class","list","fullpath","childs","values","cachetag",
                        "cachetags","parent","published","valuefromparent","userpermissions","dependencies",
                        "modificationdate","usermodification","byid","bypath","data","versions","properties",
                        "permissions","permissionsforuser","childamount","apipluginbroker","resource",
                        "parentClass","definition","locked","language"]) == false) {
                    return true;
                } else {
                    return t("objectsMetadata_invalid_key");
                }
            }
        });


        var typesColumns = [
            {header: t("position"), width: 65, sortable: true, dataIndex: 'position',
                editor: new Ext.form.NumberField({})},
            {header: t("key"), flex: 40, sortable: true, dataIndex: 'key', editor: keyTextField},
            {header: t("label"), flex: 40, sortable: true, dataIndex: 'label', editor: new Ext.form.TextField({})}
        ];

        if(hasType) {
            var types = {
                number: t("objectsMetadata_type_number"),
                text: t("objectsMetadata_type_text"),
                select: t("objectsMetadata_type_select"),
                bool: t("objectsMetadata_type_bool"),
                columnbool: t("objectsMetadata_type_columnbool"),
                multiselect: t("objectsMetadata_type_multiselect")
            };

            var typeComboBox = new Ext.form.ComboBox({
                triggerAction: 'all',
                allowBlank: false,
                lazyRender: true,
                editable: false,
                mode: 'local',
                store: new Ext.data.ArrayStore({
                    id: 'value',
                    fields: [
                        'value',
                        'label'
                    ],
                    data: [
                        ['number', types.number],
                        ['text', types.text],
                        ['select', types.select],
                        ['bool', types.bool],
                        ['columnbool', types.columnbool],
                        ['multiselect', types.multiselect]
                    ]
                }),
                valueField: 'value',
                displayField: 'label'
            });

            typesColumns.push({header: t("type"), width: 120, sortable: true, dataIndex: 'type', editor: typeComboBox,
                renderer: function(value) {
                    return types[value];
                }});
            typesColumns.push({header: t("value"), flex: 80, sortable: true, dataIndex: 'value',
                editor: new Ext.form.TextField({})});
            typesColumns.push({header: t("width"), width: 80, sortable: true, dataIndex: 'width',
                editor: new Ext.form.NumberField({})});


        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });


        this.grids[title] = Ext.create('Ext.grid.Panel', {
            title: t(title),
            autoScroll: true,
            autoDestroy: false,
            store: this.stores[title],
            height: 200,
            columns : typesColumns,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            columnLines: true,
            name: title,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this, this.stores[title], hasType),
                    iconCls: "pimcore_icon_add"
                },
                '-',
                {
                    text: t('delete'),
                    handler: this.onDelete.bind(this, this.stores[title], title),
                    iconCls: "pimcore_icon_delete"
                },
                '-'
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grids[title];
    },

    onAdd: function (store, hasType, btn, ev) {
        var u = {};
        if(hasType) {
            u.type = "text";
        }
        u.position = store.getCount() + 1;
        u.key = "name";
        store.add(u);
    },

    onDelete: function (store, title) {
        if(store.getCount() > 0) {
            var selections = this.grids[title].getSelectionModel().getSelected();
            if (!selections || selections.getCount() == 0) {
                return false;
            }
            var rec = selections.getAt(0);
            store.remove(rec);
        }
    } ,

    getData: function () {
        if(this.grids) {
            var cols = [];
            this.stores.cols.each(function(rec) {
                cols.push(rec.data);
                rec.commit();
            });
            this.datax.columns = cols;
        }

        return this.datax;
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
                    columns: source.datax.columns,
                    remoteOwner: source.datax.remoteOwner,
                    lazyLoading: source.datax.lazyLoading,
                    assetUploadPath: source.datax.assetUploadPath,
                    relationType: source.datax.relationType,
                    objectsAllowed: source.datax.objectsAllowed,
                    assetsAllowed: source.datax.assetsAllowed,
                    assetTypes: source.datax.assetTypes,
                    documentsAllowed: source.datax.documentsAllowed,
                    documentTypes: source.datax.documentTypes
                });
        }
    }

});
