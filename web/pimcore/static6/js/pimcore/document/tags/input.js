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

pimcore.registerNS("pimcore.document.tags.input");
pimcore.document.tags.input = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = "";
        }

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

        this.element.update(data + "<br>");

        this.checkValue();

        this.element.on("keyup", this.checkValue.bind(this));
        this.element.on("keydown", function (e, t, o) {
            // do not allow certain keys, like enter, ...
            if(in_array(e.getCharCode(), [13])) {
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

            text = this.clearText(text);
            text = htmlentities(text, "ENT_NOQUOTES", null, false);

            try {
                pimcore.edithelpers.pasteHtmlAtCaret(text);
            } catch (e) {
                console.log(e);
            }
        }.bind(this));

        if(options["width"]) {
            this.element.applyStyles({
                display: "inline-block",
                width: options["width"] + "px",
                overflow: "auto",
                "white-space": "nowrap"
            });
        }
        if(options["nowrap"]) {
            this.element.applyStyles({
                "white-space": "nowrap",
                overflow: "auto"
            });
        }
        if (options["placeholder"]) {
            this.element.dom.setAttribute('data-placeholder', options["placeholder"]);
        }

        if(options["validator"]) {
            this.element.isValid = options["validator"];
            this.validateElement();
        }
    },

    checkValue: function () {
        var value = trim(this.element.dom.innerHTML);
        var origValue = value;

        var textLength = trim(strip_tags(value)).length;

        if(textLength < 1) {
            this.element.addCls("empty");
            value = ""; // set to "" since it can contain an <br> at the end
        } else {
            this.element.removeCls("empty");
        }

        if(value != origValue) {
            this.element.update(this.getValue());
        }

        this.validateElement(value);
    },

    getValue: function () {
        var text = "";
        if(typeof this.element.dom.textContent != "undefined") {
            text = this.element.dom.textContent;
        } else {
            text = this.element.dom.innerText;
        }

        text = this.clearText(text);
        return text;
    },

    clearText: function (text) {
        text = str_replace("\r\n", " ", text);
        text = str_replace("\n", " ", text);
        return text;
    },

    getType: function () {
        return "input";
    },

    setInherited: function($super, inherited, el) {

        $super(inherited, el);

        if(this.inherited) {
            this.element.dom.setAttribute("contenteditable", false);
        } else {
            this.element.dom.setAttribute("contenteditable", true);
        }
    },

    /**
     *
     * validation for dedicated validator which could be added in an element view helper as the validator option
     *
     *
     * @returns {pimcore.document.tags.input}
     */
    validateElement: function(value){

        if(this.element.isValid && typeof this.element.isValid == 'function') {

            value = !value ? this.element.dom.innerText : value;

            var validatorMessage = this.element.isValid(strip_tags(value));
            if(true !== validatorMessage) {
                this.element.addCls('invalid-document-element');
                this.element.setStyle('border', '1px solid #f40204');
            } else {
                this.element.removeCls('invalid-document-element');
                this.element.setStyle('border', '');
            }
        }

        return this;
    }
});
