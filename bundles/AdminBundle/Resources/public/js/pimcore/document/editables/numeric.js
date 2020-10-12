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

pimcore.registerNS("pimcore.document.editables.numeric");
pimcore.document.editables.numeric = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        config = this.parseConfig(config);

        if ('number' !== typeof data && !data) {
            data = "";
        }

        config.value = data;
        config.name = id + "_editable";
        config.decimalPrecision = 20;

        if(config["required"]) {
            this.required = config["required"];
        }

        this.config = config;
    },

    render: function () {
        this.setupWrapper();
        this.element = new Ext.form.field.Number(this.config);
        this.element.render(this.id);

        this.checkValue();
        this.element.on("blur", this.checkValue.bind(this, true));
    },

    getValue: function () {
        if(this.element) {
            return this.element.getValue();
        }

        return this.config.value;
    },

    getType: function () {
        return "numeric";
    },

    checkValue: function (mark) {
        var value = this.getValue();

        if(Number(value) < 1) {
            this.element.addCls("empty");
        } else {
            this.element.removeCls("empty");
        }

        if (this.required) {
            this.validateRequiredValue(value, this.element, this, mark);
        }
    }
});
