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

        this.data = data;
        this.isEmpty = this.data === null;


        if (typeof data === "undefined" && fieldConfig.defaultValue) {
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

    getLayoutEdit: function (disabled) {
        var sliderConfig = {
            name: this.fieldConfig.name,
            componentCls: "object_field object_field_type_" + this.type,
            plugins: new Ext.slider.Tip()
        };

        if (this.data != null) {
            sliderConfig.value = this.data;
        }

        if (this.fieldConfig.width && !this.fieldConfig.vertical) {
            sliderConfig.width = this.fieldConfig.width;
        }
        if (this.fieldConfig.height) {
            sliderConfig.height = this.fieldConfig.height;
        } else if(this.fieldConfig.vertical) {
            sliderConfig.height = 200;
        }

        if (this.fieldConfig.minValue) {
            sliderConfig.minValue = this.fieldConfig.minValue;
        }
        if (this.fieldConfig.maxValue) {
            sliderConfig.maxValue = this.fieldConfig.maxValue;
        }
        if (this.fieldConfig.vertical) {
            sliderConfig.vertical = true;
        }
        if (this.fieldConfig.increment) {
            sliderConfig.increment = this.fieldConfig.increment;
            sliderConfig.keyIncrement = this.fieldConfig.increment;
        }
        if (this.fieldConfig.decimalPrecision) {
            sliderConfig.decimalPrecision = this.fieldConfig.decimalPrecision;
        }

        this.slider = new Ext.Slider(sliderConfig);

        this.slider.on("afterrender", this.showValueInLabel.bind(this));

        this.slider.on("dragstart", function() {
            // value change initiated by the user, it is not null anymore!
            this.isEmpty = false;
        }.bind(this));

        this.slider.on("change", function (newValue) {
            // update label while dragging
            this.showValueInLabel();
        }.bind(this));

        this.slider.on("changecomplete", function (newValue) {
            this.dirty = true;
            this.isEmpty = false;           // value change initiated by the user
            this.showValueInLabel();
        }.bind(this));

        var items = [this.slider];

        if (!disabled) {
            this.emptyButton = new Ext.Button({
                iconCls: "pimcore_icon_delete",
                cls: 'pimcore_button_transparent',
                tooltip: t("set_to_null"),
                handler: function () {
                    // note that even if we set it to null slider's new value will be constrained
                    // within minValue and maxValue
                    this.isEmpty = true;
                    this.dirty = true;
                    this.slider.setValue(null, false);      // set to minValue, do not animate
                    this.showValueInLabel();
                }.bind(this),
                style: "margin-left: 10px; filter:grayscale(100%);",
            });
            items.push(this.emptyButton);
        }

        var componentCfg = {
            fieldLabel: this.fieldConfig.title,
            layout: 'hbox',
            items: items,
            componentCls: "object_field object_field_type_" + this.type,
            border: false,
            style: {
                padding: 0
            }
        };

        if (this.fieldConfig.labelWidth) {
            componentCfg.labelWidth = this.fieldConfig.labelWidth;
        }

        this.component = Ext.create('Ext.form.FieldContainer', componentCfg);

        return this.component;
    },

    addInheritanceSourceButton:function ($super, metaData) {
        this.updateStyle("#6782F6");
        $super();
    },

    updateStyle: function(newStyle) {

        if ((this.context && this.context.cellEditing) || !this.getObject() || !this.getObject().data.general.allowInheritance) {
            return;
        }

        var sliderEl = this.slider.el.down('.x-slider-thumb');

        if (sliderEl) {
            if (!newStyle) {
                newStyle = this.getStyle();
            }

            sliderEl.setStyle('border-color', newStyle);
        }
    },

    getStyle: function() {
        if (this.isEmpty) {
            return '#6782F6';
        }

        return '';
    },

    showValueInLabel: function () {
        var labelEl = this.component.labelEl;

        if (!this.labelText) {
            this.labelText = labelEl.dom.innerHTML;
        }

        var value = null;
        if (!this.isEmpty) {
            value = this.slider.getValue();
        }

        if(value === null) {
            value = t('no_value_set');
        }

        labelEl.update(this.labelText + " (" + value  + ")");
        this.updateStyle();
    },

    getLayoutShow: function () {
        this.component = this.getLayoutEdit(true);
        this.component.disable();
        return this.component;
    },

    getValue: function () {
        if (this.isEmpty) {
            return null;
        }
        var currentValue = this.slider.getValue();
        return currentValue.toString();
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
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
            text: t(field.label), sortable: true, dataIndex: field.key, renderer: renderer,
            getEditor: this.getWindowCellEditor.bind(this, field)
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});
