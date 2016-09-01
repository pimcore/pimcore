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

            store.push([value, this.fieldConfig.options[i].key]);
            validValues.push(value);
        }

        var options = {
            name: this.fieldConfig.name,
            triggerAction: "all",
            editable: false,
            fieldLabel: this.fieldConfig.title,
            store: store,
            itemCls: "object_field",
            listeners: {
                change : function  ( multiselect , newValue , oldValue , eOpts ) {
                    if (parseInt(this.maxSelections) > 0 && this.getValue().length > this.maxSelections) {
                        // we need to set a timeout so setValue is applied when change event is totally finished
                        // without this, multiselect wont be updated visually with oldValue (but internal value will be oldValue)
                        setTimeout(function(multiselect, oldValue){
                            multiselect.setValue(oldValue);
                        }, 100, multiselect, oldValue);
                        Ext.Msg.alert(t("error"),t("limit_reached"));
                    }
                    return true;
                }
            }
        };

        if (this.fieldConfig.width) {
            options.width = this.fieldConfig.width;
        }
        if (this.fieldConfig.height) {
            options.height = this.fieldConfig.height;
        }
        if (parseInt(this.fieldConfig.maxItems) > 0) {
            options.maxSelections = parseInt(this.fieldConfig.maxItems);
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