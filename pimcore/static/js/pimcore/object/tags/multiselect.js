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

pimcore.registerNS("pimcore.object.tags.multiselect");
pimcore.object.tags.multiselect = Class.create(pimcore.object.tags.abstract, {

    type: "multiselect",

    initialize: function (data, layoutConf) {
        this.data = data;
        this.layoutConf = layoutConf;

    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
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
        var store = [];
        var validValues = [];
        for (var i = 0; i < this.layoutConf.options.length; i++) {
            store.push([this.layoutConf.options[i].value, this.layoutConf.options[i].key]);
            validValues.push(this.layoutConf.options[i].value);
        }

        var options = {
            name: this.layoutConf.name,
            triggerAction: "all",
            editable: false,
            fieldLabel: this.layoutConf.title,
            store: store,
            itemCls: "object_field"
        };

        if (this.layoutConf.width) {
            options.width = this.layoutConf.width;
        }
        if (this.layoutConf.height) {
            options.height = this.layoutConf.height;
        }

        if (typeof this.data == "string" || typeof this.data == "number") {
            options.value = this.data;
        }

        this.layout = new Ext.ux.form.MultiSelect(options);

        return this.layout;
    },


    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        if(this.layout.rendered) {
            return this.layout.getValue();
        }
    },

    getName: function () {
        return this.layoutConf.name;
    }
});