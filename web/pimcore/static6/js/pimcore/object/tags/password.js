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

pimcore.registerNS("pimcore.object.tags.password");
pimcore.object.tags.password = Class.create(pimcore.object.tags.abstract, {

    type: "password",

    initialize: function (data, fieldConfig) {

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;

    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            componentCls: "object_field"
        };

        input.value = "********";

        if (intval(this.fieldConfig.width) > 1) {
            input.width = this.fieldConfig.width;
        } else {
            input.width = 350;
        }

        input.maxLength = 30;
        input.inputType = "password";

        this.component = new Ext.form.TextField(input);

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        if(this.component.isDirty()) {
            return this.component.getValue();
        }
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});