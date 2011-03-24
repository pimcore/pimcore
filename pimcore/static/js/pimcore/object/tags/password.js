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

pimcore.registerNS("pimcore.object.tags.password");
pimcore.object.tags.password = Class.create(pimcore.object.tags.abstract, {

    type: "password",

    initialize: function (data, layoutConf) {

        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;

    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
            itemCls: "object_field"
        };

        input.value = "********";

        if (this.layoutConf.width) {
            input.width = this.layoutConf.width;
        }

        input.maxLength = 30;
        input.inputType = "password";

        this.layout = new Ext.form.TextField(input);

        return this.layout;
    },


    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        if(this.layout.isDirty()) {
            return this.layout.getValue();
        }
        return this.data;
    },

    getName: function () {
        return this.layoutConf.name;
    }
});