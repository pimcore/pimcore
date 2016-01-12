/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
            fieldLabel: '<img src="/pimcore/static6/img/icon/calculator.png"/>'  +this.fieldConfig.title,
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