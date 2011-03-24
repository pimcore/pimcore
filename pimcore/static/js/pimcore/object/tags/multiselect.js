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

pimcore.registerNS("pimcore.object.tags.multiselect");
pimcore.object.tags.multiselect = Class.create(pimcore.object.tags.abstract, {

    type: "multiselect",

    initialize: function (data, layoutConf) {
        this.data = data;
        this.layoutConf = layoutConf;

    },

    getLayoutEdit: function () {

        // generate store
        var store = [];
        var validValues = [];
        for (var i = 0; i < this.layoutConf.options.length; i++) {
            store.push([this.layoutConf.options[i].value, this.layoutConf.options[i].key]);
            validValues.push(this.layoutConf.options[i].value);
        }

        var options = {
            name: this.layoutConf.name,
            triggerAction: "all",
            editable: false,
            fieldLabel: this.layoutConf.title,
            store: store,
            itemCls: "object_field"
        };

        if (this.layoutConf.width) {
            options.width = this.layoutConf.width;
        }
        if (this.layoutConf.height) {
            options.height = this.layoutConf.height;
        }

        if (typeof this.data == "string" || typeof this.data == "number") {
            options.value = this.data;
        }

        this.layout = new Ext.ux.form.MultiSelect(options);

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
    }
});