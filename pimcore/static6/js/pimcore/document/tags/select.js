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

pimcore.registerNS("pimcore.document.tags.select");
pimcore.document.tags.select = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;

        this.setupWrapper();
        options = this.parseOptions(options);

        options.listeners = {};

        // onchange event
        if (options.onchange) {
            options.listeners.select = eval(options.onchange);
        }

        if (options["reload"]) {
            options.listeners.select = this.reloadDocument;
        }

        options.name = id + "_editable";
        options.triggerAction = 'all';
        options.editable = false;
        options.value = data;

        this.element = new Ext.form.ComboBox(options);
        this.element.render(id);
    },

    getValue: function () {
        return this.element.getValue();
    },

    getType: function () {
        return "select";
    }
});