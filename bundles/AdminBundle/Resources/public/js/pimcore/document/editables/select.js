/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.editables.select");
pimcore.document.editables.select = Class.create(pimcore.document.editable, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;

        options = this.parseOptions(options);

        options.listeners = {};

        // onchange event
        if (options.onchange) {
            options.listeners.select = eval(options.onchange);
        }

        if (options["reload"]) {
            options.listeners.select = this.reloadDocument;
        }

        if(options["defaultValue"] && data === null) {
            data = options["defaultValue"];
        }

        options.name = id + "_editable";
        options.triggerAction = 'all';
        options.editable = false;
        options.value = data;

        this.options = options;
    },

    render: function() {
        this.setupWrapper();
        this.element = new Ext.form.ComboBox(this.options);
        this.element.render(this.id);
    },

    getValue: function () {
        if(this.element) {
            return this.element.getValue();
        }

        return this.options.value;
    },

    getType: function () {
        return "select";
    }
});