/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        var columnConfig = {
            header:ts(field.label),
            dataIndex:field.key,
            renderer:function (key, value, metaData, record, rowIndex, colIndex, store) {
                var key = field.key;
                var noteditable = field.layout.noteditable;
                this.applyPermissionStyle(key, value, metaData, record);

                try {
                    if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                        metaData.tdCls += " grid_value_inherited";
                    }
                    if (noteditable) {
                        metaData.tdCls += ' grid_cbx_noteditable';

                    }
                    metaData.tdCls += ' x-grid-check-col-td';
                } catch (e) {
                    console.log(e);
                }
                return Ext.String.format('<div style="text-align: center"><div role="button" class="x-grid-checkcolumn{0}" style=""></div></div>', value ? '-checked' : '');
            }.bind(this, field)
        };

        if(!field.layout.noteditable) {
            columnConfig.editor = Ext.create('Ext.form.field.Checkbox', {style: 'margin-top: 2px;'});
        }

        return columnConfig;
    },

    getGridColumnFilter:function (field) {
        return {type:'boolean', dataIndex:field.key};
    },

    getLayoutEdit:function () {

        var checkbox = {
            fieldLabel:this.fieldConfig.title,
            name:this.fieldConfig.name,
            componentCls:"object_field",
            value: this.data
        };

        if (this.fieldConfig.labelWidth) {
            checkbox.labelWidth = this.fieldConfig.labelWidth;
        }
        checkbox.width += checkbox.labelWidth;

        if (this.fieldConfig.width) {
            checkbox.width = this.fieldConfig.width;
        }

        this.component = new Ext.form.Checkbox(checkbox);

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