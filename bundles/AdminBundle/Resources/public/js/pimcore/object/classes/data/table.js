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

pimcore.registerNS("pimcore.object.classes.data.table");
pimcore.object.classes.data.table = Class.create(pimcore.object.classes.data.data, {

    type: "table",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : true,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "table";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
                                        "visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
            return "structured";
    },

    getTypeName: function () {
        return t("table");
    },

    getIconClass: function () {
        return "pimcore_icon_table";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    buildHeaderGrid: function(datax) {
        if (typeof datax.columnConfig != "object") {
            datax.columnConfig = [];
        } else if(datax.columnConfig.length === 0) {
            //init col definitions with standard values
            for(var i = 0; i < datax.cols; i++) {
                datax.columnConfig.push({
                    key: i,
                    label: i
                });
            }
        }

        this.columnConfigStore = new Ext.data.Store({
            fields: [
                "key",
                {name: "label", allowBlank: false}
            ],
            proxy: {
                type: 'memory'
            },
            data: datax.columnConfig
        });

        var valueGrid;

        valueGrid = Ext.create('Ext.grid.Panel', {
            region: 'center',
            store: this.columnConfigStore,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            clicksToEdit: 1,
            columnLines: true,
            columns: [
                {
                    text: t("key"),
                    sortable: false,
                    dataIndex: 'key',
                    editor: { xtype : 'textfield', allowBlank : false },
                    width: 150
                },
                {
                    text: t("label"),
                    sortable: false,
                    dataIndex: 'label',
                    editor: { xtype : 'textfield', allowBlank : false },
                    width: 150
                }
            ],
            autoHeight: true,
            plugins: [
                Ext.create('Ext.grid.plugin.CellEditing', {
                    clicksToEdit: 1,
                    listeners: {
                        edit: function(editor, e) {
                            if(!e.record.get('label')) {
                                e.record.set('label', e.record.get('key'));
                            }
                        }
                    }
                })]
        });

        return valueGrid;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {

        var headerGrid = this.buildHeaderGrid(datax);
        var headerGridContainer = Ext.create('Ext.Panel', {
            layout: "border",
            border: false,
            hidden: !datax.columnConfigActivated,
            height: 150,
            style: "margin-bottom: 20px",
            items: [
                {
                    xtype: "label",
                    style: "width: 145px",
                    html: t("table_column_configuration"),
                    region: 'west',
                    width: 145
                },
                headerGrid,
            ]
        });
        var activateColumnConfigCheckbox = Ext.create('Ext.form.field.Checkbox', {
            fieldLabel: t("activate_column_configuration"),
            name: "columnConfigActivated",
            checked: datax.columnConfigActivated,
            hidden: !datax.colsFixed,
            disabled: this.isInCustomLayoutEditor(),
            listeners: {
                change: function(headerGridContainer, checkbox, newValue, oldValue) {
                    if(newValue) {
                        headerGridContainer.show();
                    } else {
                        headerGridContainer.hide();
                    }
                }. bind(this, headerGridContainer)
            },
        });

        return [
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
                fieldLabel: t("rows"),
                name: "rows",
                value: datax.rows,
                minValue: 0,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "checkbox",
                fieldLabel: t("rows_fixed"),
                name: "rowsFixed",
                checked: datax.rowsFixed,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "numberfield",
                fieldLabel: t("cols"),
                name: "cols",
                value: datax.cols,
                minValue: 0,
                listeners: {
                    blur: function(headerGrid, field) {
                        var store = headerGrid.getStore();
                        var countDiff = field.getValue() - store.getCount();

                        if(countDiff > 0) {
                            for(var i = 0; i < countDiff; i++) {
                                store.add({
                                    key: store.getCount(),
                                    label: store.getCount()
                                });
                            }
                        } else if(countDiff < 0) {
                            store.removeAt((field.getValue()), (countDiff * -1));
                        }

                    }. bind(this, headerGrid)
                },
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "checkbox",
                fieldLabel: t("cols_fixed"),
                name: "colsFixed",
                checked: datax.colsFixed,
                listeners: {
                    change: function(activateColumnConfigCheckbox, headerGridContainer, checkbox, newValue, oldValue) {
                        if(newValue) {
                            activateColumnConfigCheckbox.show();
                        } else {
                            activateColumnConfigCheckbox.hide();
                            headerGridContainer.hide();
                        }
                    }. bind(this, activateColumnConfigCheckbox, headerGridContainer)
                },
                disabled: this.isInCustomLayoutEditor()
            },
            activateColumnConfigCheckbox,
            headerGridContainer,
            {
                xtype: "textarea",
                fieldLabel: t("data"),
                name: "data",
                width: 500,
                height: 300,
                value: datax.data,
                disabled: this.isInCustomLayoutEditor()
            }
        ];
    },

    applyData: function ($super) {

        $super();

        var options = [];

        if(this.datax.colsFixed && this.columnConfigStore) {
            var valueStore = this.columnConfigStore;
            valueStore.commitChanges();
            valueStore.each(function (rec) {
                options.push({
                    key: rec.get("key"),
                    label: rec.get("label")
                });
            });

        }

        this.datax.columnConfig = options;
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
                    cols: source.datax.cols,
                    colsFixed: source.datax.colsFixed,
                    rows: source.datax.rows,
                    rowsFixed: source.datax.rowsFixed,
                    data: source.datax.data,
                    columnConfig: source.datax.columnConfig,
                    columnConfigActivated: source.datax.columnConfigActivated,
                });
        }
    }

});
