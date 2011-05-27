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

pimcore.registerNS("pimcore.object.tags.structuredTable");
pimcore.object.tags.structuredTable = Class.create(pimcore.object.tags.abstract, {

    type: "structuredTable",
    dataChanged:false,

    initialize: function (data, layoutConf) {
        this.data = [];
        this.layoutConf = layoutConf;

        if (data) {
            if(data.length == 0) {

                for(var i = 0; i < layoutConf.rows.length; i++) {
                    var dataRow = {};
                    dataRow.__row_identifyer = layoutConf.rows[i].key;
                    dataRow.__row_label = layoutConf.rows[i].label;

                    for(var j = 0; j < layoutConf.cols.length; j++) {
                        dataRow[layoutConf.cols[i].key] = null;
                    }
                    data.push(dataRow);
                }
            }
            this.data = data;
        }

        var fields = [
            "__row_identifyer",
            "__row_label"
        ];

        for(var i = 0; i < layoutConf.cols.length; i++) {
            fields.push(layoutConf.cols[i].key);
        }

        this.store = new Ext.data.JsonStore({
            fields: fields
        });

        this.store.loadData(this.data);
    },

    getLayoutEdit: function () {

        var autoHeight = false;
        if (intval(this.layoutConf.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';

        var columns = [
            {header: "", width: 80, sortable: false, dataIndex: '__row_label', editor: null, renderer: function(value, metaData) {
                    metaData.css = 'x-grid3-hd-row';
                    return ts(value);
               }
            }
        ];

        for(var i = 0; i < this.layoutConf.cols.length; i++) {
            columns.push({header: ts(this.layoutConf.cols[i].label), width: 120, sortable: false, dataIndex: this.layoutConf.cols[i].key, editor: new Ext.form.NumberField({})});
        }

        this.grid = new Ext.grid.EditorGridPanel({
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: columns
            }),
            cls: cls,
            width: this.layoutConf.width,
            height: this.layoutConf.height,
            tbar: [
                {
                    xtype: "tbspacer",
                    width: 20,
                    height: 16
                },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.layoutConf.title + "</b>"
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

        this.grid.on("afteredit", function() {
            this.dataChanged = true;
        }.bind(this));

        this.grid.reference = this;

        return this.grid;
    }
    ,


    getLayoutShow: function () {

        var autoHeight = false;
        if (intval(this.layoutConf.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';

        var columns = [
            {header: "", width: 80, sortable: false, dataIndex: '__row_label', editor: null, renderer: function(value, metaData) {
                    metaData.css = 'x-grid3-hd-row';
                    return ts(value);
               }
            }
        ];

        for(var i = 0; i < this.layoutConf.cols.length; i++) {
            columns.push({header: ts(this.layoutConf.cols[i].label), width: 120, sortable: false, dataIndex: this.layoutConf.cols[i].key, editor: null});
        }

        this.grid = new Ext.grid.EditorGridPanel({
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: columns
            }),
            cls: cls,
            width: this.layoutConf.width,
            height: this.layoutConf.height,
            tbar: [
                {
                    xtype: "tbspacer",
                    width: 20,
                    height: 16
                },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.layoutConf.title + "</b>"
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

        return this.grid;
    },

    empty: function () {
        for(var i = 0; i < this.data.length; i++) {
            for(var j = 0; j < this.layoutConf.cols.length; j++) {
                this.data[i][this.layoutConf.cols[j].key] = null;
            }
        }
        this.store.loadData(this.data);
        this.dataChanged = true;
    },

    isInvalidMandatory: function () {
        var empty = true;

        this.store.each(function(record) {
            for(var j = 0; j < this.layoutConf.cols.length; j++) {
                if(record.data[this.layoutConf.cols[j].key] != null && record.data[this.layoutConf.cols[j].key] != "") {
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
        return this.layoutConf.name;
    },


    isDirty: function() {
        return this.dataChanged;
    }
});