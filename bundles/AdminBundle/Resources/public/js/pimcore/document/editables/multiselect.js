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

pimcore.registerNS("pimcore.document.editables.multiselect");
pimcore.document.editables.multiselect = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.data = data;

        config = this.parseConfig(config);
        config.name = id + "_editable";
        if(data) {
            config.value = data;
        }
        config.valueField = "id";

        config.listeners = {};
        // onchange event
        if (config.onchange) {
            config.listeners.change = eval(config.onchange);
        }

        if (config["reload"]) {
            config.listeners.change = this.reloadDocument;
        }

        if (typeof config.store !== "undefined") {
            config.store = Ext.create('Ext.data.ArrayStore', {
                fields: ['id', 'text'],
                data: config.store
            });
        }

        this.config = config;
    },

    render: function () {
        this.setupWrapper();
        this.element = Ext.create('Ext.ux.form.MultiSelect', this.config);
        this.element.render(this.id);
    },

    getValue: function () {
        if(this.element) {
            return this.element.getValue();
        }

        return this.data;
    },

    getType: function () {
        return "multiselect";
    }
});