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
pimcore.registerNS("pimcore.document.tags.checkbox");
pimcore.document.tags.checkbox = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.htmlId = this.id + "_editable";
        this.element = null;

        this.setupWrapper();

        var checked = false,
            domElement = Ext.query("#" + escapeSelector(id)),
            options = this.parseOptions(options);

        if (!data) {
            data = false;
        }

        if(data) {
            checked = true;
        }

        if(domElement.length === 0) {
            return false;
        }

        this.element = new Ext.form.field.Checkbox({
            boxLabel: options["label"] ? options["label"] : "",
            name: this.htmlId,
            id: this.htmlId,
            checked: checked,
            handler: function(sender, checked) {
                if (options.reload) {
                    this.reloadDocument();
                }
                if (options.onchange) {
                    //this is pure evil.
                    eval(options.onchange);
                }
            }.bind(this)
        });

        this.element.render(domElement);

    },

    getValue: function () {
        return this.element.getValue();
    },

    getType: function () {
        return "checkbox";
    }
});