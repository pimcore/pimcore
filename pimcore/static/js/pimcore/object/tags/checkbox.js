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

pimcore.registerNS("pimcore.object.tags.checkbox");
pimcore.object.tags.checkbox = Class.create(pimcore.object.tags.abstract, {

    type:"checkbox",

    initialize:function (data, fieldConfig) {

        this.data = "";

        if (data) {
            this.data = data;
        } else if ((typeof data === "undefined" || data === null) && fieldConfig.defaultValue) {
            this.data = fieldConfig.defaultValue;
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig:function (field) {
        return new Ext.grid.CheckColumn({
            header:ts(field.label),
            dataIndex:field.key,
            renderer:function (key, value, metaData, record, rowIndex, colIndex, store) {
                var key = field.key;
                var noteditable = field.layout.noteditable;
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.css += " grid_value_inherited";
                }
                if (noteditable) {
                    metaData.css += ' grid_cbx_noteditable';

                }
                metaData.css += ' x-grid3-check-col-td';
                return String.format('<div class="x-grid3-check-col{0}">&#160;</div>', value ? '-on' : '');
            }.bind(this, field)
        });
    },

    getGridColumnFilter:function (field) {
        return {type:'boolean', dataIndex:field.key};
    },

    getLayoutEdit:function () {

        var checkbox = {
            fieldLabel:this.fieldConfig.title,
            name:this.fieldConfig.name,
            itemCls:"object_field"
        };


        if (this.fieldConfig.width) {
            checkbox.width = this.fieldConfig.width;
        }

        this.component = new Ext.form.Checkbox(checkbox);

        this.component.setValue(this.data);
        return this.component;
    },


    getLayoutShow:function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue:function () {
        return this.component.getValue();
    },

    getName:function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory:function () {
        return false;
    },

    isDirty:function () {
        var dirty = false;
        if (this.component && typeof this.component.isDirty == "function") {

            if (!this.component.rendered) {
                if (!this.fieldConfig.defaultValue) {
                    return false;
                } else {
                    return true;
                }

            } else {
                dirty = this.component.isDirty();

                if (!dirty && (this.fieldConfig.defaultValue)) {
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