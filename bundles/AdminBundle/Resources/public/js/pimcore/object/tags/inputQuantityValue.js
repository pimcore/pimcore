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

pimcore.registerNS("pimcore.object.tags.inputQuantityValue");
pimcore.object.tags.inputQuantityValue = Class.create(pimcore.object.tags.abstract, {

    type: "inputQuantityValue",

    initialize: function (data, fieldConfig) {
        this.defaultValue = null;
        this.defaultUnit = null;
        if ((typeof data === "undefined" || data === null) && (fieldConfig.defaultValue || fieldConfig.defaultUnit)) {
            data = {
                value: fieldConfig.defaultValue,
                unit: fieldConfig.defaultUnit
            };
            this.defaultValue = data.value;
            this.defaultUnit = data.unit;
        }

        this.data = data;
        this.fieldConfig = fieldConfig;

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'data',
            fields: ['id', 'abbreviation']
        });

        pimcore.helpers.quantityValue.initUnitStore(this.setData.bind(this), fieldConfig.validUnits, this.data);
    },

    setData: function(data) {
        var storeData = data.data;
        storeData.unshift({'id': -1, 'abbreviation' : "(" + t("empty") + ")"});

        this.store.loadData(storeData);

        if (this.unitField) {
            this.unitField.reset();
        }
    },

    getLayoutEdit: function () {

        var input = {};

        if (this.data) {
            input.value = this.data.value;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        if (this.fieldConfig["decimalPrecision"] !== null) {
            input.decimalPrecision = this.fieldConfig["decimalPrecision"];
        }

        var labelWidth = 100;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }

        var options = {
            width: 100,
            triggerAction: "all",
            autoSelect: true,
            editable: true,
            selectOnFocus: true,
            forceSelection: true,
            store: this.store,
            valueField: 'id',
            displayField: 'abbreviation',
            queryMode: 'local'
        };

        if(this.data && this.data.unit != null) {
            options.value = this.data.unit;
        } else {
            options.value = -1;
        }

        this.unitField = new Ext.form.ComboBox(options);

        this.inputField = new Ext.form.TextField(input);

        this.component = new Ext.form.FieldContainer({
            layout: 'hbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            combineErrors: false,
            items: [this.inputField, this.unitField],
            componentCls: "object_field object_field_type_" + this.type,
            isDirty: function() {
                return this.inputField.isDirty() || this.unitField.isDirty()
            }.bind(this)
        });

        return this.component;
    },

    getGridColumnConfig:function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                try {
                    metaData.tdCls += " grid_value_inherited";
                } catch (e) {
                    console.log(e);
                }
            }

            if (value) {
                return (value.value ? value.value : "") + " " + value.unitAbbr;
            } else {
                return "";
            }

        }.bind(this, field.key);

        return {
            text: t(field.label),
            sortable:true,
            dataIndex:field.key,
            renderer:renderer
        };
    },


    getLayoutShow: function () {

        this.getLayoutEdit();
        this.unitField.setReadOnly(true);
        this.inputField.setReadOnly(true);

        return this.component;
    },

    getValue: function () {
        return {
            unit: this.unitField.getValue(),
            value: this.inputField.getValue()
        };
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});
