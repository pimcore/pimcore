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
                            metaData.css += " grid_value_inherited";
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
                    metaData.css = 'x-grid3-hd-row';
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
                editor = new Ext.form.Checkbox();
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    metaData.css += ' x-grid3-check-col-td';
                    return String.format('<div class="x-grid3-check-col{0}" style="background-position:10px center;">&#160;</div>',
                                                                                    value ? '-on' : '');
                };
                listeners = {
                    "mousedown": function (col, grid, rowIndex, event) {
                        var store = grid.getStore();
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

        this.component = new Ext.grid.EditorGridPanel({
            store: this.store,
            enableColumnMove: false,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: columns
            }),
            cls: 'object_field',
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
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
            bodyCssClass: "pimcore_object_tag_objects"
        });

        this.component.on("afteredit", function() {
            this.dataChanged = true;
        }.bind(this));

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
                                metaData.css = 'x-grid3-hd-row';
                                return ts(value);
                           }
            }
        ];

        for(var i = 0; i < this.fieldConfig.cols.length; i++) {
            columns.push({header: ts(this.fieldConfig.cols[i].label), width: 120, sortable: false,
                                                dataIndex: this.fieldConfig.cols[i].key, editor: null});
        }

        this.component = new Ext.grid.EditorGridPanel({
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: columns
            }),
            cls: cls,
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
            bodyCssClass: "pimcore_object_tag_objects"
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