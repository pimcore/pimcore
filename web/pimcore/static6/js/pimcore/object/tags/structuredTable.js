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

pimcore.registerNS("pimcore.object.tags.structuredTable");
pimcore.object.tags.structuredTable = Class.create(pimcore.object.tags.abstract, {

    type: "structuredTable",
    dataChanged:false,

    initialize: function (data, fieldConfig) {
        this.data = [];
        this.fieldConfig = fieldConfig;
        var i;

        if (!data) {
            data = [];
        }
        if(data.length == 0) {

            for(i = 0; i < fieldConfig.rows.length; i++) {
                var dataRow = {};
                dataRow.__row_identifyer = fieldConfig.rows[i].key;
                dataRow.__row_label = fieldConfig.rows[i].label;

                for(var j = 0; j < fieldConfig.cols.length; j++) {
                    dataRow[fieldConfig.cols[j].key] = null;
                }
                data.push(dataRow);
            }
        }
        this.data = data;

        var fields = [
            "__row_identifyer",
            "__row_label"
        ];

        for(i = 0; i < fieldConfig.cols.length; i++) {
            var field = {name:fieldConfig.cols[i].key};
            if(fieldConfig.cols[i].type == "number") {
                field.type = "float";
            }
            if(fieldConfig.cols[i].type == "bool") {
                field.type = "bool";
            }
            fields.push(field);
        }

        this.store = new Ext.data.JsonStore({
            fields: fields
        });

        this.store.loadData(this.data);
    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key,
            renderer: function (key, field, value, metaData, record) {
                        this.applyPermissionStyle(key, value, metaData, record);

                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.tdCls += " grid_value_inherited";
                        }
                        var rows = Object.keys(value);
                        if (rows && rows.length > 0) {
                            var table = '<table cellpadding="2" cellspacing="0" border="1">';

                            var row = value[rows[0]];
                            var cols = Object.keys(row);
                            //column headlines
                            table += '<tr>';
                            table += '<td></td>';
                            for (var c = 0; c < cols.length; c++) {
                                table += '<td>' + ts(field.layout.cols[c].label) + '</td>';
                            }
                            table += '</tr>';

                            //row values
                            for (var i = 0; i < rows.length; i++) {
                                row = value[rows[i]];
                                cols = Object.keys(row);

                                table += '<tr>';
                                table += '<td>' + ts(field.layout.rows[i].label) + '</td>';
                                for (var c = 0; c < cols.length; c++) {
                                    table += '<td>' + row[cols[c]] + '</td>';
                                }
                                table += '</tr>';
                            }
                            table += '</table>';
                            return table;
                        }
                        return "";
                    }.bind(this, field.key, field)};
    },

    getLayoutEdit: function () {

        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var columns = [
            {header: this.fieldConfig.labelFirstCell, width: this.fieldConfig.labelWidth, sortable: false,
                                dataIndex: '__row_label', editor: null, renderer: function(value, metaData) {
                    metaData.tdCls = 'x-grid-hd-row';
                    return ts(value);
               }
            }
        ];

        for(var i = 0; i < this.fieldConfig.cols.length; i++) {

            var editor = null;
            var renderer = null;
            var listeners = null;
            if(this.fieldConfig.cols[i].type == "number") {
                editor = new Ext.form.NumberField({});
            } else if(this.fieldConfig.cols[i].type == "text") {
                editor = new Ext.form.TextField({
                    maxLength: 255,
                    autoCreate: {tag: 'input', type: 'text', size: '20', maxlength: "255", autocomplete: 'off'}
                });
            } else if(this.fieldConfig.cols[i].type == "bool") {
                editor = new Ext.form.Checkbox({style: 'margin-top: 2px;'});
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    if (value) {
                        return '<div style="text-align: center"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
                    } else {
                        return '<div style="text-align: center"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
                    }
                };
                listeners = {
                    "mousedown": function (col, grid, rowIndex, event) {
                        var store = this.component.getStore();
                        var record = store.getAt(rowIndex);
                        record.set(col.dataIndex, !record.data[col.dataIndex]);
                        this.dataChanged = true;
                    }.bind(this)
                };
            }

            columns.push({
                header: ts(this.fieldConfig.cols[i].label),
                width: this.fieldConfig.cols[i].width,
                sortable: false,
                dataIndex: this.fieldConfig.cols[i].key,
                editor: editor,
                listeners: listeners,
                renderer: renderer
            });
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                "edit": function () {
                    this.dataChanged = true;
                }.bind(this)
            }
        });

        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            enableColumnMove: false,
            border: true,
            style: "margin-bottom: 10px",
            columns: columns,
            componentCls: 'object_field',
            bodyCls: "pimcore_editable_grid",
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            selModel: Ext.create('Ext.selection.CellModel'),
            tbar: [
                {
                    xtype: "tbtext",
                    text: "<b>" + this.fieldConfig.title + "</b>"
                },
                "->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: this.empty.bind(this)
                }
            ],
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_objects",
            plugins: [
                this.cellEditing
            ]
        });

        this.component.reference = this;

        return this.component;
    }
    ,


    getLayoutShow: function () {

        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';

        var columns = [
            {header: "", width: 80, sortable: false, dataIndex: '__row_label', editor: null,
                renderer: function(value, metaData) {
                                metaData.tdCls = 'x-grid3-hd-row';
                                return ts(value);
                           }
            }
        ];

        for(var i = 0; i < this.fieldConfig.cols.length; i++) {

            var columnConfig = {header: ts(this.fieldConfig.cols[i].label), width: 120, sortable: false,
                dataIndex: this.fieldConfig.cols[i].key, editor: null};
            if(this.fieldConfig.cols[i].type == "bool") {
                columnConfig.renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    if (value) {
                        return '<div style="text-align: center"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
                    } else {
                        return '<div style="text-align: center"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
                    }
                };
            }

            columns.push(columnConfig);
        }


        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            columns: columns,
            componentCls: cls,
            border: true,
            style: "margin-bottom: 10px",
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: [
                {
                    xtype: "tbspacer",
                    width: 20,
                    height: 16
                },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.fieldConfig.title + "</b>"
                },
                "->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: this.empty.bind(this)
                }
            ],
            autoHeight: autoHeight,
            bodyCls: "pimcore_object_tag_objects"
        });

        return this.component;
    },

    empty: function () {
        for(var i = 0; i < this.data.length; i++) {
            for(var j = 0; j < this.fieldConfig.cols.length; j++) {
                this.data[i][this.fieldConfig.cols[j].key] = null;
            }
        }
        this.store.loadData(this.data);
        this.dataChanged = true;
    },

    isInvalidMandatory: function () {
        var empty = true;

        this.store.each(function(record) {
            for(var j = 0; j < this.fieldConfig.cols.length; j++) {
                if(record.data[this.fieldConfig.cols[j].key] != null
                                                        && record.data[this.fieldConfig.cols[j].key] != "") {
                    empty = false;
                }
            }
        }.bind(this));

        return empty;
    },

    getValue: function () {
        var tmData = [];

        var data = this.store.queryBy(function(record, id) {
            record.commit();
            return true;
        });

        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }
        return tmData;
    },

    getName: function () {
        return this.fieldConfig.name;
    },


    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        return this.dataChanged;
    }
});