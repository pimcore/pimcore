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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.slider");
pimcore.object.tags.slider = Class.create(pimcore.object.tags.abstract, {

    type:"slider",

    initialize:function (data, fieldConfig) {

        this.data = "";

        if (data) {
            this.data = data;
        } else if (typeof data === "undefined" && fieldConfig.defaultValue) {
            this.data = fieldConfig.defaultValue;
        }

        if (!fieldConfig.width) {
            fieldConfig.width = 300;
        }

        this.fieldConfig = fieldConfig;

    },

    getGridColumnFilter:function (field) {
        return {type:'numeric', dataIndex:field.key};
    },

    getLayoutEdit:function () {

        var slider = {
            fieldLabel:this.fieldConfig.title,
            name:this.fieldConfig.name,
            itemCls:"object_field"
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

    showValueInLabel:function () {
        var labelEl = this.component.label;

        if (!this.labelText) {
            this.labelText = labelEl.dom.innerHTML;
        }
        var el = labelEl.update(this.labelText + " (" + this.component.getValue() + ")");
    },

    getLayoutShow:function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue:function () {
        return this.component.getValue().toString();
    },

    getName:function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory:function () {
        return false;
    },

    isDirty:function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }
});