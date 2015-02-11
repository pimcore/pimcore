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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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
            itemCls: "object_field"
        };

        input.value = "********";

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
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