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

pimcore.registerNS("pimcore.object.classes.data.multiselect");
pimcore.object.classes.data.multiselect = Class.create(pimcore.object.classes.data.data, {

    type: "multiselect",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: true,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "multiselect";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name", "title", "tooltip", "mandatory", "noteditable", "invisible",
            "visibleGridView", "visibleSearch", "style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("multiselect");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_multiselect";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax, false);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {

        var selectionModel;

        if (typeof datax.options != "object") {
            datax.options = [];
        }

        var valueStore = new Ext.data.JsonStore({
            fields: ["key", {name: "value", allowBlank: false}],
            data: datax.options
        });
        
        var valueGrid;

        valueGrid = Ext.create('Ext.grid.Panel', {
            itemId: "valueeditor",
            viewConfig: {
                plugins: [
                    {
                        ptype: 'gridviewdragdrop',
                        dragroup: 'objectclassselect'
                    }
                ]
            },
            plugins: [Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    edit: function(editor, e) {
                        if(!e.record.get('value')) {
                            e.record.set('value', e.record.get('key'));
                        }
                    },
                    beforeedit: function(editor, e) {
                        if(e.field === 'value') {
                            return !!e.value;
                        }
                        return true;
                    },
                    validateedit: function(editor, e) {
                        if(e.field !== 'value') {
                            return true;
                        }

                        // Iterate to all store data
                        for(var i=0; i < valueStore.data.length; i++) {
                            var existingRecord = valueStore.getAt(i);
                            if(i != e.rowIdx && existingRecord.get('value') === e.value) {
                                return false;
                            }
                        }
                        return true;
                    }
                }
            })],
            tbar: [{
                xtype: "tbtext",
                text: t("selection_options")
            }, "-", {
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    var u = {
                        key: "",
                        value: ""
                    };

                    var selectedRow = selectionModel.getSelected();
                    var idx;
                    if (selectedRow) {
                        idx = valueStore.indexOf(selectedRow) + 1;
                    } else {
                        idx = valueStore.getCount();
                    }
                    valueStore.insert(idx, u);
                    selectionModel.select(idx);
                }.bind(this)
            },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_edit",
                    handler: this.showoptioneditor.bind(this, valueStore)

                }
            ],
            style: "margin-top: 10px",
            store: valueStore,
            disabled: this.isInCustomLayoutEditor(),
            selModel: Ext.create('Ext.selection.RowModel', {}),
            columnLines: true,
            columns: [
                {
                    text: t("display_name"),
                    sortable: true,
                    dataIndex: 'key',
                    editor: new Ext.form.TextField({}),
                    renderer: function (value) {
                        return replace_html_event_attributes(strip_tags(value, 'div,span,b,strong,em,i,small,sup,sub'));
                    },
                    width: 200
                },
                {
                    text: t("value"),
                    sortable: true,
                    dataIndex: 'value',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    width: 200
                },
                {
                    xtype: 'actioncolumn',
                    menuText: t('up'),
                    width: 30,
                    items: [
                        {
                            tooltip: t('up'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/up.svg",
                            handler: function (grid, rowIndex) {
                                if (rowIndex > 0) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(--rowIndex, [rec]);
                                    selectionModel.select(rowIndex);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype: 'actioncolumn',
                    menuText: t('down'),
                    width: 30,
                    items: [
                        {
                            tooltip: t('down'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/down.svg",
                            handler: function (grid, rowIndex) {
                                if (rowIndex < (grid.getStore().getCount() - 1)) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(++rowIndex, [rec]);
                                    selectionModel.select(rowIndex);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype: 'actioncolumn',
                    menuText: t('remove'),
                    width: 30,
                    items: [
                        {
                            tooltip: t('remove'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }
                    ]
                }
            ],
            autoHeight: true
        });

        selectionModel = valueGrid.getSelectionModel();

        var specificItems = [
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("height"),
                name: "height",
                value: datax.height
            },
            {
                xtype: "numberfield",
                fieldLabel: t("maximum_items"),
                name: "maxItems",
                value: datax.maxItems,
                minValue: 0
            },
            {
                xtype: "combo",
                fieldLabel: t("multiselect_render_type"),
                name: "renderType",
                itemId: "renderType",
                mode: 'local',
                store: [
                    ['list', 'List'],
                    ['tags', 'Tags']
                ],
                value: datax["renderType"] ? datax["renderType"] : 'list',
                triggerAction: "all",
                editable: false,
                forceSelection: true
            },
            {
                xtype: "textfield",
                fieldLabel: t("options_provider_class"),
                width: 600,
                name: "optionsProviderClass",
                value: datax.optionsProviderClass
            },
            {
                xtype: "textfield",
                fieldLabel: t("options_provider_data"),
                width: 600,
                value: datax.optionsProviderData,
                name: "optionsProviderData"
            },
            valueGrid
        ];

        return specificItems;
    },

    applyData: function ($super) {

        $super();

        var options = [];

        var valueEditor = this.specificPanel.getComponent("valueeditor");
        if (valueEditor) {
            var valueStore = valueEditor.getStore();
            valueStore.commitChanges();
            valueStore.each(function (rec) {
                options.push({
                    key: rec.get("key"),
                    value: rec.get("value")
                });
            });
        }

        this.datax.options = options;
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax,
                {
                    options: source.datax.options,
                    width: source.datax.width,
                    height: source.datax.height,
                    maxItems: source.datax.maxItems,
                    renderType: source.datax.renderType,
                    optionsProviderClass: source.datax.optionsProviderClass,
                    optionsProviderData: source.datax.optionsProviderData
                });
        }
    },

    showoptioneditor: function (valueStore) {
        var editor = new pimcore.object.helpers.optionEditor(valueStore);
        editor.edit();
    }
});
