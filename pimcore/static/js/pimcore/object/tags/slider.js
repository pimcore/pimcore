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

pimcore.registerNS("pimcore.object.tags.slider");
pimcore.object.tags.slider = Class.create(pimcore.object.tags.abstract, {

    type: "slider",

    initialize: function (data, layoutConf) {

        this.data = "";

        if (data) {
            this.data = data;
        }
        
        if (!layoutConf.width) {
            layoutConf.width = 300;
        }
        
        this.layoutConf = layoutConf;

    },

    getGridColumnFilter: function(field) {
        return {type: 'numeric', dataIndex: field.key};
    },    

    getLayoutEdit: function () {

        var slider = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
            itemCls: "object_field"
        };

        if (this.data) {
            slider.value = this.data;
        }

        if (this.layoutConf.width) {
            slider.width = this.layoutConf.width;
        }
        if (this.layoutConf.height) {
            slider.height = this.layoutConf.height;
        }
        if (this.layoutConf.minValue) {
            slider.minValue = this.layoutConf.minValue;
        }
        if (this.layoutConf.maxValue) {
            slider.maxValue = this.layoutConf.maxValue;
        }
        if (this.layoutConf.vertical) {
            slider.vertical = true;
        }
        if (this.layoutConf.increment) {
            slider.increment = this.layoutConf.increment;
            slider.keyIncrement = this.layoutConf.increment;
        }
        if (this.layoutConf.decimalPrecision) {
            slider.decimalPrecision = this.layoutConf.decimalPrecision;
        }
        
        slider.plugins = new Ext.slider.Tip();
        
        this.layout = new Ext.Slider(slider);
        
        this.layout.on("afterrender", this.showValueInLabel.bind(this));
        this.layout.on("dragend", this.showValueInLabel.bind(this));
        this.layout.on("change", this.showValueInLabel.bind(this));

        this.layout.on("change", function() {
            this.dirty = true;
        }.bind(this));
        
        return this.layout;
    },

    showValueInLabel: function () {
        var labelEl = Ext.get(this.layout.getEl().parent(".object_field").query("label")[0]);
        
        if(!this.labelText) {
            this.labelText = labelEl.dom.innerHTML;
        }
        var el = labelEl.update(this.labelText + " (" + this.layout.getValue() + ")");
    },
    
    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        return this.layout.getValue().toString();
    },

    getName: function () {
        return this.layoutConf.name;
    },

    isInvalidMandatory: function () {
        return false;
    },

    isDirty: function() {
        if(!this.layout.rendered) {
            return false;
        }
        
        return this.dirty;
    }
});