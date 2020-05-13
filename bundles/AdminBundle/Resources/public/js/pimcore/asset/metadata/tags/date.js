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

pimcore.registerNS("pimcore.asset.metadata.tags.date");
pimcore.asset.metadata.tags.date = Class.create(pimcore.asset.metadata.tags.abstract, {

    type:"date",

    initialize:function (data, fieldConfig) {

        this.data = null;

        if (typeof data !== "undefined" && data !== null) {
            this.data = data;
        } else if (fieldConfig.useCurrentDate) {
            this.data = (new Date().getTime()) / 1000;
        }

        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig:function (field) {
        return {
            text: field.label,
            width: this.getColumnWidth(field, 120),
            sortable:false,
            dataIndex:field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            filter: this.getGridColumnFilter(field),
            renderer:function (key, value, metaData, record) {
                if (value) {
                    var timestamp = intval(value) * 1000;
                    var date = new Date(timestamp);

                    return Ext.Date.format(date, "Y-m-d");
                }
                return "";
            }.bind(this, field.key)
        };
    },

    getGridColumnFilter:function (field) {
        return {type:'date', dataIndex:field.key, dateFormat: 'm/d/Y'};
    },

    getLayoutEdit:function () {

        var date = {
            fieldLabel:this.fieldConfig.title,
            name:this.fieldConfig.name,
            componentCls:"object_field",
            width:130,
            format: "Y-m-d"
        };

        if (this.fieldConfig.labelWidth) {
            date.labelWidth = this.fieldConfig.labelWidth;
        }
        date.width += date.labelWidth;

        if (this.data) {
            var tmpDate = new Date(intval(this.data) * 1000);
            date.value = tmpDate;
        }

        this.component = new Ext.form.DateField(date);
        return this.component;
    },

    getValue:function () {
        if (this.component.getValue()) {
            return this.component.getValue().getTime() / 1000;
        }
        return false;
    },

    getCellEditValue: function () {
        return this.getValue();
    },

    getName:function () {
        return this.fieldConfig.name;
    },

    getGridCellEditor: function (gridtype, record) {
        return Ext.create('Ext.form.field.Date', {
            format: "Y-m-d"
        });
    },

    convertPredefinedGridData: function(v, r) {
        if (v && !(v instanceof Date)) {
            var d = new Date(intval(v) * 1000);
            return d;
        }
        return v;
    },

    getGridCellRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        if (value) {
            if(!(value instanceof Date)) {
                value = new Date(value * 1000);
            }
            return Ext.Date.format(value, "Y-m-d");
        }
        return value;
    },

    marshal: function(value) {
        // value used for submission
        if (value) {
            value = value.valueOf() / 1000;
        }

        return value;
    },

    unmarshal: function(value) {
        // process received and transform it to grid value
        if (value) {
            value = new Date(intval(value) * 1000);
        }

        return value;
    },
});
