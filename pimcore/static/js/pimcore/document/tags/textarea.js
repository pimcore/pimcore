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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.document.tags.textarea");
pimcore.document.tags.textarea = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.setupWrapper();
        if (!options) {
            options = {};
        }
        if (!data) {
            data = "";
        }

        options.value = data;
        options.name = id + "_editable";

        if(!options.width) {
            options.width = Ext.get(id).getWidth()-2;
        }

        this.element = new Ext.form.TextArea(options);
        this.element.render(id);

        if(options["autoStyle"] !== false) {
            var styles = Ext.get(id).parent().getStyles("font-size","font-family","font-style","font-weight","font-stretch","font-variant","color","line-height","text-shadow","text-align","text-decoration","text-transform","direction");
            styles["background"] = "none";
            if(!options["height"]) {
                styles["height"] = "auto";
            }
            this.element.getEl().applyStyles(styles);

            // necessary for IE9
            window.setTimeout(function () {
                this.element.getEl().repaint();
            }.bind(this), 300);
        }
    },

    getValue: function () {
        return this.element.getValue();
    },

    getType: function () {
        return "textarea";
    }
});