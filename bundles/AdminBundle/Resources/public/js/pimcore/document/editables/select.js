/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.document.editables.select");
pimcore.document.editables.select = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;

        config = this.parseConfig(config);

        config.listeners = {};

        // onchange event
        if (config.onchange) {
            config.listeners.select = eval(config.onchange);
        }

        if (config["reload"]) {
            config.listeners.select = this.reloadDocument;
        }

        if(typeof config["defaultValue"] !== "undefined" && data === null) {
            data = config["defaultValue"];
        }

        config.name = id + "_editable";
        config.triggerAction = 'all';
        config.editable = false;
        config.value = data;

        this.config = config;
    },

    render: function() {
        this.setupWrapper();

        if (this.config["required"]) {
            this.required = this.config["required"];
        }

        this.element = new Ext.form.ComboBox(this.config);
        this.element.render(this.id);

        this.element.on("select", this.checkValue.bind(this, true));
        this.checkValue();
    },

    checkValue: function (mark) {
        var value = this.getValue();

        if (this.required) {
            this.validateRequiredValue(value, this.element, this, mark);
        }
    },

    getValue: function () {
        if(this.element) {
            return this.element.getValue();
        }

        return this.config.value;
    },

    getType: function () {
        return "select";
    }
});
