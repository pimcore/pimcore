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

pimcore.registerNS("pimcore.document.editables.area");
pimcore.document.editables.area = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {

        this.id = id;
        this.name = name;
        this.elements = [];
        this.config = this.parseConfig(config);

        // edit button
        try {
            var editDiv = Ext.get(id).query(".pimcore_area_edit_button")[0];
            var editButton = new Ext.Button({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_edit",
                handler: this.editmodeOpen.bind(this, Ext.get(id))
            });
            if (editDiv) {
                editButton.render(editDiv);
            }
        } catch (e) {
            console.log(e);
        }

    },

    setInherited: function ($super, inherited) {
        // disable masking for this datatype (overwrite), because it's actually not needed, otherwise call $super()
        this.inherited = inherited;
    },

    editmodeOpen: function (element) {

        var content = Ext.get(element).down(".pimcore_area_editmode");

        if( content === null && element.getAttribute('data-editmmode-button-ref') !== null)
        {
            content = Ext.getBody().down( '#' + element.getAttribute('data-editmmode-button-ref' ) );
        }

        var editmodeWindowWidth = 550;
        var editmodeWindowHeight = 370;

        if(this.config["params"] && this.config.type) {
            if (this.config.params[this.config.type] && this.config.params[this.config.type]["editWidth"]) {
                editmodeWindowWidth = this.config.params[this.config.type].editWidth;
            }

            if (this.config.params[this.config.type] && this.config.params[this.config.type]["editHeight"]) {
                editmodeWindowHeight = this.config.params.editHeight;
            }
        }

        this.editmodeWindow = new Ext.Window({
            modal: true,
            width: editmodeWindowWidth,
            height: editmodeWindowHeight,
            title: "Edit Block",
            closeAction: "hide",
            bodyStyle: "padding: 10px;",
            closable: false,
            autoScroll: true,
            listeners: {
                afterrender: function (win) {

                    content.removeCls("pimcore_area_editmode_hidden");
                    win.body.down(".x-autocontainer-innerCt").insertFirst(content);

                    var elements = win.body.query(".pimcore_editable");
                    for (var i=0; i<elements.length; i++) {
                        var name = elements[i].getAttribute("id").split("pimcore_editable_").join("");
                        for (var e=0; e<editables.length; e++) {
                            if(editables[e].getName() == name) {
                                if(editables[e].element) {
                                    if(typeof editables[e].element.doLayout == "function") {
                                        editables[e].element.updateLayout();
                                    }
                                }
                                break;
                            }
                        }
                    }

                }.bind(this)
            },
            buttons: [{
                text: t("save"),
                listeners: {
                    "click": this.editmodeSave.bind(this)
                },
                iconCls: "pimcore_icon_save"
            },{
                text: t("cancel"),
                handler: function() {
                    content.addCls("pimcore_area_editmode_hidden");
                    element.dom.setAttribute('data-editmmode-button-ref', content.getAttribute("id") );
                    this.editmodeWindow.close();
                }.bind(this),
                iconCls: "pimcore_icon_cancel"
            }]
        });
        this.editmodeWindow.show();
    },

    editmodeSave: function () {
        this.editmodeWindow.close();

        this.reloadDocument();
    },

    getValue: function () {
        var data = [];
        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i]) {
                if (this.elements[i].key) {
                    data.push({
                        key: this.elements[i].key,
                        type: this.elements[i].type
                    });
                }
            }
        }

        return data;
    },

    getType: function () {
        return "area";
    }
});