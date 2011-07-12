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

pimcore.registerNS("pimcore.object.tags.datetime");
pimcore.object.tags.datetime = Class.create(pimcore.object.tags.abstract, {

    type: "datetime",

    initialize: function (data, layoutConf) {
        this.data = data;
        this.layoutConf = layoutConf;

    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }

            if (value) {
                var timestamp = intval(value) * 1000;
                var date = new Date(timestamp);

                return date.format("Y-m-d H:i");
            }
            return "";
        }.bind(this, field.key)};
    },

    getGridColumnFilter: function(field) {
        return {type: 'date', dataIndex: field.key};
    },        

    getLayoutEdit: function () {

        var date = {
            itemCls: "object_field",
            width: 100
        };

        var time = {
            format: "H:i",
            emptyText: "",
            width: 60
        };

        if (this.data) {
            var tmpDate = new Date(intval(this.data) * 1000);
            date.value = tmpDate;
            time.value = tmpDate.format("H:i");
        }

        this.datefield = new Ext.form.DateField(date);
        this.timefield = new Ext.form.TimeField(time);

        this.layout = new Ext.form.CompositeField({
            xtype: 'compositefield',
            fieldLabel: this.layoutConf.title,
            combineErrors: false,
            items: [this.datefield, this.timefield],
            itemCls: "object_field"
        });

        return this.layout;
    },

    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        if (this.datefield.getValue()) {
            var dateString = this.datefield.getValue().format("Y-m-d");

            if (this.timefield.getValue()) {
                dateString += " " + this.timefield.getValue();
            }
            else {
                dateString += " 00:00";
            }

            return Date.parseDate(dateString, "Y-m-d H:i").getTime();
        }
        return false;
    },

    getName: function () {
        return this.layoutConf.name;
    },

    isInvalidMandatory: function () {
        if (this.getValue() == false) {
            return true;
        }
        return false;
    }
});