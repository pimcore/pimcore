/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.document.editables.wysiwyg");
/**
 * @private
 */
pimcore.document.editables.wysiwyg = Class.create(pimcore.document.editable, {

    type: "wysiwyg",

    initialize: function($super, id, name, config, data, inherited) {
        $super(id, name, config, data, inherited);

        if (!data) {
            data = "";
        }
        this.data = data ?? "";

        if (config["required"]) {
            this.required = config["required"];
        }
    },

    render: function () {
        this.setupWrapper();

        this.textarea = document.createElement("div");
        this.textarea.setAttribute("contenteditable","true");

        Ext.get(this.id).appendChild(this.textarea);
        Ext.get(this.id).insertHtml("beforeEnd",'<div class="pimcore_editable_droptarget"></div>');

        this.textarea.id = this.id + "_textarea";
        this.textarea.innerHTML = this.data;

        let textareaHeight = 100;
        if (this.config.height) {
            textareaHeight = this.config.height;
        }
        if (this.config.placeholder) {
            this.textarea.setAttribute('data-placeholder', this.config["placeholder"]);
        }

        let inactiveContainerWidth = this.config.width + "px";
        if (typeof this.config.width == "string" && this.config.width.indexOf("%") >= 0) {
            inactiveContainerWidth = this.config.width;
        }

        Ext.get(this.textarea).addCls("pimcore_wysiwyg");
        Ext.get(this.textarea).applyStyles("width: " + inactiveContainerWidth  + "; min-height: " + textareaHeight
            + "px;");

        if(this.startWysiwygEditor()) {
            // register at global DnD manager
            if (typeof dndManager !== 'undefined') {
                dndManager.addDropTarget(Ext.get(this.id), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
            }
        }
    },

    startWysiwygEditor: function () {

        const initializeWysiwyg = new CustomEvent(pimcore.events.initializeWysiwyg, {
            detail: {
                config: Object.assign({}, this.config),
                context: "document"
            },
            cancelable: true
        });
        const initIsAllowed = document.dispatchEvent(initializeWysiwyg);
        if(!initIsAllowed) {
            return false;
        }

        const createWysiwyg = new CustomEvent(pimcore.events.createWysiwyg, {
            detail: {
                textarea: this.textarea,
                context: "document",
            },
            cancelable: true
        });
        const createIsAllowed = document.dispatchEvent(createWysiwyg);
        if(!createIsAllowed) {
            return false;
        }

        document.addEventListener(pimcore.events.changeWysiwyg, function (e) {
            if(`${this.id}_textarea` === e.detail.e.target.id) {
                this.setValue(e.detail.data);
            }
        }.bind(this));

        if (!parent.pimcore.wysiwyg.editors.length) {
           this.textarea.oninput = (e) => {
               this.setValue(e.target.innerHTML);
           };
        }

        return true;
    },

    onNodeDrop: function (target, dd, e, data) {
        if (!pimcore.helpers.dragAndDropValidateSingleItem(data) || !this.dndAllowed(data.records[0].data) || this.inherited) {
            return false;
        }

        const onDropWysiwyg = new CustomEvent(pimcore.events.onDropWysiwyg, {
            detail: {
                target: target,
                dd: dd,
                e: e,
                data: data,
                context: "document",
            }
        });

        document.dispatchEvent(onDropWysiwyg);
    },

    checkValue: function (mark) {
        const value = this.getValue();
        let textarea = Ext.get(this.textarea);

        // Sync DOM class names with ExtJs (wysiwyg-editor may have added classes in the meantime)
        textarea.setCls(textarea.dom.className);

        if (trim(strip_tags(value)).length < 1) {
            textarea.addCls("empty");
        } else {
            textarea.removeCls("empty");
        }

        if (this.required) {
            this.validateRequiredValue(value, textarea, this, mark);
        }
    },

    onNodeOver: function (target, dd, e, data) {
        if (data.records.length === 1 && this.dndAllowed(data.records[0].data) && !this.inherited) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        } else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },


    dndAllowed: function (data) {

        if (data.elementType == "document" && (data.type == "page"
            || data.type == "hardlink" || data.type == "link")) {
            return true;
        } else if (data.elementType == "asset" && data.type != "folder") {
            return true;
        } else if (data.elementType == "object" && data.type != "folder") {
            return true;
        }

        return false;
    },


    getValue: function () {
        return this.data;
    },

    setValue: function (value) {
      this.data = value;
      this.checkValue(true);
    },

    getType: function () {
        return "wysiwyg";
    }
});

