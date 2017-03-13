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
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = false;
        }

        this.htmlId = id + "_editable";
        var checked = "";
        if(data) {
            checked = ' checked="checked"';
        }

        $("#" + id).append('<input name="' + this.htmlId + '" type="checkbox" value="true" id="' + this.htmlId + '" ' + checked + ' />');

        if(options["label"]) {
            $("#" + id).append('<label for="' + this.htmlId + '">' + options["label"] + '</label>');
        }

        // onchange event
        if (options.onchange) {
            $("#" + this.htmlId).change(eval(options.onchange));
        }
        if (options.reload) {
            $("#" + this.htmlId).change(this.reloadDocument);
        }
    },

    getValue: function () {
        return ($("#" + this.htmlId + ":checked").val() == "true") ? true : false;
    },

    getType: function () {
        return "checkbox";
    }
});
