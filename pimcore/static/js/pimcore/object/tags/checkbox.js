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

pimcore.registerNS("pimcore.object.tags.checkbox");
pimcore.object.tags.checkbox = Class.create(pimcore.object.tags.abstract, {

    type: "checkbox",

    initialize: function (data, layoutConf) {

        this.data = "";

        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;
    },

    getLayoutEdit: function () {

        var checkbox = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
            itemCls: "object_field"
        };


        if (this.layoutConf.width) {
            checkbox.width = this.layoutConf.width;
        }

        this.layout = new Ext.form.Checkbox(checkbox);

        this.layout.setValue(this.data);

        return this.layout;
    },


    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        return this.layout.getValue();
    },

    getName: function () {
        return this.layoutConf.name;
    },

    isInvalidMandatory: function () {
        return false;
    }
});