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

pimcore.registerNS("pimcore.object.tags.date");
pimcore.object.tags.date = Class.create(pimcore.object.tags.abstract, {

    type:"date",

    initialize:function (data, fieldConfig) {

        if ((typeof data === "undefined" || data === null) && fieldConfig.defaultValue) {
            data = fieldConfig.defaultValue;
        } else if ((typeof data === "undefined" || data === null) && fieldConfig.useCurrentDate) {
            data = (new Date().getTime()) / 1000;
        }

        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig:function (field) {
        return {header:ts(field.label), width:150, sortable:false, dataIndex:field.key, renderer:function (key, value, metaData, record) {
            if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }

            if (value) {
                var timestamp = intval(value) * 1000;
                var date = new Date(timestamp);

                return date.format("Y-m-d");
            }
            return "";
        }.bind(this, field.key)};
    },

    getGridColumnFilter:function (field) {
        return {type:'date', dataIndex:field.key};
    },

    getLayoutEdit:function () {

        var date = {
            fieldLabel:this.fieldConfig.title,
            name:this.fieldConfig.name,
            itemCls:"object_field",
            width:100
        };

        if (this.data) {
            var tmpDate = new Date(intval(this.data) * 1000);
            date.value = tmpDate;
        }

        this.component = new Ext.form.DateField(date);
        return this.component;
    },

    getLayoutShow:function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue:function () {
        if (this.component.getValue()) {
            return this.component.getValue().getTime();
        }
        return false;
    },

    getName:function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory:function () {

        // no render check is necessary because the date compontent returns the right values even it is not rendered
        if (this.getValue() == false) {
            return true;
        }
        return false;
    },

    isDirty:function () {
        var dirty = false;
        if (this.component && typeof this.component.isDirty == "function") {

            if (!this.component.rendered) {
                if(!this.fieldConfig.defaultValue && !this.fieldConfig.useCurrentDate){
                    return false;
                } else return true;

            } else {
                dirty = this.component.isDirty();

                if(!dirty && (this.fieldConfig.defaultValue || this.fieldConfig.useCurrentDate)){
                   dirty = true;
                }

                // once a field is dirty it should be always dirty (not an ExtJS behavior)
                if (this.component["__pimcore_dirty"]) {
                    dirty = true;
                }
                if (dirty) {
                    this.component["__pimcore_dirty"] = true;
                }

                return dirty;
            }
        }

        throw "isDirty() is not implemented";
    }
});