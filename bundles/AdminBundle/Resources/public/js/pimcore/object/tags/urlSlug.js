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

pimcore.registerNS("pimcore.object.tags.urlSlug");
pimcore.object.tags.urlSlug = Class.create(pimcore.object.tags.abstract, {

    type: "urlSlug",

    initialize: function (data, fieldConfig) {

        this.data = "";

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnEditor: function(field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if(field.layout.noteditable) {
            return null;
        }
        return new Ext.form.TextField(editorConfig);
    },

    getGridColumnFilter: function(field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            componentCls: "object_field",
            labelWidth: 100
        };

        if (this.data) {
            input.value = this.data;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        } else {
            input.width = 250;
        }

        if (this.fieldConfig.labelWidth) {
            input.labelWidth = this.fieldConfig.labelWidth;
        }
        input.width += input.labelWidth;

        if(this.fieldConfig.columnLength) {
            input.maxLength = this.fieldConfig.columnLength;
            input.enforceMaxLength = true;
        }

        input.regex = new RegExp("^(\\/\\w+)+(\\.)?\\w+(\\?(\\w+=[\\w\\d]+(&\\w+=[\\w\\d]+)*)+){0,1}$");

        this.component = new Ext.form.TextField(input);
        return this.component;
    },
    

    getLayoutShow: function () {
        var layout = this.getLayoutEdit();
        this.component.setReadOnly(true);
        return layout;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
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

            return Ext.util.Format.htmlEncode(value);

        }.bind(this, field.key);

        return {text:ts(field.label), sortable:false, dataIndex:field.key, renderer:renderer,
            editor:this.getGridColumnEditor(field)};
    }

});