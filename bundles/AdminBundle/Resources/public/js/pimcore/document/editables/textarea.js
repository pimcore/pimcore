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

pimcore.registerNS("pimcore.document.editables.textarea");
pimcore.document.editables.textarea = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);

        if (!data) {
            data = "";
        }

        this.data = str_replace("\n","<br>", data);

        if(this.config["required"]) {
            this.required = this.config["required"];
        }
    },

    render: function () {
        this.setupWrapper();
        this.element = Ext.get(this.id);
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

        this.element.update(this.data);

        this.checkValue();

        this.element.on("keyup", this.checkValue.bind(this, true));
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
            text = trim(text);

            try {
                pimcore.edithelpers.pasteHtmlAtCaret(text);
            } catch (e) {
                console.log(e);
            }
        }.bind(this));

        if(this.config["width"] || this.config["height"]) {
            this.element.applyStyles({
                display: "inline-block",
                overflow: "auto"
            });
        }
        if(this.config["width"]) {
            this.element.applyStyles({
                width: this.config["width"] + "px"
            })
        }
        if(this.config["height"]) {
            this.element.applyStyles({
                height: this.config["height"] + "px"
            })
        }
        if (this.config["placeholder"]) {
            this.element.dom.setAttribute('data-placeholder', this.config["placeholder"]);
        }
    },

    checkValue: function (mark) {
        var value = this.element.dom.innerHTML;

        if(trim(strip_tags(value)).length < 1) {
            this.element.addCls("empty");
        } else {
            this.element.removeCls("empty");
        }

        if (this.required) {
            this.validateRequiredValue(value, this.element, this, mark);
        }
    },

    getValue: function () {

        let value = this.data;
        if(this.element) {
            value = this.element.dom.innerHTML;
        }

        value = strip_tags(value, '<br>'); // strip out nasty HTML, eg. inserted by highlighting feature (ExtJS masks)
        value = value.replace(/<br>/g, "\n");
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
