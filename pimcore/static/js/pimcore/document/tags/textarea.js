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

pimcore.registerNS("pimcore.document.tags.textarea");
pimcore.document.tags.textarea = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = "";
        }

        data = str_replace("\n","<br>", data);

        this.element = Ext.get(id);
        this.element.dom.setAttribute("contenteditable", true);
        this.element.update(data);
        this.checkValue();

        this.element.on("keyup", this.checkValue.bind(this));
        this.element.on("keydown", function (e, t, o) {

            if(e.getCharCode() == 13) {
                var selection = window.getSelection(),
                    range = selection.getRangeAt(0),
                    br = document.createElement("br");
                range.deleteContents();
                range.insertNode(br);
                range.setStartAfter(br);
                range.setEndAfter(br);
                range.collapse(false);
                selection.removeAllRanges();
                selection.addRange(range);

                e.stopEvent();
            }
        });

        if(options["width"] || options["height"]) {
            this.element.applyStyles({
                display: "inline-block",
                overflow: "auto"
            });
        }
        if(options["width"]) {
            this.element.applyStyles({
                width: options["width"] + "px"
            })
        }
        if(options["height"]) {
            this.element.applyStyles({
                height: options["height"] + "px"
            })
        }
    },

    checkValue: function () {

        // ensure that the last node is always an <br>
        if (!this.element.dom.lastChild || this.element.dom.lastChild.nodeName.toLowerCase() != "br") {
            this.element.dom.appendChild(document.createElement("br"));
        }

        var value = this.element.dom.innerHTML;
        var origValue = value;
        value = strip_tags(value, "<br>");

        if(value != origValue) {
            this.element.update(value);
        }

        if(trim(strip_tags(value)).length < 1) {
            this.element.addClass("empty");
        } else {
            this.element.removeClass("empty");
        }
    },

    getValue: function () {
        var value = this.element.dom.innerHTML;
        value = value.replace(/<br>/g,"\n");
        value = trim(value);
        return value;
    },

    getType: function () {
        return "textarea";
    }
});
