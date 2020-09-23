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

pimcore.registerNS("pimcore.document.editables.input");
pimcore.document.editables.input = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);

        if (!data) {
            data = "";
        }

        this.data = data;
    },

    render: function() {
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

        this.element.update(this.data + "<br>");

        if(this.config["required"]) {
            this.required = this.config["required"];
        }

        this.checkValue();

        this.element.on("keyup", this.checkValue.bind(this, true));
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
            text = trim(text);

            try {
                pimcore.edithelpers.pasteHtmlAtCaret(text);
            } catch (e) {
                console.log(e);
            }
        }.bind(this));

        if(this.config["width"]) {
            this.element.applyStyles({
                display: "inline-block",
                width: this.config["width"] + "px",
                overflow: "auto",
                "white-space": "nowrap"
            });
        }
        if(this.config["nowrap"]) {
            this.element.applyStyles({
                "white-space": "nowrap",
                overflow: "auto"
            });
        }
        if (this.config["placeholder"]) {
            this.element.dom.setAttribute('data-placeholder', this.config["placeholder"]);
        }

        // @TODO validator is based on \Zend\Json\Expr and does not work with Twig templates, to be removed in v7.0
        if(this.config["validator"]) {
            this.element.isValid = this.config["validator"];
            this.validateElement();
        }
    },

    checkValue: function (mark) {
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

        if (this.required) {
            this.validateRequiredValue(value, this.element, this, mark);
        }

        this.validateElement(value);
    },

    getValue: function () {

        if(!this.element) {
            return this.data;
        }

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
     * @returns {pimcore.document.editables.input}
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
