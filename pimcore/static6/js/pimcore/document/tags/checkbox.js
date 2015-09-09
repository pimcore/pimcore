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

        $("#" + id).html('<input name="' + this.htmlId + '" type="checkbox" value="true" id="' + this.htmlId + '" ' + checked + ' />');

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
