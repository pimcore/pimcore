/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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