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

pimcore.registerNS("pimcore.object.tags.date");
pimcore.object.tags.date = Class.create(pimcore.object.tags.abstract, {

    type:"date",

    initialize:function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    applyDefaultValue: function() {
        this.defaultValue = null;

        if ((typeof this.data === "undefined" || this.data === null) && this.fieldConfig.defaultValue) {
            this.defaultValue = this.fieldConfig.defaultValue;
        } else if ((typeof this.data === "undefined" || this.data === null) && this.fieldConfig.useCurrentDate) {
            this.defaultValue = (new Date().getTime()) / 1000;
        }

        if(this.defaultValue) {
            this.data = this.defaultValue;
        }
    },

    getGridColumnConfig:function (field) {
        return {text: t(field.label), width:150, sortable:true, dataIndex:field.key,
            getEditor:this.getWindowCellEditor.bind(this, field),
            renderer:function (key, value, metaData, record) {

                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                if (value) {
                    var timestamp = intval(value) * 1000;
                    var date = new Date(timestamp);

                    return Ext.Date.format(date, "Y-m-d");
                }
                return "";
            }.bind(this, field.key)};
    },

    getGridColumnFilter:function (field) {
        return {type:'date', dataIndex:field.key, dateFormat: 'm/d/Y'};
    },

    getLayoutEdit:function () {

        var date = {
            fieldLabel:this.fieldConfig.title,
            name:this.fieldConfig.name,
            componentCls: "object_field object_field_type_" + this.type,
            width:130,
            format: "Y-m-d"
        };

        if (this.fieldConfig.labelWidth) {
            date.labelWidth = this.fieldConfig.labelWidth;
        }
        date.width += date.labelWidth;

        if (this.data) {
            var tmpDate = new Date(intval(this.data) * 1000);
            date.value = tmpDate;
        }

        this.component = new Ext.form.DateField(date);
        return this.component;
    },

    getLayoutShow:function () {

        this.component = this.getLayoutEdit();
        this.component.setReadOnly(true);

        return this.component;
    },

    getValue:function () {
        if (this.component.getValue()) {
            return this.component.getValue().getTime();
        }
        return false;
    },

    getCellEditValue: function () {
        return this.getValue() / 1000;
    },

    getName:function () {
        return this.fieldConfig.name;
    },

    isDirty:function () {
        var dirty = false;

        if(this.defaultValue){
            return true;
        }

        if (this.component && typeof this.component.isDirty == "function") {
            if (this.component.rendered) {
                dirty = this.component.isDirty();

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

        return false;
    }

});
