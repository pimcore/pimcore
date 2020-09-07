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

pimcore.registerNS("pimcore.document.editables.checkbox");
pimcore.document.editables.checkbox = Class.create(pimcore.document.editable, {


    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = false;
        }

        this.htmlId = id + "_editable";

        var elContainer = Ext.get(id);

        var inputCheckbox = document.createElement("input");
        inputCheckbox.setAttribute('name', this.htmlId);
        inputCheckbox.setAttribute('type', 'checkbox');
        inputCheckbox.setAttribute('value', 'true');
        inputCheckbox.setAttribute('id', this.htmlId);
        if(data) {
            inputCheckbox.setAttribute('checked', 'checked');
        }

        elContainer.appendChild(inputCheckbox);

        if(options["label"]) {
            var labelCheckbox = document.createElement("label");
            labelCheckbox.setAttribute('for', this.htmlId);
            labelCheckbox.innerText = options["label"];
            elContainer.appendChild(labelCheckbox);
        }

        this.elComponent = Ext.get(this.htmlId);

        // onchange event
        if (options.onchange) {
            this.elComponent.on('change', eval(options.onchange));
        }
        if (options.reload) {
            this.elComponent.on('change', this.reloadDocument);
        }
    },

    getValue: function () {
        return this.elComponent.dom.checked;
    },

    getType: function () {
        return "checkbox";
    }
});
