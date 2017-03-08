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

pimcore.registerNS("pimcore.settings.videothumbnail.item");
pimcore.settings.videothumbnail.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentIndex = 0;

        this.addLayout();

        if(this.data.items && this.data.items.length > 0) {
            for(var i=0; i<this.data.items.length; i++) {
                this.addItem("item" + ucfirst(this.data.items[i].method), this.data.items[i].arguments);
            }
        }
    },


    addLayout: function () {

/*        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 20px;",
            autoScroll: true
        });
*/

        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        }); 


        var addMenu = [];
        var itemTypes = Object.keys(pimcore.settings.videothumbnail.items);
        for(var i=0; i<itemTypes.length; i++) {
            if(itemTypes[i].indexOf("item") == 0) {
                addMenu.push({
                    iconCls: "pimcore_icon_add",
                    handler: this.addItem.bind(this, itemTypes[i]),
                    text: pimcore.settings.videothumbnail.items[itemTypes[i]](null, null,true)
                });
            }
        }

        this.itemContainer = new Ext.Panel({
            style: "margin: 20px 0 0 0;",
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu,
                text: t("transformations")
            }],
            border: false
        });

        this.panel = new Ext.form.FormPanel({
            border: false,
            closable: true,
            autoScroll: true,

            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_videothumbnail_panel_" + this.data.name,
            labelWidth: 150,
            items: [{
                xtype: "panel",
                autoHeight: true,
                border: false,
                loader: {
                    url: "/admin/settings/video-thumbnail-adapter-check",
                    autoLoad: true
                }
            },{
                xtype: "textfield",
                name: "name",
                value: this.data.name,
                fieldLabel: t("name"),
                width: 450,
                disabled: true
            }, {
                xtype: "textarea",
                name: "description",
                value: this.data.description,
                fieldLabel: t("description"),
                width: 450,
                height: 100
            }, {
                xtype: "combo",
                name: "present",
                fieldLabel: t("select_presetting"),
                triggerAction: "all",
                mode: "local",
                width: 300,
                store: [["average",t("average")],["good", t("good")],["best",t("best")]],
                listeners: {
                    select: function (el) {
                        var sel = el.getValue();
                        var vb = "";
                        var ab = "";

                        if(sel == "average") {
                            vb = 400;
                            ab = 128;
                        } else if (sel == "good") {
                            vb = 600;
                            ab = 128;
                        } else if (sel == "best") {
                            vb = 800;
                            ab = 196;
                        }

                        this.panel.getComponent("videoBitrate").setValue(vb);
                        this.panel.getComponent("audioBitrate").setValue(ab);
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
            }, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (type, data) {

        var item = pimcore.settings.videothumbnail.items[type](this, data);
        this.itemContainer.add(item);
        this.itemContainer.updateLayout();

        this.currentIndex++;
    },

    save: function () {

        var m = Ext.encode(this.panel.getForm().getFieldValues());
        Ext.Ajax.request({
            url: "/admin/settings/video-thumbnail-update",
            method: "post",
            params: {
                configuration: m,
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getStore().load({
            node: this.parentPanel.tree.getRootNode()
        });

        pimcore.helpers.showNotification(t("success"), t("thumbnail_saved_successfully"), "success");
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
        },"->",{
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.itemContainer.remove(Ext.getCmp(index));
            }.bind(window, index, parent)
        }];
    },

    itemResize: function (panel, data, getName) {

        var niceName = t("resize");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
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
                    name: "item." + myId  + ".width",
                    style: "padding-right: 10px",
                    fieldLabel: t("width") + ", " + t("height"),
                    width: 210,
                    value: data.width
                },
                {
                    xtype: 'numberfield',
                    name: "item." + myId  + ".height",
                    hideLabel: true,
                    width: 95,
                    value: data.height
                }]
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
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
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "item." + myId  + ".height",
                fieldLabel: t("height"),
                width: 250,
                value: data.height
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "scaleByHeight"
            }]
        });

        return item;
    },

    itemScaleByWidth: function (panel, data, getName) {

        var niceName = t("scalebywidth");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "item." + myId  + ".width",
                fieldLabel: t("width"),
                width: 250,
                value: data.width
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "scaleByWidth"
            }]
        });

        return item;
    }
};
