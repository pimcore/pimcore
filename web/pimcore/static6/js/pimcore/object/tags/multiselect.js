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

pimcore.registerNS("pimcore.object.tags.multiselect");
pimcore.object.tags.multiselect = Class.create(pimcore.object.tags.abstract, {

    type: "multiselect",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {

        var displayValues = {};
        for (var i = 0; i < field.layout.options.length; i++) {
            displayValues[field.layout.options[i].value] = ts(field.layout.options[i].key);
        }

        return {text: ts(field.label), width: 150, sortable: false, dataIndex: field.key,
            getEditor:this.getWindowCellEditor.bind(this, field),
            renderer: function (key, displayValues, value, metaData, record) {
                try {
                    if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                        metaData.tdCls += " grid_value_inherited";
                    }
                } catch (e) {
                    console.log(e);
                }

                if (value) {

                    var singleValues = [];
                    if(typeof value === 'string') {
                        singleValues = value.split(',');
                    } else {
                        singleValues = value;
                    }

                    var singleDisplayValues = [];
                    for(var i = 0; i < singleValues.length; i++) {
                        if(displayValues[singleValues[i]]) {
                            singleDisplayValues.push(displayValues[singleValues[i]]);
                        } else {
                            singleDisplayValues.push(singleValues[i]);
                        }
                    }

                    return singleDisplayValues.join(", ");
                } else {
                    return "";
                }
            }.bind(this, field.key, displayValues)};
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
        var hasHTMLContent = false;

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

                storeData.push({id: value, text: ts(this.fieldConfig.options[i].key)});

                if(ts(this.fieldConfig.options[i].key).indexOf('<') >= 0) {
                    hasHTMLContent = true;
                }

                validValues.push(value);
            }
        }

        var store = Ext.create('Ext.data.Store', {
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
        } else if (this.fieldConfig.renderType != "tags") {
            options.height = 100;
        }

        if (typeof this.data == "string" || typeof this.data == "number") {
            options.value = this.data;
        }

        if (this.fieldConfig.renderType == "tags") {
            options.queryMode = 'local';
            options.editable = true;
            if(hasHTMLContent) {
                options.labelTpl = '{[Ext.util.Format.stripTags(values.text)]}';
            }
            this.component = Ext.create('Ext.form.field.Tag', options);
        } else {
            this.component = Ext.create('Ext.ux.form.MultiSelect', options);
        }

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
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});
