/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS('pimcore.object.tags.quantityValueRange');
/**
 * @private
 */
pimcore.object.tags.quantityValueRange = Class.create(pimcore.object.tags.abstract, {
    type: 'quantityValueRange',

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
        this.applyDefaultValue();
    },

    applyDefaultValue: function() {
        this.defaultUnit = null;
        this.autoConvert = false;

        if ((typeof this.data === "undefined" || this.data === null) && (this.fieldConfig.defaultUnit || this.fieldConfig.autoConvert)) {
            this.data = {
                unit: this.fieldConfig.defaultUnit,
                autoConvert: this.fieldConfig.autoConvert
            };
            this.defaultUnit = this.data.unit;
            this.autoConvert = this.data.autoConvert;
        }
    },

    finishSetup: function () {
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'data',
            fields: ['id', 'abbreviation']
        });

        pimcore.helpers.quantityValue.initUnitStore(this.setData.bind(this), this.fieldConfig.validUnits, this.data);
    },

    setData: function (data) {
        const storeData = data.data;
        storeData.unshift({ id: -1, abbreviation : `(${t('empty')})` });

        this.store.loadData(storeData);

        if (this.unit) {
            this.unit.reset();
        }
    },

    getLayoutEdit: function () {
        if (typeof this.store === 'undefined') {
            this.finishSetup();
        }

        const input = {
            mouseWheelEnabled: false,
            flex: 1,
        };

        if (this.fieldConfig['decimalPrecision'] !== null) {
            input.decimalPrecision = this.fieldConfig['decimalPrecision'];
        }

        this.minimum = new Ext.form.field.Number(input);
        this.maximum = new Ext.form.field.Number(input);

        if (this.data) {
            if (!isNaN(this.data.minimum)) {
                this.minimum.setRawValue(this.data.minimum);
                this.minimum.resetOriginalValue();
            } else {
                this.data.minimum = null;
            }

            if (!isNaN(this.data.maximum)) {
                this.maximum.setRawValue(this.data.maximum);
                this.maximum.resetOriginalValue();
            } else {
                this.data.maximum = null;
            }
        }

        let width = 255;
        if (this.fieldConfig.width) {
            width = this.fieldConfig.width + 5;
        }

        let labelWidth = 100;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }

        let unitWidth = 100;
        if (this.fieldConfig.unitWidth) {
            unitWidth = this.fieldConfig.unitWidth;
        }

        let labelAlign = 'left';
        if (this.fieldConfig.labelAlign) {
            labelAlign = this.fieldConfig.labelAlign;
        }

        if (labelAlign === 'left') {
            width = this.sumWidths(width, labelWidth);
            width = this.sumWidths(width, unitWidth);
        }

        const options = {
            width: unitWidth,
            triggerAction: 'all',
            autoSelect: true,
            editable: true,
            selectOnFocus: true,
            forceSelection: true,
            store: this.store,
            valueField: 'id',
            displayField: 'abbreviation',
            queryMode: 'local',
        };

        if (this.data && this.data.unit !== null) {
            options.value = this.data.unit;
        } else {
            options.value = -1;
        }

        this.unit = new Ext.form.ComboBox(options);

        this.component = new Ext.form.FieldContainer({
            layout: 'hbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelAlign: labelAlign,
            labelWidth: labelWidth,
            width: width,
            combineErrors: false,
            items: [this.minimum, { xtype: 'splitter' }, this.maximum, this.unit],
            componentCls: this.getWrapperClassNames(),
            isDirty: function () {
                return this.minimum.isDirty() || this.maximum.isDirty() || this.unit.isDirty()
            }.bind(this),
        });

        return this.component;
    },

    getGridColumnConfig: function (field) {
        const renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited === true) {
                try {
                    metaData.tdCls += ' grid_value_inherited';
                } catch (e) {
                    console.log(e);
                }
            }

            if (value) {
                return `[${value.minimum || ''}, ${value.maximum || ''}] ${t(value.unitAbbr)}`;
            } else {
                return '';
            }
        }.bind(this, field.key);

        return {
            getEditor: this.getWindowCellEditor.bind(this, field),
            text: t(field.label),
            sortable: true,
            dataIndex: field.key,
            renderer: renderer,
        };
    },

    getLayoutShow: function () {
        this.getLayoutEdit();
        this.minimum.setReadOnly(true);
        this.maximum.setReadOnly(true);
        this.unit.setReadOnly(true);

        return this.component;
    },

    getValue: function () {
        return {
            minimum: this.minimum.getValue(),
            maximum: this.maximum.getValue(),
            unit: this.unit.getValue(),
        };
    },

    getCellEditValue: function () {
        const value = this.getValue();
        value['unitAbbr'] = this.unit.getRawValue();

        return value;
    },

    getName: function () {
        return this.fieldConfig.name;
    },
});
