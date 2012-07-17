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

pimcore.registerNS("pimcore.object.classes.data.objectsMetadata");
pimcore.object.classes.data.objectsMetadata = Class.create(pimcore.object.classes.data.data, {

    type: "objectsMetadata",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false
    },

    initialize: function (treeNode, initData) {
        this.type = "objectsMetadata";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible","visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "relation";
    },

    getTypeName: function () {
        return t("objectsMetadata");
    },

    getIconClass: function () {
        return "pimcore_icon_objectsMetadata";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();

        this.specificPanel.removeAll();
        this.specificPanel.add([
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
        ]);

        this.classCombo = new Ext.form.ComboBox({
            typeAhead: true,
            triggerAction: 'all',
            store: pimcore.globalmanager.get("object_types_store"),
            valueField: 'id',
            displayField: 'text',
            fieldLabel: t('objectsMetadata_allowed_class'),
            name: 'allowedClassId',
            value: this.datax.allowedClassId,
            forceSelection:true,
            listeners: {
                change: function(field, classNamevalue, oldValue) {
                    this.datax.allowedClassId = classNamevalue;
                    if (this.datax.allowedClassId != null) {
                        this.fieldStore.load({params:{id:this.datax.allowedClassId}});
                    }
                }.bind(this)
            }

        });

        this.specificPanel.add(this.classCombo);

        this.fieldStore = new Ext.data.JsonStore({
            url: '/admin/object-helper/grid-get-column-config',
            baseParams: {
                no_system_columns: "true",
                no_brick_columns: "true",
                gridtype: 'all',
                id: this.datax.allowedClassId
            },
            root: "availableFields",
            fields: ['key', 'label'],
            autoLoad: false,
            forceSelection:true,
            listeners: {
                load: function() {
                    this.fieldSelect.setValue(this.datax.visibleFields);
                }.bind(this)

            }
        });
        this.fieldStore.load();

        this.fieldSelect = new Ext.ux.form.MultiSelect({
            name: "visibleFields",
            triggerAction: "all",
            editable: false,
            fieldLabel: t("objectsMetadata_visible_fields"),
            store: this.fieldStore,
            width: 'auto',
            value: this.datax.visibleFields,
            displayField: "key",
            valueField: "key",
            width: 300
        });
        this.specificPanel.add(this.fieldSelect);


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

                if (value.length > 1 && regresult == value && in_array(value.toLowerCase(), ["id","key","path","type","index","classname","creationdate","userowner","value","class","list","fullpath","childs","values","cachetag","cachetags","parent","published","valuefromparent","userpermissions","dependencies","modificationdate","usermodification","byid","bypath","data","versions","properties","permissions","permissionsforuser","childamount","apipluginbroker","resource","parentClass","definition","locked","language"]) == false) {
                    return true;
                } else {
                    return t("objectsMetadata_invalid_key");
                }
            }
        });


        var typesColumns = [
            {header: t("position"), width: 10, sortable: true, dataIndex: 'position', editor: new Ext.form.NumberField({})},
            {header: t("key"), width: 40, sortable: true, dataIndex: 'key', editor: keyTextField},
            {header: t("label"), width: 60, sortable: true, dataIndex: 'label', editor: new Ext.form.TextField({})}
        ];

        if(hasType) {
            var types = {
                number: t("objectsMetadata_type_number"),
                text: t("objectsMetadata_type_text"),
                select: t("objectsMetadata_type_select"),
                bool: t("objectsMetadata_type_bool")
            };

            var typeComboBox = new Ext.form.ComboBox({
                triggerAction: 'all',
                allowBlank: false,
                lazyRender: true,
                mode: 'local',
                store: new Ext.data.ArrayStore({
                    id: 'value',
                    fields: [
                        'value',
                        'label'
                    ],
                    data: [['number', types.number], ['text', types.text], ['select', types.select], ['bool', types.bool]]
                }),
                valueField: 'value',
                displayField: 'label'
            });

            typesColumns.push({header: t("type"), width: 30, sortable: true, dataIndex: 'type', editor: typeComboBox, renderer: function(value) {
                return types[value];
            }});
            typesColumns.push({header: t("value"), width: 100, sortable: true, dataIndex: 'value', editor: new Ext.form.TextField({})});
            typesColumns.push({header: t("width"), width: 10, sortable: true, dataIndex: 'width', editor: new Ext.form.NumberField({})});


        }



        this.grids[title] = new Ext.grid.EditorGridPanel({
            title: t(title),
            autoScroll: true,
            autoDestroy: false,
            store: this.stores[title],
            height: 200,
            columns : typesColumns,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
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
        var u = new store.recordType();
        if(hasType) {
            u.data.type = "text";
        }
        u.data.position = store.getCount() + 1;
        u.data.key = "name";
        store.add(u);
    },

    onDelete: function (store, title) {
        if(store.getCount() > 1) {
            var rec = this.grids[title].getSelectionModel().getSelected();
            if (!rec) {
                return false;
            }
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
    }

});
