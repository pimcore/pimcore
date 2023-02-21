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

pimcore.registerNS('pimcore.object.tags.dateRange');
/**
 * @private
 */
pimcore.object.tags.dateRange = Class.create(pimcore.object.tags.abstract, {
    type: 'dateRange',

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    getLayoutEdit: function () {
        const startDateConfig = { format: 'Y-m-d' };
        const endDateConfig = { format: 'Y-m-d' };

        if (this.data && 'start_date' in this.data) {
            startDateConfig.value = new Date(intval(this.data['start_date']) * 1000);
        }

        if (this.data && 'end_date' in this.data) {
            endDateConfig.value = new Date(intval(this.data['end_date']) * 1000);
        }

        this.startDate = new Ext.form.DateField(startDateConfig);
        this.endDate = new Ext.form.DateField(endDateConfig);

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
            items: [this.startDate, { xtype: 'splitter' }, this.endDate],
            componentCls: this.getWrapperClassNames(),
            isDirty: function() {
                return this.startDate.isDirty() || this.endDate.isDirty();
            }.bind(this),
        });

        return this.component;
    },

    getLayoutShow: function () {
        this.component = this.getLayoutEdit();
        this.component.items[0].setReadonly(true);
        this.component.items[2].setReadonly(true);

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
                    const minDate = new Date(intval(value['start_date'] || 0) * 1000);
                    const maxDate = new Date(intval(value['end_date'] || 0) * 1000);

                    return `${Ext.Date.format(minDate, 'Y-m-d')}, ${Ext.Date.format(maxDate, 'Y-m-d')}`;
                }

                return '';
            }.bind(this, field.key),
        };
    },

    getValue: function () {
        let startDate = this.startDate.getValue();
        let endDate = this.endDate.getValue();

        if (startDate && typeof startDate.getTime === 'function') {
            startDate = startDate.getTime();
        }

        if (endDate && typeof endDate.getTime === 'function') {
            endDate = endDate.getTime();
        }

        return {
            start_date: startDate,
            end_date: endDate,
        };
    },

    getCellEditValue: function () {
        const values = this.getValue();

        return {
            start_date: values.start_date / 1000,
            end_date: values.end_date / 1000,
        };
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
