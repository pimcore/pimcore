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

pimcore.registerNS("pimcore.object.tags.calculatedValue");
pimcore.object.tags.calculatedValue = Class.create(pimcore.object.tags.abstract, {

    type: "calculatedValue",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },


    getLayoutEdit: function () {

        var input = {
            fieldLabel: '<img src="/pimcore/static6/img/flat-color-icons/calculator.svg" style="height: 1.8em; display: inline-block; vertical-align: middle;"/>' + this.fieldConfig.title,
            componentCls: "object_field",
            labelWidth: 100,
            disabled: true
        };

        if (this.data) {
            input.value = this.data.value;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        if (this.fieldConfig.labelWidth) {
            input.labelWidth = this.fieldConfig.labelWidth;
        }

        input.width += input.labelWidth;


        if (this.data) {
            input.value = this.data;
        }


        this.component = new Ext.form.field.Text(input);

        return this.component;
    },


    getLayoutShow: function () {

        this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        return true;
    }
});