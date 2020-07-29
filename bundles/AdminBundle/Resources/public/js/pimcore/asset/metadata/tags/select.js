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

pimcore.registerNS("pimcore.asset.metadata.tags.select");
pimcore.asset.metadata.tags.select = Class.create(pimcore.asset.metadata.tags.abstract, {

    type: "select",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig:function (field) {
        return {
            text: field.label,
            editable: false,
            width: this.getColumnWidth(field, 80),
            sortable: false,
            dataIndex: field.key,
            filter: this.getGridColumnFilter(field),
            getEditor: this.getGridColumnEditor.bind(this, field),
            renderer: this.getRenderer(field)
        };
    },

    addGridOptionsFromColumnConfig: function (key, v, rec) {
        if (v && typeof v.options !== "undefined") {
            // split it up and store the options in a separate field
            rec.set(key + "%options", v.options, {convert: false, dirty: false});
            return v.value;
        }
        return v;
    },

    getCellEditor: function (field, record) {
        var key = field.key;

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

        return new Ext.form.ComboBox(editorConfig);
    },

    getGridColumnEditor: function(field) {

        var storeData = this.prepareStoreDataAndFilterLabels(field.layout.config);
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
            options = options.split(',');
            for (var i = 0; i < options.length; i++) {

                var key = t(options[i]);
                if(key.indexOf('<') >= 0) {
                    key = replace_html_event_attributes(strip_tags(key, "div,span,b,strong,em,i,small,sup,sub"));
                }

                filteredStoreData.push({'value': key, 'key': key});
            }
        }

        return filteredStoreData;
    },

    getGridColumnFilter: function(field) {
        var store = Ext.create('Ext.data.JsonStore', {
            fields: ['key', "value"],
            data: this.prepareStoreDataAndFilterLabels(field.layout.config)
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

    getGridCellEditor: function (gridtype, record) {
        // there is no value cell editor for predefined grid
        if (gridtype == "custom") {
            let data  = record.data;
            var config = data.config;
            return Ext.create('Ext.form.ComboBox', {
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }
        return null;
    }

});
