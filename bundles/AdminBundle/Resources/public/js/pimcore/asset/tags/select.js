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

pimcore.registerNS("pimcore.asset.tags.select");
pimcore.asset.tags.select = Class.create(pimcore.asset.tags.abstract, {

    type: "select",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfigDynamic: function(field) {
        var renderer = function (key, data, metaData, record) {
            var value = data;
            var options = record.data[key + "%options"];

            if (options) {
                for (var i = 0; i < options.length; i++) {
                    if (options[i]["value"] == value) {
                        return replace_html_event_attributes(strip_tags(options[i]["key"], 'div,span,b,strong,em,i,small,sup,sub'));
                    }
                }
            }

            return replace_html_event_attributes(strip_tags(value, 'div,span,b,strong,em,i,small,sup,sub'));
        }.bind(this, field.key);

        return {
            text:ts(field.label),
            sortable:true,
            dataIndex:field.key,
            renderer: renderer,
            getEditor:this.getCellEditor.bind(this, field)
        };
    },

    getGridColumnConfigStatic: function(field) {
        var renderer = function (key, value, metaData, record) {
            return replace_html_event_attributes(strip_tags(value, 'div,span,b,strong,em,i,small,sup,sub'));
        }.bind(this, field.key);

        return {
            text: ts(field.label),
            sortable: true,
            dataIndex: field.key,
            renderer: renderer,
            editor: this.getGridColumnEditor(field)
        };
    },

    getGridColumnConfig:function (field) {
        if (field.layout.optionsProviderClass) {
            return this.getGridColumnConfigDynamic(field);
        } else {
            return this.getGridColumnConfigStatic(field);
        }
    },

    getCellEditor: function (field, record) {
        var key = field.key;
        if(field.layout.noteditable) {
            return null;
        }

        var value = record.data[key];
        var options = record.data[key +  "%options"];
        options = this.prepareStoreDataAndFilterLabels(options);

        var store = new Ext.data.Store({
            autoDestroy: true,
            fields: ['key', 'value'],
            data: options
        });

        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        editorConfig = Object.assign(editorConfig, {
            store: store,
            triggerAction: "all",
            editable: false,
            mode: "local",
            valueField: 'value',
            displayField: 'key',
            value: value,
            displayTpl: Ext.create('Ext.XTemplate',
                '<tpl for=".">',
                '{[Ext.util.Format.stripTags(values.key)]}',
                '</tpl>'
            )
        });

        var combo = new Ext.form.ComboBox(editorConfig);
        var currentValue = combo.getValue();
        return combo;
    },

    getGridColumnEditor: function(field) {
        if(field.layout.noteditable) {
            return null;
        }

        var storeData = this.prepareStoreDataAndFilterLabels(field.layout.options);
        var store = new Ext.data.Store({
            autoDestroy: true,
            fields: ['key', 'value'],
            data: storeData
        });

        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        editorConfig = Object.assign(editorConfig, {
            store: store,
            triggerAction: "all",
            editable: false,
            mode: "local",
            valueField: 'value',
            displayField: 'key',
            displayTpl: Ext.create('Ext.XTemplate',
                '<tpl for=".">',
                '{[Ext.util.Format.stripTags(values.key)]}',
                '</tpl>'
            )
        });

        return new Ext.form.ComboBox(editorConfig);
    },

    prepareStoreDataAndFilterLabels: function(options) {
        var filteredStoreData = [];
        if (options) {
            for (var i = 0; i < options.length; i++) {

                var label = ts(options[i].key);
                if(label.indexOf('<') >= 0) {
                    label = replace_html_event_attributes(strip_tags(label, "div,span,b,strong,em,i,small,sup,sub2"));
                }

                filteredStoreData.push({'value': options[i].value, 'key': label});
            }
        }

        return filteredStoreData;
    },

    getGridColumnFilter: function(field) {
        if (field.layout.dynamicOptions) {
            return {type: 'string', dataIndex: field.key};
        } else {
            var store = Ext.create('Ext.data.JsonStore', {
                fields: ['key', "value"],
                data: this.prepareStoreDataAndFilterLabels(field.layout.options)
            });

            return {
                type: 'list',
                dataIndex: field.key,
                labelField: "key",
                idField: "value",
                options: store
            };
        }
    },

    getLayoutEdit: function () {
        var storeData = [];

        if(this.fieldConfig.config) {
            storeData = this.fieldConfig.config.split(",");
        }

        var options = {
            name: this.fieldConfig.name,
            triggerAction: "all",
            editable: true,
            queryMode: 'local',
            autoComplete: false,
            forceSelection: true,
            selectOnFocus: true,
            fieldLabel: this.fieldConfig.title,
            store: storeData,
            componentCls: "object_field",
            width: 250,
            displayField: 'key',
            valueField: 'value',
            labelWidth: 100
        };

        this.component = new Ext.form.ComboBox(options);

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.setReadOnly(true);

        return this.component;
    },

    getValue:function () {
        if (this.isRendered()) {
            return this.component.getValue();
        }
        return this.data;
    },


    getName: function () {
        return this.fieldConfig.name;
    },

});
