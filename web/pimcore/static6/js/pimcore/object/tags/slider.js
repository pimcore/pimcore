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

pimcore.registerNS("pimcore.object.tags.slider");
pimcore.object.tags.slider = Class.create(pimcore.object.tags.abstract, {

    type: "slider",

    initialize: function (data, fieldConfig) {

        this.data = "";

        if (data) {
            this.data = data;
        } else if (typeof data === "undefined" && fieldConfig.defaultValue) {
            this.data = fieldConfig.defaultValue;
        }

        if (!fieldConfig.width) {
            fieldConfig.width = 350;
        }

        this.fieldConfig = fieldConfig;

    },

    getGridColumnFilter: function (field) {
        return {type: 'numeric', dataIndex: field.key};
    },

    getLayoutEdit: function () {

        var slider = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            componentCls: "object_field"
        };

        if (this.data) {
            slider.value = this.data;
        }

        if (this.fieldConfig.width) {
            slider.width = this.fieldConfig.width;
        }
        if (this.fieldConfig.height) {
            slider.height = this.fieldConfig.height;
        }
        if (this.fieldConfig.minValue) {
            slider.minValue = this.fieldConfig.minValue;
        }
        if (this.fieldConfig.maxValue) {
            slider.maxValue = this.fieldConfig.maxValue;
        }
        if (this.fieldConfig.vertical) {
            slider.vertical = true;
        }
        if (this.fieldConfig.increment) {
            slider.increment = this.fieldConfig.increment;
            slider.keyIncrement = this.fieldConfig.increment;
        }
        if (this.fieldConfig.decimalPrecision) {
            slider.decimalPrecision = this.fieldConfig.decimalPrecision;
        }

        slider.plugins = new Ext.slider.Tip();

        this.component = new Ext.Slider(slider);

        this.component.on("afterrender", this.showValueInLabel.bind(this));
        this.component.on("dragend", this.showValueInLabel.bind(this));
        this.component.on("change", this.showValueInLabel.bind(this));

        this.component.on("change", function () {
            this.dirty = true;
        }.bind(this));

        return this.component;
    },

    showValueInLabel: function () {
        var labelEl = this.component.labelEl;

        if (!this.labelText) {
            this.labelText = labelEl.dom.innerHTML;
        }
        var el = labelEl.update(this.labelText + " (" + this.component.getValue() + ")");
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.component.getValue().toString();
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        return false;
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
    },

    getCellEditor: function (field, record) {
        return new pimcore.object.helpers.gridCellEditor({
            fieldInfo: field
        });
    },

    getGridColumnConfig: function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            try {
                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }
            } catch (e) {
                console.log(e);
            }
            return value;

        }.bind(this, field.key);

        return {
            header: ts(field.label), sortable: true, dataIndex: field.key, renderer: renderer,
            getEditor: this.getCellEditor.bind(this, field)
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});