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

pimcore.registerNS("pimcore.object.tags.consent");
pimcore.object.tags.consent = Class.create(pimcore.object.tags.abstract, {

    type:"consent",

    initialize:function (data, fieldConfig) {

        this.data = {
            'consent': false,
            'note-text': ''
        };

        if (data) {
            this.data = data;
        }

        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig:function (field) {
        var columnConfig = {
            text: t(field.label),
            dataIndex: field.key,
            renderer:function (key, value, metaData, record, rowIndex, colIndex, store) {
                var key = field.key;
                var noteditable = field.layout.noteditable;
                this.applyPermissionStyle(key, value, metaData, record);

                try {
                    if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                        metaData.tdCls += " grid_value_inherited";
                    }
                    if (noteditable) {
                        metaData.tdCls += ' grid_cbx_noteditable';

                    }
                    metaData.tdCls += ' x-grid-check-col-td';
                } catch (e) {
                    console.log(e);
                }
                return Ext.String.format('<div style="text-align: center"><div role="button" class="x-grid-checkcolumn{0}" style=""></div></div>', value.consent ? '-checked' : '');
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

        var width = 200;
        if (this.fieldConfig.width) {
            width = this.fieldConfig.width;
        }

        this.textLabel = Ext.create('Ext.Panel', {
            style: "margin-bottom: 10px;",
            html: this.data.noteContent,
            width: width
        });


        this.checkBox = Ext.create('Ext.form.field.Checkbox', {
            name: this.fieldConfig.name,
            value: this.data.consent,
            listeners: {
                change: function() {
                    this.textLabel.setHtml('');
                    this.component.update();
                }.bind(this)
            }
        });


        var labelWidth = 200;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }

        this.component = Ext.create('Ext.form.FieldContainer', {
            layout: 'vbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            combineErrors: false,
            width: width,
            items: [this.checkBox, this.textLabel],
            componentCls: "object_field object_field_type_" + this.type,
            isDirty: function() {
                return this.checkBox.isDirty()
            }.bind(this)
        });

        return this.component;
    },


    getLayoutShow:function () {

        this.component = this.getLayoutEdit();
        this.checkBox.disable();

        return this.component;
    },

    getValue:function () {
        return this.checkBox.getValue();
    },

    getName:function () {
        return this.fieldConfig.name;
    }
});
