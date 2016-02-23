/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.document.tags.area");
pimcore.document.tags.area = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.elements = [];
        this.options = this.parseOptions(options);

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
            } else {
                console.log(e);
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

        this.editmodeWindow = new Ext.Window({
            modal: true,
            width: 550,
            height: 370,
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
                icon: "/pimcore/static6/img/icon/tick.png"
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