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

pimcore.registerNS("pimcore.document.editables.link");
pimcore.document.editables.link = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {

        if (!data) {
            data = {};
        }

        this.defaultData = {
            type: "internal",
            path: "",
            parameters: "",
            anchor: "",
            accesskey: "",
            rel: "",
            tabindex: "",
            target: "",
            "class": "",
            attributes: ""
        };

        this.data = mergeObject(this.defaultData, data);

        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);
    },

    render: function() {
        this.setupWrapper();

        Ext.get(this.id).setStyle({
            display:"inline"
        });
        Ext.get(this.id).insertHtml("beforeEnd",'<span class="pimcore_tag_link_text pimcore_editable_link_text">' + this.getLinkContent() + '</span>');

        var editButton = new Ext.Button({
            iconCls: "pimcore_icon_link pimcore_icon_overlay_edit",
            cls: "pimcore_edit_link_button",
            listeners: {
                "click": this.openEditor.bind(this)
            }
        });

        var openButton = new Ext.Button({
            iconCls: "pimcore_icon_open",
            cls: "pimcore_open_link_button",
            listeners: {
                "click": function () {
                    if (this.data && this.data.path) {
                        if (this.data.linktype == "internal") {
                            pimcore.helpers.openElement(this.data.path, this.data.internalType);
                        } else {
                            window.open(this.data.path, "_blank");
                        }
                    }
                }.bind(this)
            }
        });

        openButton.render(this.id);
        editButton.render(this.id);
    },

    openEditor: function () {

        // disable the global dnd handler in this editmode/frame
        window.dndManager.disable();

        this.window = pimcore.helpers.editmode.openLinkEditPanel(this.data, {
            empty: this.empty.bind(this),
            cancel: this.cancel.bind(this),
            save: this.save.bind(this)
        });
    },


    getLinkContent: function () {

        var text = "[" + t("not_set") + "]";
        if (this.data.text) {
            text = this.data.text;
        } else if (this.data.path) {
            text = this.data.path;
        }
        if (this.data.path) {
            return '<a href="' + this.data.path + '" class="' + this.config["class"] + ' ' + this.data["class"] + '">' + text + '</a>';
        }
        return text;
    },

    save: function () {

        // enable the global dnd dropzone again
        window.dndManager.enable();

        var values = this.window.getComponent("form").getForm().getFieldValues();
        this.data = values;

        // close window
        this.window.close();

        // set text
        Ext.get(this.id).query(".pimcore_editable_link_text")[0].innerHTML = this.getLinkContent();

        this.reload();
    },

    reload : function () {
        if (this.config.reload) {
            this.reloadDocument();
        }
    },

    empty: function () {

        // enable the global dnd dropzone again
        window.dndManager.enable();

        // close window
        this.window.close();

        this.data = this.defaultData;

        // set text
        Ext.get(this.id).query(".pimcore_editable_link_text")[0].innerHTML = this.getLinkContent();
    },

    cancel: function () {

        // enable the global dnd dropzone again
        window.dndManager.enable();

        this.window.close();
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "link";
    }
});