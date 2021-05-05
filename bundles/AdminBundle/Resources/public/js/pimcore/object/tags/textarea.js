/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.tags.textarea");
pimcore.object.tags.textarea = Class.create(pimcore.object.tags.abstract, {

    type: "textarea",

    initialize: function (data, fieldConfig) {
        this.data = data;
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
        // TEXTAREA
        if (field.type == "textarea") {
           return new Ext.form.TextArea(editorConfig);
        }
    },

    getGridColumnFilter: function(field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayoutEdit: function () {
        if (!this.fieldConfig.width) {
            this.fieldConfig.width = 250;
        }
        if (!this.fieldConfig.height) {
            this.fieldConfig.height = 250;
        }

        var labelWidth = this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100;

        var conf = {
            name: this.fieldConfig.name,
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth
        };

        if (!this.fieldConfig.showCharCount) {
            conf.componentCls = "object_field object_field_type_" + this.type;
        }

        if (this.fieldConfig.labelAlign) {
            conf.labelAlign = this.fieldConfig.labelAlign;
        }

        if (!this.fieldConfig.labelAlign || 'left' === this.fieldConfig.labelAlign) {
            conf.width = this.sumWidths(conf.width, conf.labelWidth);
        }

        if (this.data) {
            conf.value = this.data;
        }
        if(this.fieldConfig.maxLength) {
            conf.maxLength = this.fieldConfig.maxLength;
            conf.enforceMaxLength = true;
        }

        this.component = new Ext.form.TextArea(conf);

        if(this.fieldConfig.showCharCount) {
            var charCount = Ext.create("Ext.Panel", {
                bodyStyle: '',
                margin: '0 0 0 0',
                bodyCls: 'char_count',
                width: conf.width,
                height: 17
            });

            this.component.setStyle("margin-bottom", "0");
            this.component.addListener("change", function(charCount) {
                this.updateCharCount(this.component, charCount);
            }.bind(this, charCount));

            //init word count
            this.updateCharCount(this.component, charCount);

            return Ext.create("Ext.Panel", {
                cls: "object_field object_field_type_" + this.type,
                style: "margin-bottom: 10px",
                layout: {
                    type: 'vbox',
                    align: 'left'
                },
                items: [
                    this.component,
                    charCount
                ]
            });

        } else {
            return this.component;
        }
    },

    updateCharCount: function(textField, charCount) {
        if( this.fieldConfig.maxLength) {
            charCount.setHtml(textField.getValue().length + "/" + this.fieldConfig.maxLength);
        } else {
            charCount.setHtml(textField.getValue().length);
        }
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
    }
});
