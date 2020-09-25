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

pimcore.registerNS("pimcore.document.editables.table");
pimcore.document.editables.table = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {

        this.id = id;
        this.name = name;
        config = this.parseConfig(config);

        if (!data) {
            data = [
                [" "]
            ];
            if (config.defaults) {
                if (config.defaults.cols) {
                    for (let i = 0; i < (config.defaults.cols - 1); i++) {
                        data[0].push(" ");
                    }
                }
                if (config.defaults.rows) {
                    for (let i = 0; i < (config.defaults.rows - 1); i++) {
                        data.push(data[0]);
                    }
                }
                if (config.defaults.data) {
                    data = config.defaults.data;
                }
            }
        }

        delete config["height"];

        this.config = config;

        this.initStore(data);
    },

    refreshStoreGrid: function (data) {
        this.initStore(data);
        this.render();
    },

    render: function() {
        if (this.grid) {
            this.grid.destroy();
        }
        this.setupWrapper();

        var data = this.store.queryBy(function(record, id) {
            return true;
        });
        var columns = [];

        var fields = this.store.getInitialConfig().fields;

        if (data.items[0]) {
            for (var i = 0; i < fields.length; i++) {
                columns.push({
                    dataIndex: fields[i].name,
                    editor: new Ext.form.TextField({
                        allowBlank: true
                    }),
                    hideable: false,
                    sortable: false
                });
            }
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        let gridConfig = array_merge(this.config, {
            name: this.id + "_editable",
            store: this.store,
            border: true,
            columns:columns,
            stripeRows: true,
            columnLines: true,
            selModel: Ext.create('Ext.selection.CellModel'),
            manageHeight: false,
            plugins: [
                this.cellEditing
            ],
            tbar: [
                {
                    iconCls: "pimcore_icon_table_col pimcore_icon_overlay_add",
                    handler: this.addColumn.bind(this)
                },
                {
                    iconCls: "pimcore_icon_table_col pimcore_icon_overlay_delete",
                    handler: this.deleteColumn.bind(this)
                },
                {
                    iconCls: "pimcore_icon_table_row pimcore_icon_overlay_add",
                    handler: this.addRow.bind(this)
                },
                {
                    iconCls: "pimcore_icon_table_row pimcore_icon_overlay_delete",
                    handler: this.deleteRow.bind(this)
                },
                {
                    iconCls: "pimcore_icon_empty",
                    handler: this.refreshStoreGrid.bind(this, [
                        [" "]
                    ])
                }
            ]
        });

        this.grid = Ext.create('Ext.grid.Panel', gridConfig);
        this.grid.render(this.id);
    },

    initStore: function (data) {
        var storeFields = [];
        if (data[0]) {
            for (var i = 0; i < data[0].length; i++) {
                storeFields.push({
                    name: "col_" + i
                });
            }
        }

        this.store = new Ext.data.ArrayStore({
            fields: storeFields
        });

        this.store.loadData(data);
    },

    addColumn : function  () {

        var currentData = this.getValue();

        for (var i = 0; i < currentData.length; i++) {
            currentData[i].push(" ");
        }

        this.refreshStoreGrid(currentData);
    },

    addRow: function  () {
        var initData = {};

        var columnnManager = this.grid.getColumnManager();
        var columns = columnnManager.getColumns();

        for (var o = 0; o < columns.length; o++) {
            initData["col_" + o] = " ";
        }

        this.store.add(initData);
    },

    deleteRow : function  () {
        var selected = this.grid.getSelectionModel();
        if (selected.selection) {
            this.store.remove(selected.selection.record);
            this.grid.editingPlugin.view.refresh();  // Prevents the editor from being garbage collected
        }
    },

    deleteColumn: function () {
        var selected = this.grid.getSelectionModel();

        if (selected.selection) {
            var column = selected.selection.colIdx;

            var currentData = this.getValue();

            for (var i = 0; i < currentData.length; i++) {
                currentData[i].splice(column, 1);
            }

            this.refreshStoreGrid(currentData);
        }
    },

    getValue: function () {
        var data = this.store.queryBy(function(record, id) {
            return true;
        });

        var fields = this.store.getInitialConfig().fields;

        var storedData = [];
        var tmData = [];
        for (var i = 0; i < data.items.length; i++) {
            tmData = [];

            for (var u = 0; u < fields.length; u++) {
                tmData.push(data.items[i].data[fields[u].name]);
            }
            storedData.push(tmData);
        }

        return storedData;
    },

    getType: function () {
        return "table";
    }
});