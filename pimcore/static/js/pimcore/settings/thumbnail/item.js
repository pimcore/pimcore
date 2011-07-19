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

pimcore.registerNS("pimcore.settings.thumbnail.item");
pimcore.settings.thumbnail.item = Class.create({


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
        var itemTypes = Object.keys(pimcore.settings.thumbnail.items);
        for(var i=0; i<itemTypes.length; i++) {
            if(itemTypes[i].indexOf("item") == 0) {
                addMenu.push({
                    iconCls: "pimcore_icon_add",
                    handler: this.addItem.bind(this, itemTypes[i]),
                    text: t(itemTypes[i].split("item")[1].toLowerCase())
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
            id: "pimcore_thumbnail_panel_" + this.data.name,
            items: [{
                xtype: "panel",
                bodyStyle: "padding: 10px;",
                style: "margin:0 0 20px 0",
                html: '<span style="color: red; font-weight: bold;">' + t("important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality") + '</span>'
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
                xtype: "combo",
                name: "format",
                fieldLabel: t("format"),
                value: this.data.format,
                triggerAction: 'all',
                editable: false,
                store: ["PNG","GIF","JPEG","SOURCE"],
                width: 75
            }, {
                xtype: "spinnerfield",
                name: "quality",
                value: this.data.quality,
                fieldLabel: t("quality"),
                width: 60
            }, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (type, data) {

        var item = pimcore.settings.thumbnail.items[type](this, data);
        this.itemContainer.add(item);
        this.itemContainer.doLayout();

        this.currentIndex++;
    },

    save: function () {

        var m = Ext.encode(this.panel.getForm().getFieldValues());
        Ext.Ajax.request({
            url: "/admin/settings/thumbnail-update",
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

pimcore.registerNS("pimcore.settings.thumbnail.items");

pimcore.settings.thumbnail.items = {

    getTopBar: function (name, index, parent) {
        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.itemContainer.remove(Ext.getCmp(index));
            }.bind(window, index, parent)
        }];
    },

    itemResize: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("resize"), myId, panel),
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
            }]
        });

        return item;
    },

    itemScaleByHeight: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("scalebyheight"), myId, panel),
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

    itemScaleByWidth: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("scalebywidth"), myId, panel),
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
    },

    itemContain: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("contain"), myId, panel),
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
                value: "contain"
            }]
        });

        return item;
    },


    itemCrop: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("crop"), myId, panel),
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
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "item." + myId  + ".x",
                    fieldLabel: "X",
                    width: 50,
                    value: data.x
                },
                {
                    xtype: 'spinnerfield',
                    name: "item." + myId  + ".y",
                    fieldLabel: "Y",
                    width: 50,
                    value: data.y
                }]
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "crop"
            }]
        });

        return item;
    },

    itemCover: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("cover"), myId, panel),
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
                xtype: "combo",
                name: "item." + myId  + ".positioning",
                fieldLabel: t("positioning"),
                value: data.positioning,
                triggerAction: 'all',
                editable: false,
                store: ["center","topleft","topright","bottomleft","bottomright","centerleft","centerright","topcenter","bottomcenter"],
                width: 150
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "cover"
            }]
        });

        return item;
    },

    itemFrame: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("frame"), myId, panel),
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
                value: "frame"
            }]
        });

        return item;
    },

    itemRotate: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("rotate"), myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "item." + myId  + ".angle",
                fieldLabel: t("angle"),
                width: 50,
                value: data.angle
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "rotate"
            }]
        });

        return item;
    },

    itemSetBackgroundColor: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("setbackgroundcolor"), myId, panel),
            items: [{
                xtype: 'textfield',
                name: "item." + myId  + ".color",
                fieldLabel: t("color") + " (#hex)",
                width: 70,
                value: data.color
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "setBackgroundColor"
            }]
        });

        return item;
    },


    itemRoundCorners: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("roundcorners") + " (Imagick)", myId, panel),
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
                value: "roundCorners"
            }]
        });

        return item;
    },

    itemSetBackgroundImage: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("setbackgroundimage") + " (Imagick)", myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "item." + myId  + ".path",
                value: data.path,
                width: 350
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "setBackgroundImage"
            }]
        });

        return item;
    },

    itemAddOverlay: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("addoverlay") + " (Imagick)", myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "item." + myId  + ".path",
                value: data.path,
                width: 350
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "item." + myId  + ".x",
                    fieldLabel: "X",
                    width: 50,
                    value: data.x
                },
                {
                    xtype: 'spinnerfield',
                    name: "item." + myId  + ".y",
                    fieldLabel: "Y",
                    width: 50,
                    value: data.y
                }]
            },{
                xtype: 'spinnerfield',
                name: "item." + myId  + ".alpha",
                fieldLabel: t("opacity") + " (0-100)",
                width: 50,
                value: data.alpha
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "addOverlay"
            }]
        });

        return item;
    },

    itemApplyMask: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("applymask") + " (Imagick)", myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "item." + myId  + ".path",
                value: data.path,
                width: 350
            },{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "applyMask"
            }]
        });

        return item;
    },

    itemGrayscale: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("grayscale"), myId, panel),
            html: t("nothing_to_configure"),
            items: [{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "grayscale"
            }]
        });

        return item;
    },

    itemSepia: function (panel, data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(t("sepia"), myId, panel),
            html: t("nothing_to_configure"),
            items: [{
                xtype: "hidden",
                name: "item." + myId  + ".type",
                value: "sepia"
            }]
        });

        return item;
    }
}