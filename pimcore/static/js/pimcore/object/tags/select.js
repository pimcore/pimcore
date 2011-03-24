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

pimcore.registerNS("pimcore.object.tags.select");
pimcore.object.tags.select = Class.create(pimcore.object.tags.abstract, {

    type: "select",

    initialize: function (data, layoutConf) {
        this.data = data;
        this.layoutConf = layoutConf;

    },

    getLayoutEdit: function () {

        // generate store
        var store = [];
        var validValues = [];

        if(!this.layoutConf.mandatory) {
            store.push(["","(" + t("empty") + ")"]);
        }

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
            itemCls: "object_field",
            width: 300
        };

        if (this.layoutConf.width) {
            options.width = this.layoutConf.width;
        }

        if (typeof this.data == "string" || typeof this.data == "number") {
            if (in_array(this.data, validValues)) {
                options.value = this.data;
            } else {
                options.value = "";
            }
        } else {
            options.value = "";
        }

        this.layout = new Ext.form.ComboBox(options);

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