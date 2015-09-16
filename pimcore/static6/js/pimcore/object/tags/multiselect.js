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

pimcore.registerNS("pimcore.object.tags.multiselect");
pimcore.object.tags.multiselect = Class.create(pimcore.object.tags.abstract, {

    type: "multiselect",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                try {
                    if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                        metaData.tdCls += " grid_value_inherited";
                    }
                } catch (e) {
                    console.log(e);
                }

                if (value && value.length > 0) {
                    return value.join(",");
                }
            }.bind(this, field.key)};
    },

    getGridColumnFilter: function(field) {
        var selectFilterFields = [];

        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'options',
            fields: ['key',"value"],
            data: field.layout
        });

        store.each(function (rec) {
            selectFilterFields.push(rec.data.value);
        });

        return {type: 'list', dataIndex: field.key, options: selectFilterFields};
    },

    getLayoutEdit: function () {

        // generate store
        var storeData = [];
        var validValues = [];

        var restrictTo = null;

        if (this.fieldConfig.restrictTo) {
            restrictTo = this.fieldConfig.restrictTo.split(",");
        }

        for (var i = 0; i < this.fieldConfig.options.length; i++) {
            var value = this.fieldConfig.options[i].value;
            if (restrictTo) {
                if (!in_array(value, restrictTo)) {
                    continue;
                }
            }

            storeData.push([value, this.fieldConfig.options[i].key]);
            validValues.push(value);
        }

        var store = Ext.create('Ext.data.ArrayStore', {
            fields: ['id', 'text'],
            data: storeData
        });


        var options = {
            name: this.fieldConfig.name,
            triggerAction: "all",
            editable: false,
            fieldLabel: this.fieldConfig.title,
            store: store,
            componentCls: "object_field",
            height: 100,
            valueField: 'id',
            labelWidth: this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100
        };

        if (this.fieldConfig.width) {
            options.width = this.fieldConfig.width;
        } else {
            options.width = 300;
        }

        options.width += options.labelWidth;

        if (this.fieldConfig.height) {
            options.height = this.fieldConfig.height;
        }


        if (typeof this.data == "string" || typeof this.data == "number") {
            options.value = this.data;
        }

        this.component = new Ext.ux.form.MultiSelect(options);

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();

        this.component.on("afterrender", function () {
            this.component.disable();
        }.bind(this));


        return this.component;
    },

    getValue: function () {
        if(this.isRendered()) {
            return this.component.getValue();
        }
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});