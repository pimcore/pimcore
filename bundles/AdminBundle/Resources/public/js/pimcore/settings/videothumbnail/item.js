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
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.settings.videothumbnail.item");
pimcore.settings.videothumbnail.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentIndex = 0;
        this.medias = {};

        this.addLayout();

        // add default panel
        this.addMediaPanel("default", this.data.items, false, true);

        // add medias
        if (this.data["medias"]) {
            Ext.iterate(this.data.medias, function (key, items) {
                this.addMediaPanel(key, items, true, false);
            }.bind(this));
        }
    },


    addLayout: function () {
        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        });

        this.mediaPanel = new Ext.TabPanel({
            autoHeight: true,
            plugins: [Ext.create('Ext.ux.TabReorderer', {})]
        });

        var addViewPortButton = {
            xtype: 'panel',
            style: 'margin-bottom: 15px',
            items: [{
                xtype: 'button',
                style: "float: right",
                text: t("add_media_segment"),
                iconCls: "pimcore_icon_add",
                handler: function () {
                    Ext.MessageBox.prompt("", t("enter_media_segment"), function (button, value) {
                        if (button == "ok") {
                            this.addMediaPanel(value, null, true, true);
                        }
                    }.bind(this), null, false, '500K');
                }.bind(this)
            }, {
                xtype: 'component',
                style: "float: right; padding: 8px 40px 0 0;",
                html: t('dash_media_message')
            }]
        };

        this.groupField = new Ext.form.field.Text({
            name: "group",
            value: this.data.group,
            fieldLabel: t("group"),
            width: 450
        });

        this.settings = new Ext.form.FormPanel({
            border: false,
            labelWidth: 150,
            defaults: {
                renderer: Ext.util.Format.htmlEncode
            },
            items: [{
                xtype: "panel",
                autoHeight: true,
                border: false,
                loader: {
                    url: Routing.generate('pimcore_admin_settings_videothumbnailadaptercheck'),
                    autoLoad: true
                }
            }, {
                xtype: "textfield",
                name: "name",
                value: this.data.name,
                fieldLabel: t("name"),
                width: 450,
                readOnly: true
            }, {
                xtype: "textarea",
                name: "description",
                value: this.data.description,
                fieldLabel: t("description"),
                width: 450,
                height: 100
            }, this.groupField, {
                xtype: "combo",
                name: "present",
                fieldLabel: t("select_presetting"),
                triggerAction: "all",
                mode: "local",
                width: 300,
                store: [["average", t("average")], ["good", t("good")], ["best", t("best")]],
                listeners: {
                    select: function (el) {
                        var sel = el.getValue();
                        var vb = "";
                        var ab = "";

                        if (sel == "average") {
                            vb = 400;
                            ab = 128;
                        } else if (sel == "good") {
                            vb = 600;
                            ab = 128;
                        } else if (sel == "best") {
                            vb = 800;
                            ab = 196;
                        }

                        this.settings.getComponent("videoBitrate").setValue(vb);
                        this.settings.getComponent("audioBitrate").setValue(ab);
                    }.bind(this)
                }
            }, {
                xtype: "numberfield",
                name: "videoBitrate",
                itemId: "videoBitrate",
                value: this.data.videoBitrate,
                fieldLabel: t("video_bitrate"),
                width: 250
            }, {
                xtype: "numberfield",
                name: "audioBitrate",
                itemId: "audioBitrate",
                value: this.data.audioBitrate,
                fieldLabel: t("audio_bitrate"),
                width: 250
            }]
        });

        this.panel = new Ext.Panel({
            border: false,
            closable: true,
            autoScroll: true,
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_videothumbnail_panel_" + this.data.name,
            items: [this.settings, addViewPortButton, this.mediaPanel],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        pimcore.layout.refresh();
    },

    addMediaPanel: function (name, items, closable, activate) {

        if (this.medias[name]) {
            return;
        }

        var addMenu = [];
        var itemTypes = Object.keys(pimcore.settings.videothumbnail.items);
        for (var i = 0; i < itemTypes.length; i++) {
            if (itemTypes[i].indexOf("item") == 0) {
                addMenu.push({
                    iconCls: "pimcore_icon_add",
                    handler: this.addItem.bind(this, name, itemTypes[i]),
                    text: pimcore.settings.videothumbnail.items[itemTypes[i]](null, null, true)
                });
            }
        }

        var title = "";
        if (name == "default") {
            title = t("default");
        } else {
            title = name;
        }

        var itemContainer = new Ext.Panel({
            title: title,
            tbar: [{
                text: t("transformations"),
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false,
            closable: closable,
            autoHeight: true,
            listeners: {
                close: function (name) {
                    delete this.medias[name];
                }.bind(this, name)
            }
        });

        this.medias[name] = itemContainer;

        if (items && items.length > 0) {
            for (var i = 0; i < items.length; i++) {
                this.addItem(name, "item" + ucfirst(items[i].method), items[i].arguments);
            }
        }


        this.mediaPanel.add(itemContainer);
        this.mediaPanel.updateLayout();

        // activate the default panel
        if (activate) {
            this.mediaPanel.setActiveTab(itemContainer);
        }

        return itemContainer;
    },


    addItem: function (name, type, data) {

        var item = pimcore.settings.videothumbnail.items[type](this.medias[name], data);
        this.medias[name].add(item);
        this.medias[name].updateLayout();

        this.currentIndex++;
    },

    getData: function () {

        var mediaData = {};
        var mediaOrder = {};

        Ext.iterate(this.medias, function (key, value) {
            mediaData[key] = [];
            mediaOrder[key] = this.mediaPanel.tabBar.items.indexOf(value.tab);

            var items = value.items.getRange();
            for (var i = 0; i < items.length; i++) {
                mediaData[key].push(items[i].getForm().getFieldValues());
            }
        }.bind(this));

        return {
            settings: Ext.encode(this.settings.getForm().getFieldValues()),
            medias: Ext.encode(mediaData),
            mediaOrder: Ext.encode(mediaOrder),
            name: this.data.name
        }
    },

    save: function () {

        var reload = false;
        var newGroup = this.groupField.getValue();
        if (newGroup != this.data.group) {
            this.data.group = newGroup;
            reload = true;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_videothumbnailupdate'),
            method: "PUT", params: this.getData(),
            success: this.saveOnComplete.bind(this, reload)
        });
    },

    saveOnComplete: function (reload) {
        if (reload) {
            this.parentPanel.tree.getStore().load({
                node: this.parentPanel.tree.getRootNode()
            });
        }

        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
    },

    getCurrentIndex: function () {
        return this.currentIndex;
    }

});


/** ITEM TYPES **/

pimcore.registerNS("pimcore.settings.videothumbnail.items");

pimcore.settings.videothumbnail.items = {

    getTopBar: function (name, index, parent) {
        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        }, "-", {
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent;
                var blockElement = Ext.getCmp(blockId);

                container.moveBefore(blockElement, blockElement.previousSibling());
            }.bind(window, index, parent)
        }, {
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent;
                var blockElement = Ext.getCmp(blockId);

                container.moveAfter(blockElement, blockElement.nextSibling());
            }.bind(window, index, parent)
        }, "->", {
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.remove(Ext.getCmp(index));
            }.bind(window, index, parent)
        }];
    },

    itemResize: function (panel, data, getName) {

        var niceName = t("resize");
        if (typeof getName != "undefined" && getName) {
            return niceName;
        }

        if (typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item = new Ext.form.FormPanel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: 'false',
                padding: 0,
                items: [{
                    xtype: 'numberfield',
                    name: "width",
                    style: "padding-right: 10px",
                    fieldLabel: t("width") + ", " + t("height"),
                    width: 210,
                    value: data.width
                },
                    {
                        xtype: 'numberfield',
                        name: "height",
                        hideLabel: true,
                        width: 95,
                        value: data.height
                    }]
            }, {
                xtype: "hidden",
                name: "type",
                value: "resize"
            }, {
                xtype: "displayfield",
                hideLabel: true,
                value: "<small style='color: red;'>" + t("width_and_height_must_be_an_even_number") + "</small>"
            }]
        });

        return item;
    },

    itemScaleByHeight: function (panel, data, getName) {

        var niceName = t("scalebyheight");
        if (typeof getName != "undefined" && getName) {
            return niceName;
        }

        if (typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item = new Ext.form.FormPanel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "height",
                fieldLabel: t("height"),
                width: 250,
                value: data.height
            }, {
                xtype: "hidden",
                name: "type",
                value: "scaleByHeight"
            }]
        });

        return item;
    },

    itemScaleByWidth: function (panel, data, getName) {

        var niceName = t("scalebywidth");
        if (typeof getName != "undefined" && getName) {
            return niceName;
        }

        if (typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item = new Ext.form.FormPanel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "width",
                fieldLabel: t("width"),
                width: 250,
                value: data.width
            }, {
                xtype: "hidden",
                name: "type",
                value: "scaleByWidth"
            }]
        });

        return item;
    }
};
