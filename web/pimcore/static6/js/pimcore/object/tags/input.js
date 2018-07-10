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

pimcore.registerNS("pimcore.object.tags.input");
pimcore.object.tags.input = Class.create(pimcore.object.tags.abstract, {

    type: "input",

    initialize: function (data, fieldConfig) {

        this.data = "";

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
        pimcore.eventDispatcher.registerTarget(null, this);
    },

    postSaveObject: function(obj) {
        if (obj.id !== this.object.id) {
            return;
        }

        var data = this.object.data.data;
        if (this.context.containerType !== 'localizedfield' && data[this.fieldConfig.name] !== undefined) {
            this.data = data[this.fieldConfig.name];
            this.component.setValue(this.data);
            return;
        }

        if (this.context.containerType === 'localizedfield'
            && data.localizedfields.data[this.context.language][this.fieldConfig.name] !== undefined
        ) {
            this.data = data.localizedfields.data[this.context.language][this.fieldConfig.name];
            this.component.setValue(this.data);
        }
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

        if(this.fieldConfig["regex"]) {
            input.regex = new RegExp(this.fieldConfig.regex);
        }

        this.component = new Ext.form.TextField(input);

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});
