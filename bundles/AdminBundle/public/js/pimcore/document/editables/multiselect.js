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

pimcore.registerNS("pimcore.document.editables.multiselect");
/**
 * @private
 */
pimcore.document.editables.multiselect = Class.create(pimcore.document.editable, {

    initialize: function($super, id, name, config, data, inherited) {
        $super(id, name, config, data, inherited);

        this.data = data;

        this.config.name = id + "_editable";
        if(data) {
            this.config.value = data;
        }
        this.config.valueField = "id";

        this.config.listeners = {};

        if (this.config["reload"]) {
            this.config.listeners.change = this.reloadDocument;
        }

        if (typeof this.config.store !== "undefined") {
            this.config.store = Ext.create('Ext.data.ArrayStore', {
                fields: ['id', 'text'],
                data: this.config.store
            });
        }
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