/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.document.tags.multiselect");
pimcore.document.tags.multiselect = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;

        this.setupWrapper();

        options = this.parseOptions(options);
        options.name = id + "_editable";
        options.value = data;

        options.listeners = {};
        // onchange event
        if (options.onchange) {
            options.listeners.change = eval(options.onchange);
        }

        if (options["reload"]) {
            options.listeners.change = this.reloadDocument;
        }

        this.element = new Ext.ux.form.MultiSelect(options);

        this.element.render(id);
    },

    getValue: function () {
        return this.element.getValue();
    },

    getType: function () {
        return "multiselect";
    }
});