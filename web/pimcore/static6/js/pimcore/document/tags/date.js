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

pimcore.registerNS("pimcore.document.tags.date");
pimcore.document.tags.date = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (data) {
            var tmpDate = new Date(intval(data) * 1000);
            options.value = tmpDate;
        }

        options.name = id + "_editable";



        this.element = new Ext.form.DateField(options);
        if (options["reload"]) {
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