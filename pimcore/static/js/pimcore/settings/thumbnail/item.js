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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.settings.thumbnail.item");
pimcore.settings.thumbnail.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentIndex = 0;
        this.medias = {};

        this.addLayout();


        // add default panel
        this.addMediaPanel("default", this.data.items ,false, true);

        // add medias
        if(this.data["medias"]) {
            Ext.iterate(this.data.medias, function (key, items) {
                this.addMediaPanel(key, items ,true, false);
            }.bind(this));
        }
    },


    addLayout: function () {

        this.editpanel = new Ext.Panel({
            bodyStyle: "padding: 20px;",
            autoScroll: true
        });

        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        });


        this.mediaPanel = new Ext.TabPanel({
            autoHeight: true,
            closable: true
        });

        this.settings = new Ext.form.FormPanel({
            layout: "pimcoreform",
            border: false,
            labelWidth: 150,
            items: [{
                xtype: "panel",
                autoHeight: true,
                border: false,
                autoLoad: "/admin/settings/thumbnail-adapter-check"
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
                store: [["SOURCE", "Auto (Web-optimized - recommended)"], ["PNG","PNG"],["GIF","GIF"], ["JPEG","JPEG"], ["PJPEG","JPEG (progressive)"],["TIFF","TIFF"],
                        ["PRINT","Print (PNG,JPG,SVG,TIFF)"]],
                width: 300
            }, {
                xtype: "spinnerfield",
                name: "quality",
                value: this.data.quality,
                fieldLabel: t("quality"),
                width: 60
            }, {
                xtype: "spinnerfield",
                name: "highResolution",
                value: this.data.highResolution,
                fieldLabel: t("high_resolution") + "<br /><small>(2x Retina, 3.2x Print, ...)</small>",
                width: 60,
                decimalPrecision: 1
            }],
            buttons: [{
                text: t("add_media_query") + " (<b>" + t("experimental") + "</b>)",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    Ext.MessageBox.prompt("", t("please_enter_the_maximum_viewport_width_in_pixels_allowed_for_this_thumbnail"), function (button, value) {
                        if(button == "ok" && is_numeric(value)) {
                            value = value + "w"; // add the width indicator here, to be future-proof
                            this.addMediaPanel(value, null ,true, true);
                        }
                    }.bind(this));
                }.bind(this)
            }]
        });

        this.panel = new Ext.Panel({
            border: false,
            closable: true,
            autoScroll: true,
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_thumbnail_panel_" + this.data.name,
            items: [this.settings, this.mediaPanel],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },

    addMediaPanel: function (name, items, closable, activate) {

        if(this.medias[name]) {
            return;
        }

        var addMenu = [];
        var itemTypes = Object.keys(pimcore.settings.thumbnail.items);
        for(var i=0; i<itemTypes.length; i++) {
            if(itemTypes[i].indexOf("item") == 0) {
                addMenu.push({
                    iconCls: "pimcore_icon_add",
                    handler: this.addItem.bind(this, name, itemTypes[i]),
                    text: pimcore.settings.thumbnail.items[itemTypes[i]](null, null,true)
                });
            }
        }

        var title = "";
        if(name == "default") {
            title = t("default");
        } else {
            // remove the width indicator (maybe there will be more complex syntax in the future)
            var tmpName = name.replace("w","");
            title = "max. width: " + tmpName + "px";
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

        if(items && items.length > 0) {
            for(var i=0; i<items.length; i++) {
                this.addItem(name, "item" + ucfirst(items[i].method), items[i].arguments);
            }
        }


        this.mediaPanel.add(itemContainer);
        this.mediaPanel.doLayout();

        // activate the default panel
        if(activate) {
            this.mediaPanel.activate(itemContainer);
        }

        return itemContainer;
    },

    addItem: function (name, type, data) {

        var item = pimcore.settings.thumbnail.items[type](this.medias[name], data);
        this.medias[name].add(item);
        this.medias[name].doLayout();

        this.currentIndex++;
    },

    getData: function () {

        var mediaData = {};

        Ext.iterate(this.medias, function (key, value) {
            mediaData[key] = [];
            var items = value.items.getRange();
            for (var i=0; i<items.length; i++) {
                mediaData[key].push(items[i].getForm().getFieldValues());
            }
        });

        return {
            settings: Ext.encode(this.settings.getForm().getFieldValues()),
            medias: Ext.encode(mediaData),
            name: this.data.name
        }
    },

    save: function () {
        Ext.Ajax.request({
            url: "/admin/settings/thumbnail-update",
            method: "post",
            params: this.getData(),
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

    detectBlockIndex: function (blockElement, container) {
        // detect index
        var index;

        for(var s=0; s<container.items.items.length; s++) {
            if(container.items.items[s].getId() == blockElement.getId()) {
                index = s;
                break;
            }
        }
        return index;
    },

    getTopBar: function (name, index, parent) {
        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.settings.thumbnail.items.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                var newIndex = index-1;
                if(newIndex < 0) {
                    newIndex = 0;
                }

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(newIndex, blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                pimcore.layout.refresh();
            }.bind(window, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.settings.thumbnail.items.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(index+1, blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                pimcore.layout.refresh();
            }.bind(window, index, parent)
        },"->",{
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.remove(Ext.getCmp(index));
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

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "width",
                    fieldLabel: t("width"),
                    width: 60,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "height",
                    fieldLabel: t("height"),
                    width: 60,
                    value: data.height
                }]
            },{
                xtype: "hidden",
                name: "type",
                value: "resize"
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

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "height",
                fieldLabel: t("height"),
                width: 60,
                value: data.height
            },{
                xtype: "hidden",
                name: "type",
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

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "width",
                fieldLabel: t("width"),
                width: 60,
                value: data.width
            },{
                xtype: "hidden",
                name: "type",
                value: "scaleByWidth"
            }]
        });

        return item;
    },

    itemContain: function (panel, data, getName) {

        var niceName = t("contain");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "width",
                    fieldLabel: t("width"),
                    width: 60,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "height",
                    fieldLabel: t("height"),
                    width: 60,
                    value: data.height
                }]
            },{
                xtype: "hidden",
                name: "type",
                value: "contain"
            }]
        });

        return item;
    },


    itemCrop: function (panel, data, getName) {

        var niceName = t("crop");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "width",
                    fieldLabel: t("width"),
                    width: 60,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "height",
                    fieldLabel: t("height"),
                    width: 60,
                    value: data.height
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "x",
                    fieldLabel: "X",
                    width: 60,
                    value: data.x
                },
                {
                    xtype: 'spinnerfield',
                    name: "y",
                    fieldLabel: "Y",
                    width: 60,
                    value: data.y
                }]
            },{
                xtype: "hidden",
                name: "type",
                value: "crop"
            }]
        });

        return item;
    },

    itemCover: function (panel, data, getName) {

        var niceName = t("cover");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "width",
                    fieldLabel: t("width"),
                    width: 60,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "height",
                    fieldLabel: t("height"),
                    width: 60,
                    value: data.height
                }]
            },{
                xtype: "combo",
                name: "positioning",
                fieldLabel: t("positioning"),
                value: data.positioning,
                triggerAction: 'all',
                editable: false,
                store: ["center","topleft","topright","bottomleft","bottomright","centerleft","centerright",
                            "topcenter","bottomcenter"],
                width: 150
            },{
                xtype: "checkbox",
                name: "doNotScaleUp",
                checked: data["doNotScaleUp"],
                fieldLabel: t("do_not_scale_up")
            }, {
                xtype: "hidden",
                name: "type",
                value: "cover"
            }]
        });

        return item;
    },

    itemFrame: function (panel, data, getName) {

        var niceName = t("frame");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "width",
                    fieldLabel: t("width"),
                    width: 60,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "height",
                    fieldLabel: t("height"),
                    width: 60,
                    value: data.height
                }]
            },{
                xtype: "hidden",
                name: "type",
                value: "frame"
            }]
        });

        return item;
    },

    itemTrim: function (panel, data, getName) {

        var niceName = t("trim") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "tolerance",
                minValue: 0,
                maxValue: 100,
                fieldLabel: t("tolerance"),
                width: 60,
                value: data.tolerance ? data.tolerance : 0
            },{
                xtype: "hidden",
                name: "type",
                value: "trim"
            }]
        });

        return item;
    },

    itemRotate: function (panel, data, getName) {

        var niceName = t("rotate");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: "angle",
                fieldLabel: t("angle"),
                width: 50,
                value: data.angle
            },{
                xtype: "hidden",
                name: "type",
                value: "rotate"
            }]
        });

        return item;
    },

    itemSetBackgroundColor: function (panel, data, getName) {

        var niceName = t("setbackgroundcolor");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                name: "color",
                fieldLabel: t("color") + " (#hex)",
                width: 70,
                value: data.color
            },{
                xtype: "hidden",
                name: "type",
                value: "setBackgroundColor"
            }]
        });

        return item;
    },


    itemRoundCorners: function (panel, data, getName) {

        var niceName = t("roundcorners") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "width",
                    fieldLabel: t("width"),
                    width: 60,
                    value: data.width
                },
                {
                    xtype: 'spinnerfield',
                    name: "height",
                    fieldLabel: t("height"),
                    width: 60,
                    value: data.height
                }]
            },{
                xtype: "hidden",
                name: "type",
                value: "roundCorners"
            }]
        });

        return item;
    },

    itemSetBackgroundImage: function (panel, data, getName) {

        var niceName = t("setbackgroundimage") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 350
            },{
                xtype: "hidden",
                name: "type",
                value: "setBackgroundImage"
            }]
        });

        return item;
    },

    itemAddOverlay: function (panel, data, getName) {

        var niceName = t("addoverlay") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }

        //set some sane default values, maybe the data parameter should already contain these values?
        if(typeof data.x == "undefined" || data.x == "") {
            data.x = 0;
        }
        if(typeof data.y == "undefined" || data.y == "") {
            data.y = 0;
        }
        if(typeof data.origin == "undefined" || data.origin == "") {
            data.origin = "top-left";
        }
        if(typeof data.alpha == "undefined" || data.alpha == "") {
            data.alpha = 100;
        }
        if(typeof data.composite == "undefined" || data.composite == "") {
            data.composite = "COMPOSITE_DEFAULT";
        }

        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 350
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'spinnerfield',
                    name: "x",
                    fieldLabel: "X",
                    width: 50,
                    value: data.x
                },
                {
                    xtype: 'spinnerfield',
                    name: "y",
                    fieldLabel: "Y",
                    width: 50,
                    value: data.y
                }]
            },{
                xtype: "combo",
                name: "origin",
                fieldLabel: t("origin"),
                value: data.origin,
                triggerAction: 'all',
                editable: false,
                store: ["top-left", "top-right", "bottom-left", "bottom-right", "center"],
                width: 200
            },{
                xtype: 'spinnerfield',
                name: "alpha",
                fieldLabel: t("opacity") + " (0-100)",
                width: 50,
                value: data.alpha
            },{
                xtype: "combo",
                name: "composite",
                fieldLabel: t("composite"),
                value: data.composite,
                triggerAction: 'all',
                editable: false,
                store: ["COMPOSITE_DEFAULT", "COMPOSITE_HARDLIGHT", "COMPOSITE_EXCLUSION"],
                width: 200
            },{
                xtype: "hidden",
                name: "type",
                value: "addOverlay"
            }]
        });

        return item;
    },

    itemApplyMask: function (panel, data, getName) {

        var niceName = t("applymask") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 350
            },{
                xtype: "hidden",
                name: "type",
                value: "applyMask"
            }]
        });

        return item;
    },

    itemGrayscale: function (panel, data, getName) {

        var niceName = t("grayscale");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            html: t("nothing_to_configure"),
            items: [{
                xtype: "hidden",
                name: "type",
                value: "grayscale"
            }]
        });

        return item;
    },

    itemSepia: function (panel, data, getName) {

        var niceName = t("sepia");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            html: t("nothing_to_configure"),
            items: [{
                xtype: "hidden",
                name: "type",
                value: "sepia"
            }]
        });

        return item;
    },

    itemSharpen: function (panel, data, getName) {

        var niceName = t("sharpen") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: 'radius',
                fieldLabel: t('radius'),
                width: 50,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.radius || 0
            },{
                xtype: 'spinnerfield',
                name: 'sigma',
                fieldLabel: t('sigma'),
                width: 50,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.sigma || 1
            },{
                xtype: 'spinnerfield',
                name: 'amount',
                fieldLabel: t('amount'),
                width: 50,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.amount || 1
            },{
                xtype: 'spinnerfield',
                name: 'threshold',
                fieldLabel: t('threshold'),
                width: 50,
                decimalPrecision: 2,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01,
                value: data.threshold || 0.05
            },{
                xtype: 'hidden',
                name: 'type',
                value: 'sharpen'
            }]
        });

        return item;
    },

    itemGaussianBlur: function (panel, data, getName) {

        var niceName = t("gaussianBlur") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'spinnerfield',
                name: 'radius',
                fieldLabel: t('radius'),
                width: 50,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.radius || 0
            },{
                xtype: 'spinnerfield',
                name: 'sigma',
                fieldLabel: t('sigma'),
                width: 50,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.sigma || 1
            },{
                xtype: 'hidden',
                name: 'type',
                value: 'gaussianBlur'
            }]
        });

        return item;
    },

    itemTifforiginal: function (panel, data, getName) {

        var niceName = t("use_original_tiff");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            html: t("use_original_tiff_description"),
            items: [{
                xtype: "hidden",
                name: "type",
                value: "tifforiginal"
            }]
        });

        return item;
    }
};
