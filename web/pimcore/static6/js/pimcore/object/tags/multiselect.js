/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        var store = Ext.create('Ext.data.JsonStore', {
            fields: ['key',"value"],
            data: field.layout.options
        });

        return {
            type: 'list',
            dataIndex: field.key,
            labelField: "key",
            idField: "value",
            options: store
        };
    },

    getLayoutEdit: function () {

        // generate store
        var storeData = [];
        var validValues = [];

        var restrictTo = null;

        if (this.fieldConfig.restrictTo) {
            restrictTo = this.fieldConfig.restrictTo.split(",");
        }

        if (this.fieldConfig.options) {
            for (var i = 0; i < this.fieldConfig.options.length; i++) {
                var value = this.fieldConfig.options[i].value;
                if (restrictTo) {
                    if (!in_array(value, restrictTo)) {
                        continue;
                    }
                }

                storeData.push([value, ts(this.fieldConfig.options[i].key)]);
                validValues.push(value);
            }
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
            labelWidth: this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100,
            listeners: {
                change : function  ( multiselect , newValue , oldValue , eOpts ) {
                    if (this.fieldConfig.maxItems && multiselect.getValue().length > this.fieldConfig.maxItems) {
                        // we need to set a timeout so setValue is applied when change event is totally finished
                        // without this, multiselect wont be updated visually with oldValue (but internal value will be oldValue)
                        setTimeout(function(multiselect, oldValue){
                            multiselect.setValue(oldValue);
                        }, 100, multiselect, oldValue);

                        Ext.Msg.alert(t("error"),t("limit_reached"));
                    }
                    return true;
                }.bind(this)
            }
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

        this.component = Ext.create('Ext.ux.form.MultiSelect', options);

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
