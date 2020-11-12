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


    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);

        if (!data) {
            data = false;
        }

        this.data = data;
    },

    render: function () {
        this.setupWrapper();
        this.htmlId = this.id + "_editable";

        var elContainer = Ext.get(this.id);

        var inputCheckbox = document.createElement("input");
        inputCheckbox.setAttribute('name', this.htmlId);
        inputCheckbox.setAttribute('type', 'checkbox');
        inputCheckbox.setAttribute('value', 'true');
        inputCheckbox.setAttribute('id', this.htmlId);
        if(this.data) {
            inputCheckbox.setAttribute('checked', 'checked');
        }

        elContainer.appendChild(inputCheckbox);

        if(this.config["label"]) {
            var labelCheckbox = document.createElement("label");
            labelCheckbox.setAttribute('for', this.htmlId);
            labelCheckbox.innerText = this.config["label"];
            elContainer.appendChild(labelCheckbox);
        }

        this.elComponent = Ext.get(this.htmlId);

        // onchange event
        if (this.config.onchange) {
            this.elComponent.on('change', eval(this.config.onchange));
        }
        if (this.config.reload) {
            this.elComponent.on('change', this.reloadDocument);
        }
    },

    renderInDialogBox: function () {

        if(this.config['dialogBoxConfig'] &&
            (this.config['dialogBoxConfig']['label'] || this.config['dialogBoxConfig']['name'])) {
            this.config["label"] = this.config['dialogBoxConfig']['label'] ?? this.config['dialogBoxConfig']['name'];
        }

        this.render();
    },

    getValue: function () {
        if(this.elComponent) {
            return this.elComponent.dom.checked;
        }

        return this.data;
    },

    getType: function () {
        return "checkbox";
    }
});
