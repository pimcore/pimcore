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
        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        });


        this.mediaPanel = new Ext.TabPanel({
            autoHeight: true
        });

        var addViewPortButton = {
            xtype: 'panel',
            style: 'margin-bottom: 15px',
            items: [{
                xtype: 'button',
                style: "float: right",
                text: t("add_media_query"),
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

        };

        this.settings = new Ext.form.FormPanel({
            border: false,
            labelWidth: 150,
            items: [{
                xtype: "panel",
                autoHeight: true,
                border: false,
                loader: {
                    url: "/admin/settings/thumbnail-adapter-check",
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
                height: 50
            }, {
                xtype: "combo",
                name: "format",
                fieldLabel: t("format"),
                value: this.data.format,
                triggerAction: 'all',
                editable: false,
                store: [["SOURCE", "Auto (Web-optimized - recommended)"], ["ORIGINAL","ORIGINAL"], ["PNG","PNG"],["GIF","GIF"], ["JPEG","JPEG"], ["PJPEG","JPEG (progressive)"],["TIFF","TIFF"],
                        ["PRINT","Print (PNG,JPG,SVG,TIFF)"]],
                width: 450
            }, {
                xtype: "fieldset",
                title: t("advanced_settings"),
                collapsible: true,
                collapsed: true,
                items: [{
                    xtype: "numberfield",
                    name: "quality",
                    value: this.data.quality,
                    fieldLabel: t("quality") + " (JPEG)",
                    width: 210
                }, {
                    xtype: "numberfield",
                    name: "highResolution",
                    value: this.data.highResolution,
                    fieldLabel: t("high_resolution"),
                    width: 210,
                    decimalPrecision: 1
                }, {
                    xtype: "container",
                    html: "<small>(" + t("high_resolution_info_text") + ")</small>",
                    style: "margin-bottom: 20px"
                }, {
                    xtype: "checkbox",
                    name: "preserveColor",
                    labelWidth: 350,
                    fieldLabel: t("preserve_color") + " (Imagick, ORIGINAL)",
                    checked: this.data.preserveColor
                }, {
                    xtype: "checkbox",
                    name: "preserveMetaData",
                    labelWidth: 350,
                    fieldLabel: t("preserve_meta_data") + " (Imagick, ORIGINAL)",
                    checked: this.data.preserveMetaData
                }, {
                    xtype: "container",
                    html: "<small>(" + t("thumbnail_preserve_info_text") + ")</small>",
                    style: "margin-bottom: 20px"
                }]
            }]
        });

        this.panel = new Ext.Panel({
            border: false,
            closable: true,
            autoScroll: true,
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_thumbnail_panel_" + this.data.name,
            items: [this.settings, addViewPortButton, this.mediaPanel],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

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
        this.mediaPanel.updateLayout();

        // activate the default panel
        if(activate) {
            this.mediaPanel.setActiveTab(itemContainer);
        }

        return itemContainer;
    },

    addItem: function (name, type, data) {

        var item = pimcore.settings.thumbnail.items[type](this.medias[name], data);
        this.medias[name].add(item);
        this.medias[name].updateLayout();

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

pimcore.registerNS("pimcore.settings.thumbnail.items");

pimcore.settings.thumbnail.items = {
    getTopBar: function (name, index, parent) {
        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent;
                var blockElement = Ext.getCmp(blockId);

                container.moveBefore(blockElement, blockElement.previousSibling());
            }.bind(window, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent;
                var blockElement = Ext.getCmp(blockId);

                container.moveAfter(blockElement, blockElement.nextSibling());
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "width",
                fieldLabel: t("width"),
                width: 210,
                value: data.width
            },{
                xtype: 'numberfield',
                name: "height",
                fieldLabel: t("height"),
                width: 210,
                value: data.height
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "height",
                fieldLabel: t("height"),
                width: 210,
                value: data.height
            },{
                xtype: "checkbox",
                name: "forceResize",
                checked: data["forceResize"],
                fieldLabel: t("force_resize")
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "width",
                fieldLabel: t("width"),
                width: 210,
                value: data.width
            },{
                xtype: "checkbox",
                name: "forceResize",
                checked: data["forceResize"],
                fieldLabel: t("force_resize")
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: false,
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
            },{
                xtype: "checkbox",
                name: "forceResize",
                checked: data["forceResize"],
                fieldLabel: t("force_resize")
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: false,
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
            },{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: false,
                padding: 0,
                items: [{
                    xtype: 'numberfield',
                    name: "x",
                    style: "padding-right: 10px",
                    fieldLabel: "X, Y",
                    width: 210,
                    value: data.x
                },
                {
                    xtype: 'numberfield',
                    name: "y",
                    hideLabel: true,
                    width: 95,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                border: false,
                style: "border-top: none !important;",
                layout: 'hbox',
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
            },{
                xtype: "combo",
                name: "positioning",
                fieldLabel: t("positioning"),
                value: data.positioning,
                triggerAction: 'all',
                editable: false,
                store: ["center","topleft","topright","bottomleft","bottomright","centerleft","centerright",
                            "topcenter","bottomcenter"],
                width: 250
            },{
                xtype: "checkbox",
                name: "forceResize",
                checked: data["forceResize"],
                fieldLabel: t("force_resize")
            },{
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: false,
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
            },{
                xtype: "checkbox",
                name: "forceResize",
                checked: data["forceResize"],
                fieldLabel: t("force_resize")
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "tolerance",
                minValue: 0,
                maxValue: 100,
                fieldLabel: t("tolerance"),
                width: 210,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: "angle",
                fieldLabel: t("angle"),
                width: 210,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                name: "color",
                fieldLabel: t("color") + " (#hex)",
                width: 210,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: false,
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
            },{
                xtype: "hidden",
                name: "type",
                value: "roundCorners"
            }]
        });

        return item;
    },

    itemSetBackgroundImage: function (panel, data, getName) {

        var niceName = t("setbackgroundimage");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 450
            },{
                xtype: "combo",
                name: "mode",
                fieldLabel: t("mode"),
                value: data.mode,
                triggerAction: 'all',
                editable: false,
                store: [["", "fit"], ["cropTopLeft","cropTopLeft"]],
                width: 300
            }, {
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 450
            },{
                xtype: 'fieldset',
                layout: 'hbox',
                style: "border-top: none !important;",
                border: false,
                padding: 0,
                items: [{
                    xtype: 'numberfield',
                    name: "x",
                    style: "padding-right: 10px",
                    fieldLabel: "X, Y",
                    width: 210,
                    value: data.x
                },
                {
                    xtype: 'numberfield',
                    name: "y",
                    hideLabel: true,
                    width: 95,
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
                width: 300
            },{
                xtype: 'numberfield',
                name: "alpha",
                fieldLabel: t("opacity") + " (0-100)",
                width: 210,
                value: data.alpha
            },{
                xtype: "combo",
                name: "composite",
                fieldLabel: t("composite"),
                value: data.composite,
                triggerAction: 'all',
                editable: false,
                store: ["COMPOSITE_DEFAULT", "COMPOSITE_HARDLIGHT", "COMPOSITE_EXCLUSION"],
                width: 300
            },{
                xtype: "hidden",
                name: "type",
                value: "addOverlay"
            }]
        });

        return item;
    },

    itemAddOverlayFit: function (panel, data, getName) {

        var niceName = t("addoverlay_fit") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }

        //set some sane default values, maybe the data parameter should already contain these values?
        if(typeof data.composite == "undefined" || data.composite == "") {
            data.composite = "COMPOSITE_DEFAULT";
        }

        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 450
            },{
                xtype: "combo",
                name: "composite",
                fieldLabel: t("composite"),
                value: data.composite,
                triggerAction: 'all',
                editable: false,
                store: ["COMPOSITE_DEFAULT", "COMPOSITE_HARDLIGHT", "COMPOSITE_EXCLUSION"],
                width: 300
            },{
                xtype: "hidden",
                name: "type",
                value: "addOverlayFit"
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                fieldLabel: t("path") + " <br />(rel. to doc-root)",
                name: "path",
                value: data.path,
                width: 450
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: 'radius',
                fieldLabel: t('radius'),
                width: 210,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.radius || 0
            },{
                xtype: 'numberfield',
                name: 'sigma',
                fieldLabel: t('sigma'),
                width: 210,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.sigma || 1
            },{
                xtype: 'numberfield',
                name: 'amount',
                fieldLabel: t('amount'),
                width: 210,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.amount || 1
            },{
                xtype: 'numberfield',
                name: 'threshold',
                fieldLabel: t('threshold'),
                width: 210,
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: 'radius',
                fieldLabel: t('radius'),
                width: 210,
                decimalPrecision: 1,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.1,
                value: data.radius || 0
            },{
                xtype: 'numberfield',
                name: 'sigma',
                width: 210,
                fieldLabel: t('sigma'),
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

    itemBrightnessSaturation: function (panel, data, getName) {

        var niceName = t("brightness") + " / " + t("saturation") + " / " + t("hue") + " (Imagick)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            id: myId,
            style: "margin-top: 10px",
            border: true,
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: 'brightness',
                fieldLabel: t('brightness'),
                width: 210,
                allowDecimals: false,
                incrementValue: 1,
                value: data.brightness || 100
            },{
                xtype: 'numberfield',
                name: 'saturation',
                fieldLabel: t('saturation'),
                width: 210,
                allowDecimals: false,
                incrementValue: 1,
                value: data.saturation || 100
            },{
                xtype: 'numberfield',
                name: 'hue',
                fieldLabel: t('hue'),
                width: 210,
                allowDecimals: false,
                incrementValue: 1,
                value: data.hue || 100
            },{
                xtype: 'hidden',
                name: 'type',
                value: 'brightnessSaturation'
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
            id: myId,
            style: "margin-top: 10px",
            border: true,
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
