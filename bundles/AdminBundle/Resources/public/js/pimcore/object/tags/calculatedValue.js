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

pimcore.registerNS("pimcore.object.tags.calculatedValue");
pimcore.object.tags.calculatedValue = Class.create(pimcore.object.tags.abstract, {

    type: "calculatedValue",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },


    getLayoutEdit: function () {

        var input = {
            fieldLabel: '<img src="/bundles/pimcoreadmin/img/flat-color-icons/calculator.svg" style="height: 1.8em; display: inline-block; vertical-align: middle;"/>' + this.fieldConfig.title,
            componentCls: "object_field object_field_type_" + this.type,
            labelWidth: 100,
            readOnly: true,
            width: 100
        };

        if (this.data) {
            input.value = this.data.value;
        }

        if (isNaN(this.fieldConfig.width)) {
            input.width = 100;
        } else if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        if (!isNaN(this.fieldConfig.labelWidth)) {
            input.labelWidth = this.fieldConfig.labelWidth;
        }

        input.width += input.labelWidth;


        if (this.data) {
            input.value = this.data;
        }

        if(this.fieldConfig.elementType === 'textarea') {
            this.component = new Ext.form.field.TextArea(input);
        } else {
            this.component = new Ext.form.field.Text(input);
        }

        return this.component;
    },

    getLayoutShow: function () {
        this.getLayoutEdit();
        this.component.setReadOnly(true);

        return this.component;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    getGridColumnFilter: function (field) {
        return {type: 'string', dataIndex: field.key};
    },

    getGridColumnConfig:function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            try {
                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }
            } catch (e) {
                console.log(e);
            }

            if (value) {
                value = value.replace(/\n/g,"<br>");
                value = strip_tags(value, '<br>');
            }
            return value;
        }.bind(this, field.key);

        return {text: t(field.label), sortable:true, dataIndex:field.key, renderer:renderer,
            editor:this.getGridColumnEditor(field)};
    }
});
