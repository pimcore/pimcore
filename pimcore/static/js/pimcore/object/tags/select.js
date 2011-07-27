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

pimcore.registerNS("pimcore.object.tags.select");
pimcore.object.tags.select = Class.create(pimcore.object.tags.abstract, {

    type: "select",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnEditor: function(field) {
        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'options',
            fields: ['key',"value"],
            data: field.layout
        });

        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        editorConfig = Object.extend(editorConfig, {
            store: store,
            triggerAction: "all",
            editable: false,
            mode: "local",
            valueField: 'value',
            displayField: 'key'
        });

        return new Ext.form.ComboBox(editorConfig);
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

        return {
            type: 'list',
            dataIndex: field.key,
            options: selectFilterFields
        };
    },    

    getLayoutEdit: function () {

        // generate store
        var store = [];
        var validValues = [];

        if(!this.fieldConfig.mandatory) {
            store.push(["","(" + t("empty") + ")"]);
        }

        for (var i = 0; i < this.fieldConfig.options.length; i++) {
            store.push([this.fieldConfig.options[i].value, ts(this.fieldConfig.options[i].key)]);
            validValues.push(this.fieldConfig.options[i].value);
        }

        var options = {
            name: this.fieldConfig.name,
            triggerAction: "all",
            editable: false,
            fieldLabel: this.fieldConfig.title,
            store: store,
            itemCls: "object_field",
            width: 300
        };

        if (this.fieldConfig.width) {
            options.width = this.fieldConfig.width;
        }

        if (typeof this.data == "string" || typeof this.data == "number") {
            if (in_array(this.data, validValues)) {
                options.value = this.data;
            } else {
                options.value = "";
            }
        } else {
            options.value = "";
        }

        this.component = new Ext.form.ComboBox(options);

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});