/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.classes.data.structuredTable");
pimcore.object.classes.data.structuredTable = Class.create(pimcore.object.classes.data.data, {

    type: "structuredTable",
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
        this.type = "structuredTable";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
            return "structured";
    },

    getTypeName: function () {
        return t("structuredTable");
    },

    getIconClass: function () {
        return "pimcore_icon_structuredTable";
    },

    getLayout: function ($super) {
        this.grids = {};
        this.stores = {};

        $super();

        this.specificPanel.removeAll();
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
            },
            {
                xtype: "numberfield",
                fieldLabel: t("label_width"),
                name: "labelWidth",
                value: this.datax.labelWidth
            },
            {
                xtype: "textfield",
                fieldLabel: t("label_first_cell"),
                name: "labelFirstCell",
                value: this.datax.labelFirstCell
            },
            this.getGrid("rows", this.datax.rows, false),
            this.getGrid("cols", this.datax.cols, true)
        ]);

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
            fields.push('length');
            fields.push('width');
        }

        this.stores[title] = new Ext.data.JsonStore({
            autoDestroy: false,
            autoSave: false,
            idIndex: 1,
            fields: fields
        });

        if(!data || data.length < 1) {
            var d = {position:1, key: "", label: ""};
            if(hasType) {
                d.type = "text";
            }
            data = [d];
        }

        if(data) {
            this.stores[title].loadData(data);
        }

        var keyTextField = new Ext.form.TextField({
            //validationEvent: false,
            validator: function(value) {
                value = trim(value);
                var regresult = value.match(/[a-zA-Z0-9_]+/);

                if (value.length > 1 && regresult == value && in_array(value.toLowerCase(),
                                    ["id","key","path","type","index","classname","creationdate","userowner",
                                     "value","class","list","fullpath","childs","values","cachetag","cachetags",
                                     "parent","published","valuefromparent","userpermissions","dependencies",
                                     "modificationdate","usermodification","byid","bypath","data","versions",
                                     "properties","permissions","permissionsforuser","childamount","apipluginbroker",
                                     "resource","parentClass","definition","locked","language"]) == false) {
                    return true;
                } else {
                    return t("invalid_name");
                }
            }
        });


        var typesColumns = [
            {text: t("position"), width: 100, sortable: true, dataIndex: 'position',
                                    editor: new Ext.form.NumberField({})},
            {text: t("key"), flex: 50, sortable: true, dataIndex: 'key',
                                    editor: keyTextField},
            {text: t("label"), flex: 150, sortable: true, dataIndex: 'label',
                                    editor: new Ext.form.TextField({})}
        ];

        if(hasType) {
            var types = {
                number: t("structuredtable_type_number"),
                text: t("structuredtable_type_text"),
                bool: t("structuredtable_type_bool")
            };

            var typeComboBox = new Ext.form.ComboBox({
                triggerAction: 'all',
                allowBlank: false,
                lazyRender: true,
                mode: 'local',
                editable: false,
                store: new Ext.data.ArrayStore({
                    id: 'value',
                    fields: [
                        'value',
                        'label'
                    ],
                    data: [['number', types.number], ['text', types.text], ['bool', types.bool]]
                }),
                valueField: 'value',
                displayField: 'label'
            });

            typesColumns.push({text: t("type"), flex: 30, sortable: true, dataIndex: 'type',
                                        editor: typeComboBox, renderer: function(value) {
                return types[value];
            }});

            typesColumns.push({text: t("length"), width: 70, sortable: true, dataIndex: 'length',
                editor: new Ext.form.NumberField({})});

            typesColumns.push({text: t("width"), width: 70, sortable: true, dataIndex: 'width',
                                        editor: new Ext.form.NumberField({})});

        }

        typesColumns.push({xtype: 'actioncolumn', menuText: t('remove'),width: 40,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }
            ]
        });


        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                edit: function (editor, e) {
                    if (!e.record.get('label')) {
                        e.record.set('label', e.record.get('key'));
                    }
                },
            }
        });


        this.grids[title] = Ext.create('Ext.grid.Panel', {
            title: t(title),
            autoScroll: true,
            autoDestroy: false,
            store: this.stores[title],
            height: 200,
            plugins: [cellEditing],
            columns : typesColumns,
            selModel: new Ext.selection.RowModel({singleSelect:true}),
            columnLines: true,
            name: title,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this, this.stores[title], hasType),
                    iconCls: "pimcore_icon_add"
                },
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grids[title];
    },


    onAdd: function (store, hasType, btn, ev) {
        var u = {}
        if(hasType) {
            u.type = "text";
        }
        if (store.getAt(store.getCount() - 1)) {
            u.position = store.getAt(store.getCount() - 1).data.position + 1;
        } else {
            u.position = store.getCount() + 1;
        }

        store.add(u);
    },

    onDelete: function (store, title) {
        if(store.getCount() > 1) {
            var selections = this.grids[title].getSelectionModel().getSelected();
            if (!selections || selections.getCount() == 0) {
                return false;
            }
            var rec = selections.getAt(0);
            store.remove(rec);
        }
    },

    getData: function () {
        if(this.grids) {
            var rows = [];
            this.stores.rows.each(function(rec) {
                rows.push(rec.data);
                rec.commit();
            });
            this.datax.rows = rows;

            var cols = [];
            this.stores.cols.each(function(rec) {
                cols.push(rec.data);
                rec.commit();
            });
            this.datax.cols = cols;
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
                    labelWidth: source.datax.labelWidth,
                    labelFirstCell: source.datax.labelFirstCell,
                    cols: source.datax.cols,
                    rows: source.datax.rows
                });
        }
    }

});
