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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.numeric");
pimcore.object.tags.numeric = Class.create(pimcore.object.tags.abstract, {

    type: "numeric",

    initialize: function (data, layoutConf) {

        this.data = data;
        this.layoutConf = layoutConf;
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
        // NUMERIC
        if (field.type == "numeric") {
            editorConfig.decimalPrecision = 20;
            return new Ext.ux.form.SpinnerField(editorConfig);
        }
    },

    getGridColumnFilter: function(field) {
        return {type: 'numeric', dataIndex: field.key};
    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
            itemCls: "object_field"
        };

        if (!isNaN(this.data)) {
            input.value = this.data;
        }

        if (this.layoutConf.width) {
            input.width = this.layoutConf.width;
        }

        input.decimalPrecision = 20;

        this.layout = new Ext.ux.form.SpinnerField(input);

        return this.layout;
    },


    getLayoutShow: function () {

        var input = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
            cls: "object_field"
        };

        if (this.data) {
            input.value = this.data;
        }

        if (this.layoutConf.width) {
            input.width = this.layoutConf.width;
        }

        this.layout = new Ext.form.TextField(input);
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        if(this.layout.rendered) {
            return this.layout.getValue().toString();
        }
        return null;
    },

    getName: function () {
        return this.layoutConf.name;
    },

    isInvalidMandatory: function () {
        if (this.getValue()) {
            return false;
        }
        return true;
    }
});