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
/**
 * @private
 */
pimcore.document.editables.select = Class.create(pimcore.document.editable, {

    initialize: function($super, id, name, config, data, inherited) {
        $super(id, name, config, data, inherited);

        this.config.listeners = {};

        // onchange event
        if (this.config.onchange) {
            this.config.listeners.select = eval(config.onchange);
        }

        if (this.config["reload"]) {
            this.config.listeners.select = this.reloadDocument;
        }

        if(typeof this.config["defaultValue"] !== "undefined" && data === null) {
            data = this.config["defaultValue"];
        }

        this.config.name = id + "_editable";
        this.config.triggerAction = 'all';
        this.config.editable = config.editable ? config.editable : false;
        this.config.value = data;
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
