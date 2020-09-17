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

pimcore.registerNS("pimcore.document.editables.date");
pimcore.document.editables.date = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {

        this.id = id;
        this.name = name;
        this.setupWrapper();
        config = this.parseConfig(config);

        if (config.format) {
            // replace any % prefixed parts from strftime format
            config.format = config.format.replace(/%([a-zA-Z])/g, '$1');
        }

        if (data) {
            var tmpDate = new Date(intval(data) * 1000);
            config.value = tmpDate;
        }

        config.name = id + "_editable";



        this.element = new Ext.form.DateField(config);
        if (config["reload"]) {
            this.element.on("change", this.reloadDocument);
        }

        this.element.render(id);
    },

    getValue: function () {
        return this.element.getValue();
    },

    getType: function () {
        return "date";
    }
});
