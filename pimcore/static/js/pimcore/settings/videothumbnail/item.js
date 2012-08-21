/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
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

        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 20px;",
            autoScroll: true
        });

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
            title: t("transformations"),
            style: "margin: 20px 0 0 0;",
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false
        });

        this.panel = new Ext.form.FormPanel({
            border: false,
            layout: "fit",
            closable: true,
            autoScroll: true,
            layout: "pimcoreform",
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_videothumbnail_panel_" + this.data.name,
            labelWidth: 150,
            items: [{
                xtype: "panel",
                autoHeight: true,
                border: false,
                autoLoad: "/admin/settings/video-thumbnail-adapter-check"
            },{
                xtype: "textfield",
                name: "name",
                value: this.data.name,
                fieldLabel: t("name"),
                width: 300,
                disabled: true
            }, {
                xtype: "textarea",
                name: "description",
                value: this.data.description,
                fieldLabel: t("description"),
                width: 300,
                height: 100
            }, {
                xtype: "spinnerfield",
                name: "videoBitrate",
                value: this.data.videoBitrate,
                fieldLabel: t("video_bitrate"),
                width: 150
            }, {
                xtype: "spinnerfield",
                name: "audioBitrate",
                value: this.data.audioBitrate,
                fieldLabel: t("audio_bitrate"),
                width: 150
            }, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (type, data) {

        var item = pimcore.settings.videothumbnail.items[type](this, data);
        this.itemContainer.add(item);
        this.itemContainer.doLayout();

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
        this.parentPanel.tree.getRootNode().reload();
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
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "item." + myId  + ".width",
                    fieldLabel: t("width"),
                    width: 50,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "item." + myId  + ".height",
                    fieldLabel: t("height"),
                    width: 50,
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
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "item." + myId  + ".height",
                fieldLabel: t("height"),
                width: 50,
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
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "item." + myId  + ".width",
                fieldLabel: t("width"),
                width: 50,
                value: data.width
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "scaleByWidth"
            }]
        });

        return item;
    }
}
