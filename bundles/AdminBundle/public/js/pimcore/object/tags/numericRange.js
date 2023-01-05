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

pimcore.registerNS('pimcore.object.tags.numericRange');
/**
 * @private
 */
pimcore.object.tags.numericRange = Class.create(pimcore.object.tags.abstract, {
    type: 'numericRange',

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    getLayoutEdit: function () {
        const fieldConfig = { mouseWheelEnabled: false };

        if (this.fieldConfig['unsigned']) {
            fieldConfig.minValue = 0;
        }

        if (is_numeric(this.fieldConfig['minValue'])) {
            fieldConfig.minValue = this.fieldConfig.minValue;
        }

        if (is_numeric(this.fieldConfig['maxValue'])) {
            fieldConfig.maxValue = this.fieldConfig.maxValue;
        }

        if (this.fieldConfig['integer']) {
            fieldConfig.decimalPrecision = 0;
        } else if (this.fieldConfig['decimalPrecision']) {
            fieldConfig.decimalPrecision = this.fieldConfig['decimalPrecision'];
        } else {
            fieldConfig.decimalPrecision = 20;
        }

        this.minimum = new Ext.form.field.Number(fieldConfig);
        this.maximum = new Ext.form.field.Number(fieldConfig);

        if (this.data) {
            // set raw values to stop values being initially dirty
            this.minimum.setRawValue(this.data.minimum);
            this.minimum.resetOriginalValue();
            this.maximum.setRawValue(this.data.maximum);
            this.maximum.resetOriginalValue();
        }

        let width = 255;
        if (this.fieldConfig.width) {
            width = this.fieldConfig.width + 5;
        }

        let labelWidth = 100;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }

        let labelAlign = 'left';
        if (this.fieldConfig.labelAlign) {
            labelAlign = this.fieldConfig.labelAlign;
        }

        if (labelAlign === 'left') {
            width = this.sumWidths(width, labelWidth);
        }

        this.component = new Ext.form.FieldContainer({
            layout: 'hbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelAlign: labelAlign,
            labelWidth: labelWidth,
            width: width,
            combineErrors: false,
            fieldDefaults: { flex: 1 },
            items: [this.minimum, { xtype: 'splitter' }, this.maximum],
            componentCls: this.getWrapperClassNames(),
            isDirty: function() {
                return this.minimum.isDirty() || this.maximum.isDirty();
            }.bind(this),
        });

        return this.component;
    },

    getLayoutShow: function () {
        this.minimum = new Ext.form.field.Text();
        this.minimum.setReadOnly(true);
        this.maximum = new Ext.form.field.Text();
        this.maximum.setReadOnly(true);

        if (this.data) {
            this.minimum.setRawValue(this.data.minimum);
            this.maximum.setRawValue(this.data.maximum);
        }

        let width = 255;
        if (this.fieldConfig.width) {
            width = this.fieldConfig.width + 5;
        }

        let labelWidth = 100;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }

        let labelAlign = 'left';
        if (this.fieldConfig.labelAlign) {
            labelAlign = this.fieldConfig.labelAlign;
        }

        if (labelAlign === 'left') {
            width = this.sumWidths(width, labelWidth);
        }

        this.component = new Ext.form.FieldContainer({
            layout: 'hbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelAlign: labelAlign,
            labelWidth: labelWidth,
            width: width,
            combineErrors: false,
            fieldDefaults: { flex: 1 },
            items: [this.minimum, { xtype: 'splitter' }, this.maximum],
            componentCls: this.getWrapperClassNames(),
            isDirty: function() {
                return this.minimum.isDirty() || this.maximum.isDirty();
            }.bind(this),
        });

        return this.component;
    },

    getGridColumnConfig: function (field) {
        return {
            text: t(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited === true) {
                    metaData.tdCls += ' grid_value_inherited';
                }

                if (value) {
                    const min = value.minimum || '-∞';
                    const max = value.maximum || '+∞';

                    return `[${min}, ${max}]`;
                }
            }.bind(this, field.key),
        };
    },

    getValue: function () {
        return {
            minimum: this.minimum.getValue(),
            maximum: this.maximum.getValue()
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    },

    isDirty: function () {
        let dirty = false;

        if (this.component && typeof this.component.isDirty === 'function' && this.component.rendered) {
            dirty = this.component.isDirty();

            // once a field is dirty it should be always dirty (not an ExtJS behavior)
            if (this.component['__pimcore_dirty']) {
                dirty = true;
            }

            if (dirty) {
                this.component['__pimcore_dirty'] = true;
            }

            return dirty;
        }

        return false;
    },
});
