/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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

        // set min height for IE, as he isn't able to update :after css selector
        this.element.update("|"); // dummy content to get appropriate height
        if(this.element.getHeight()) {
            this.element.applyStyles({
                "min-height": this.element.getHeight() + "px"
            });
        } else {
            this.element.applyStyles({
                "min-height": this.element.getStyle("font-size")
            });
        }

        this.element.update(data);

        this.checkValue();

        this.element.on("keyup", this.checkValue.bind(this));
        this.element.on("keydown", function (e, t, o) {

            if(e.getCharCode() == 13) {

                if (window.getSelection) {
                    var selection = window.getSelection(),
                        range = selection.getRangeAt(0),
                        br = document.createElement("br"),
                        textNode = document.createTextNode("\u00a0"); //Passing " " directly will not end up being shown correctly
                    range.deleteContents();//required or not?
                    range.insertNode(br);
                    range.collapse(false);
                    range.insertNode(textNode);
                    range.selectNodeContents(textNode);

                    selection.removeAllRanges();
                    selection.addRange(range);
                }

                e.stopEvent();
            }
        });

        this.element.dom.addEventListener("paste", function(e) {
            e.preventDefault();

            var text = "";
            if(e.clipboardData) {
                text = e.clipboardData.getData("text/plain");
            } else if (window.clipboardData) {
                text = window.clipboardData.getData("Text");
            }

            text = htmlentities(text, 'ENT_NOQUOTES', null, false);
            text = str_replace(" ", "&nbsp;", text);

            try {
                pimcore.edithelpers.pasteHtmlAtCaret(text);
            } catch (e) {
                console.log(e);
            }
        }.bind(this));

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
        if (options["placeholder"]) {
            this.element.dom.setAttribute('data-placeholder', options["placeholder"]);
        }
    },

    checkValue: function () {
        var value = this.element.dom.innerHTML;

        if(trim(strip_tags(value)).length < 1) {
            this.element.addCls("empty");
        } else {
            this.element.removeCls("empty");
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
    },

    setInherited: function($super, inherited, el) {

        $super(inherited, el);

        if(this.inherited) {
            this.element.dom.setAttribute("contenteditable", false);
        } else {
            this.element.dom.setAttribute("contenteditable", true);
        }
    }
});
