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

pimcore.registerNS("pimcore.object.tags.checkbox");
pimcore.object.tags.checkbox = Class.create(pimcore.object.tags.abstract, {

    type:"checkbox",

    initialize:function (data, fieldConfig) {

        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    applyDefaultValue: function() {
        if ((typeof this.data === "undefined" || this.data === null)) {
            if (this.fieldConfig.defaultValue !== null) {
                this.dataChanged = true;
            }

            this.data = this.fieldConfig.defaultValue;
        }
    },


    getGridColumnConfig:function (field) {
        var columnConfig = {
            text: t(field.label),
            dataIndex:field.key,
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

    getStyle: function() {
        if (this.data === null) {
            return '#6782F6';
        }

        return '';
    },

    updateStyle: function(newStyle) {

        if(!this.getObject() || !this.getObject().data.general.allowInheritance) {
            return;
        }

        var cbEl = this.checkbox.el.down('.x-form-checkbox');

        if (cbEl) {
            if (!newStyle) {
                newStyle = this.getStyle();
            }

            cbEl.setStyle('color', newStyle);
        }
    },

    getLayoutEdit:function () {

        var checkbox = {
            name:this.fieldConfig.name,
            value: this.data,
            width: 25,
            handler: function (checkbox, checked) {
                this.dataChanged = true;
                this.data = this.checkbox.getValue();
                this.updateStyle();
            }.bind(this),
            listeners: {
                afterrender: function() {
                    this.updateStyle();
                }.bind(this)
            }
        };

        if (this.fieldConfig.labelWidth) {
            checkbox.labelWidth = this.fieldConfig.labelWidth;
        }


        this.createEmptyButton();

        this.checkbox = new Ext.form.Checkbox(checkbox);

        var componentCfg = {
            fieldLabel:this.fieldConfig.title,
            layout: 'hbox',
            items: [
                this.checkbox,
                this.emptyButton
            ],
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

    createEmptyButton: function() {
        if (this.getObject()) {
            this.emptyButton = new Ext.Button({
                iconCls: "pimcore_icon_delete",
                cls: 'pimcore_button_transparent',
                tooltip: t("set_to_null"),
                hidden: this.fieldConfig.hideEmptyButton || !this.getObject().data.general.allowInheritance,
                handler: function () {
                    if (this.data !== null) {
                        this.dataChanged = true;
                    }
                    this.checkbox.setValue(false);

                    this.data = null;
                    this.updateStyle();
                }.bind(this),
                style: "margin-left: 10px; filter:grayscale(100%);",
            });
        }
    },

    addInheritanceSourceButton:function ($super, metaData) {
        this.updateStyle("#6782F6");
        $super();
    },

    getLayoutShow:function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue:function () {
        return this.data;
    },

    getName:function () {
        return this.fieldConfig.name;
    },

    isDirty:function () {
        return this.dataChanged;
    }
});
