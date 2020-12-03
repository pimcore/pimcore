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
    allowBatchAppend: true,
    allowBatchRemove: true,

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {

        var displayValues = {};
        if (field.layout.options) {
            for (var i = 0; i < field.layout.options.length; i++) {
                displayValues[field.layout.options[i].value] = t(field.layout.options[i].key);
            }
        }

        return {text: t(field.label), width: 150, sortable: false, dataIndex: field.key,
            getEditor:this.getWindowCellEditor.bind(this, field),
            renderer: function (key, displayValues, value, metaData, record) {
                try {
                    if(record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
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

                    return replace_html_event_attributes(strip_tags(singleDisplayValues.join(", "), 'div,span,b,strong,em,i,small,sup,sub'));
                } else {
                    return "";
                }
            }.bind(this, field.key, displayValues)};
    },

    getGridColumnFilter: function(field) {

        var storeData = this.prepareStoreDataAndFilterLabels(field.layout);

        var store = Ext.create('Ext.data.JsonStore', {
            fields: ['id', 'text'],
            data: storeData
        });

        return {
            type: 'list',
            dataIndex: field.key,
            labelField: "text",
            idField: "id",
            options: store
        };
    },

    prepareStoreDataAndFilterLabels: function(fieldConfig) {

        var storeData = [];
        var restrictTo = null;

        if (fieldConfig.restrictTo) {
            restrictTo = fieldConfig.restrictTo.split(",");
        }

        if (fieldConfig.options) {
            for (var i = 0; i < fieldConfig.options.length; i++) {
                var value = fieldConfig.options[i].value;
                if (restrictTo) {
                    if (!in_array(value, restrictTo)) {
                        continue;
                    }
                }

                var label = t(fieldConfig.options[i].key);
                if(label.indexOf('<') >= 0) {
                    label = replace_html_event_attributes(strip_tags(label, "div,span,b,strong,em,i,small,sup,sub2"));
                }
                storeData.push({id: value, text: label});
            }
        }

        return storeData;

    },

    getLayoutEdit: function () {

        // generate store
        var validValues = [];
        var hasHTMLContent = false;
        var storeData = this.prepareStoreDataAndFilterLabels(this.fieldConfig);
        for (var i = 0; i < storeData.length; i++) {
            validValues.push(storeData[i].text);
            if(storeData[i].text.indexOf('<') >= 0) {
                hasHTMLContent = true;
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
            componentCls: "object_field object_field_type_" + this.type,
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

        let res = [];
        if (this.data) {
            res = [this.data];
        }

        return res;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});
