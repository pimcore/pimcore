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

pimcore.registerNS("pimcore.document.tags.select");
pimcore.document.tags.select = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;

        this.setupWrapper();

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