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

pimcore.registerNS("pimcore.object.tags.rgbaColor");
pimcore.object.tags.rgbaColor = Class.create(pimcore.object.tags.abstract, {

    type: "rgbaColor",

    initialize: function (data, fieldConfig) {

        this.data = null;

        if (data) {
            this.data = data;
        }

        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function (field) {

        return {
            text: t(field.label), width: 120, sortable: false, dataIndex: field.key, sortable: true,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                if (value) {
                    var result = '<div style="float: left;"><div style="float: left; margin-right: 5px; background-image: ' + ' url(/bundles/pimcoreadmin/img/ext/colorpicker/checkerboard.png);">'
                        + '<div style="background-color: ' + value + '; width:15px; height:15px;"></div></div>' + value + '</div>';
                    return result;
                }

            }.bind(this, field.key)
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    },

    getGridColumnEditor: function (field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if (field.layout.noteditable) {
            return null;
        }
        return new Ext.form.TextField(editorConfig);
    },

    getGridColumnFilter: function (field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayoutEdit: function () {

        var labelWidth = 100;
        var width = this.fieldConfig.width ? this.fieldConfig.width : 400;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }
        width += labelWidth;


        this.selector = new Ext.ux.colorpick.Selector(
            {
                showPreviousColor: true,
                hidden: true,
                bind: {
                    value: '{color}',
                    visible: '{full}'
                }
            }
        );

        var colorConfig =  {
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            format: '#hex8',
            isNull: !this.data,
            hidden: true,
            bind: '{color}'
        };

        if (this.data) {
            colorConfig["value"] = this.data;
        }

        this.colorField = Ext.create('pimcore.colorpick.Field',
            colorConfig
        );

        var panel = new Ext.panel.Panel({
            viewModel: {
                data: {
                    color: this.data ? this.data : "FFFFFFFF"
                }
            },
            layout: 'hbox',
            width: width,
            componentCls: "object_field object_field_type_" + this.type,
            items: [this.colorField, this.selector,
                {
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                style: "margin-left: 5px",
                handler: this.empty.bind(this),
            }],
            style: "padding-bottom: 10px;"
        });

        this.colorField.setVisible(true);
        this.component = panel;
        return this.component;
    },

    empty: function () {
        this.colorField.setIsNull(true);
        this.component.getViewModel().set('color', "FFFFFFFF");
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        var viewModel = this.component.getViewModel();
        var isNull = this.colorField.getIsNull();
        if (isNull) {
            return null;
        }
        var value = viewModel.get("color");
        return value;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        var dirty = this.getValue() != this.data

        return dirty;
    }
});
