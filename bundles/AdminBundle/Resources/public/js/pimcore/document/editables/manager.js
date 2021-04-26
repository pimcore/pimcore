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
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.editables.manager");
pimcore.document.editables.manager = Class.create({

    editables: {},
    requiredEditables:{},
    initialized: false,

    addByDefinition: function (definition) {
        let type = definition.type
        let name = definition.name;
        let inherited = false;
        if(typeof definition["inherited"] != "undefined") {
            inherited = definition["inherited"];
        }

        let EditableClass = pimcore.document.editables[type];

        if (typeof EditableClass !== 'function') {
            throw 'Editable of type `' + type + '` with name `' + name + '` could not be found.';
        }

        if (definition.inDialogBox && typeof EditableClass.prototype['render'] !== 'function') {
            throw 'Editable of type `' + type + '` with name `' + name + '` does not support the use in the dialog box.';
        }

        let editable = new EditableClass(definition.id, name, definition.config, definition.data, inherited);
        editable.setRealName(definition.realName);
        editable.setInDialogBox(definition.inDialogBox);

        if (!definition.inDialogBox) {
            if (typeof editable['render'] === 'function') {
                editable.render();
            }
            editable.setInherited(inherited);
        }

        this.editables[definition['name']] = editable;
        if (definition['config']['required']) {
            this.requiredEditables[definition['name']] = editable;
        }
    },

    add: function(editable) {
        this.editables[editable.getName()] = editable;
    },

    remove: function(name) {
        delete this.editables[name];
        delete this.requiredEditables[name];
    },

    getEditables: function() {
        return this.editables;
    },

    getRequiredEditables: function() {
        return this.requiredEditables;
    },

    setInitialized: function(state) {
        this.initialized = state;
    },

    isInitialized: function() {
        return this.initialized;
    }
});
