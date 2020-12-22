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

pimcore.registerNS("pimcore.element.tag.imagehotspotmarkereditor");
pimcore.element.tag.imagehotspotmarkereditor = Class.create({

    initialize: function (imageId, data, saveCallback, config) {
        this.imageId = imageId;
        this.data = data;
        this.saveCallback = saveCallback;
        this.modal = true;
        this.config = typeof config != "undefined" ? config : {};
        this.context = this.config.context ? this.config.context : {};
        this.predefinedDataTemplates = this.config.predefinedDataTemplates ? this.config.predefinedDataTemplates : {};
        this.context.scope = "hotspotEditor";

        // we need some space for the surrounding area (button, dialog frame, etc...)
        this.width = Math.min(1000, window.innerWidth - 100);
        this.height = Math.min(800, window.innerHeight - 180);

    },

    open: function (modal) {
        var validImage = (typeof this.imageId != "undefined" && this.imageId !== null),
            imageUrl = Routing.generate('pimcore_admin_asset_getimagethumbnail', {id: this.imageId, width: this.width, height: this.height, contain: true});

        if (this.config.crop) {
            imageUrl = imageUrl + '&' + Ext.urlEncode(this.config.crop);
        }

        if (typeof modal != "undefined") {
            this.modal = modal;
        }

        this.hotspotStore = [];
        this.hotspotMetaData = {};

        var markerConfig = this.getButtonConfig("marker", "pimcore_icon_overlay_add");
        var hotspotConfig = this.getButtonConfig("hotspot", "pimcore_icon_image_region pimcore_icon_overlay_add");

        this.hotspotWindow = new Ext.Window({
            width: this.width + 100,
            height: this.height + 100,
            modal: this.modal,
            closeAction: "destroy",
            autoDestroy: true,
            resizable: false,
            bodyStyle: "background: url('/bundles/pimcoreadmin/img/tree-preview-transparent-background.png');",
            tbar: {
                overflowHandler: 'menu',
                items:
                    [
                        markerConfig,
                        hotspotConfig
                    ]
            },
            bbar: {
                overflowHandler: 'menu',
                items: ["->", {
                    xtype: "button",
                    iconCls: "pimcore_icon_apply",
                    text: t("save"),
                    handler: function () {

                        var el;
                        var dataHotspot = [];
                        var dataMarker = [];

                        var windowId = this.hotspotWindow.getId();
                        var windowEl = Ext.getCmp(windowId).body;
                        var originalWidth = windowEl.getWidth(true);
                        var originalHeight = windowEl.getHeight(true);

                        for (var i = 0; i < this.hotspotStore.length; i++) {
                            el = this.hotspotStore[i];

                            if (Ext.get(el["id"])) {
                                var theEl = Ext.get(el["id"]);
                                var dimensions = theEl.getStyle(["top", "left", "width", "height"]);
                                var name = Ext.get(el["id"]).getAttribute("title");
                                var metaData = [];
                                if (this.hotspotMetaData && this.hotspotMetaData[el["id"]]) {
                                    metaData = this.hotspotMetaData[el["id"]];
                                }

                                if (el.type == "marker") {
                                    dataMarker.push({
                                        top: (intval(dimensions.top) + 35) * 100 / originalHeight, //the marker el is 35px high
                                        left: (intval(dimensions.left) + 12) * 100 / originalWidth,//the marker el is 25px wide
                                        data: metaData,
                                        name: name
                                    });
                                } else if (el.type == "hotspot") {
                                    dataHotspot.push({
                                        top: intval(dimensions.top) * 100 / originalHeight,
                                        left: intval(dimensions.left) * 100 / originalWidth,
                                        width: intval(dimensions.width) * 100 / originalWidth,
                                        height: intval(dimensions.height) * 100 / originalHeight,
                                        data: metaData,
                                        name: name
                                    });
                                }
                            }
                        }

                        this.data.hotspots = dataHotspot;
                        this.data.marker = dataMarker;

                        if (typeof this.saveCallback == "function") {
                            this.saveCallback(this.data);
                        }

                        this.hotspotWindow.close();
                    }.bind(this)
                }]
            },
            html: validImage ? '<img id="hotspotImage" src="' + imageUrl + '" />' : '<span style="padding:10px;">' + t("no_data_to_display") + '</span>'
        });

        this.hotspotWindowInitCount = 0;

        this.hotspotWindow.on("afterrender", function () {
            this.hotspotWindowInterval = window.setInterval(function () {
                var el = Ext.get("hotspotImage");
                if (!el) {
                    clearInterval(this.hotspotWindowInterval);
                    return;
                }
                var imageWidth = el.getWidth();
                var imageHeight = el.getHeight();
                var i;
                var elId;

                if (el) {
                    if (el.getWidth() > 30) {
                        clearInterval(this.hotspotWindowInterval);
                        this.hotspotWindowInitCount = 0;

                        var winBodyInnerSize = this.hotspotWindow.body.getSize();
                        var winOuterSize = this.hotspotWindow.getSize();
                        var paddingWidth = winOuterSize["width"] - winBodyInnerSize["width"];
                        var paddingHeight = winOuterSize["height"] - winBodyInnerSize["height"];

                        this.hotspotWindow.setSize(imageWidth + paddingWidth, imageHeight + paddingHeight);
                        //Ext.get("hotspotImage").remove();

                        if (this.data && this.data["hotspots"]) {
                            for (i = 0; i < this.data.hotspots.length; i++) {
                                elId = this.addHotspot(this.data.hotspots[i]);
                                if (this.data.hotspots[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.data.hotspots[i]["data"];
                                }
                            }
                        }

                        if (this.data && this.data["marker"]) {
                            for (i = 0; i < this.data.marker.length; i++) {
                                elId = this.addMarker(this.data.marker[i]);
                                if (this.data.marker[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.data.marker[i]["data"];
                                }
                            }
                        }

                        return;

                    } else if (this.hotspotWindowInitCount > 60) {
                        // if more than 30 secs cancel and close the window
                        this.hotspotWindow.close();
                    }

                    this.hotspotWindowInitCount++;
                }
            }.bind(this), 500);

        }.bind(this));

        this.hotspotWindow.show();
    },

    addMarker: function (config) {

        var markerId = "marker-" + (this.hotspotStore.length + 1);
        this.hotspotWindow.body.getFirstChild().insertHtml("beforeEnd", '<div id="' + markerId
            + '" class="pimcore_image_marker"></div>');

        var markerEl = Ext.get(markerId);

        if (typeof config == "object") {
            if (config["top"]) {
                var windowId = this.hotspotWindow.getId();
                var windowEl = Ext.getCmp(windowId).body;
                var originalWidth = windowEl.getWidth(true);
                var originalHeight = windowEl.getHeight(true);

                markerEl.applyStyles({
                    top: (originalHeight * (config["top"] / 100) - 35) + "px",
                    left: (originalWidth * (config["left"] / 100) - 12) + "px"
                });
            }

            if (config["name"]) {
                markerEl.dom.setAttribute("title", config["name"]);
            }
        }

        this.addMarkerHotspotContextMenu(markerId, "marker", markerEl);

        var markerDD = new Ext.dd.DD(markerEl);
        this.hotspotStore.push({
            id: markerId,
            type: "marker"
        });

        return markerId;
    },

    addHotspot: function (config) {
        var hotspotId = "hotspot-" + (this.hotspotStore.length + 1);

        this.hotspotWindow.add(
            {
                xtype: 'component',
                id: hotspotId,
                resizable: {
                    target: hotspotId,
                    pinned: true,
                    minWidth: 20,
                    minHeight: 20,
                    preserveRatio: false,
                    dynamic: true,
                    handles: 'all'
                },
                style: "cursor:move;",
                draggable: true,
                cls: 'pimcore_image_hotspot'
            });

        var hotspotEl = Ext.get(hotspotId);

        // default dimensions
        hotspotEl.applyStyles({
            width: "50px",
            height: "50px"
        });

        if (typeof config == "object") {
            if (config["top"]) {
                var windowId = this.hotspotWindow.getId();
                var windowEl = Ext.getCmp(windowId).body;
                var originalWidth = windowEl.getWidth(true);
                var originalHeight = windowEl.getHeight(true);

                hotspotEl.applyStyles({
                    top: (originalHeight * (config["top"] / 100)) + "px",
                    left: (originalWidth * (config["left"] / 100)) + "px",
                    width: (originalWidth * (config["width"] / 100)) + "px",
                    height: (originalHeight * (config["height"] / 100)) + "px"
                });
            }

            if (config["name"]) {
                hotspotEl.dom.setAttribute("title", config["name"]);
            }
        }

        this.addMarkerHotspotContextMenu(hotspotId, "hotspot", hotspotEl);

        this.hotspotStore.push({
            id: hotspotId,
            type: "hotspot"
        });

        return hotspotId;
    },

    addMarkerHotspotContextMenu: function (id, type, el) {
        el.on("contextmenu", function (id, e) {
            var menu = new Ext.menu.Menu();

            menu.add(new Ext.menu.Item({
                text: t("add_data"),
                iconCls: "pimcore_icon_metadata pimcore_icon_overlay_add",
                handler: function (id, item) {
                    item.parentMenu.destroy();

                    this.editMarkerHotspotData(id);
                }.bind(this, id)
            }));

            menu.add(new Ext.menu.Item({
                text: t("remove"),
                iconCls: "pimcore_icon_delete",
                handler: function (id, type, item) {
                    item.parentMenu.destroy();
                    if (type == "hotspot") {
                        var cmp = Ext.getCmp(id);
                        this.hotspotWindow.remove(cmp);
                    } else {
                        var el = Ext.get(id);
                        el.remove();
                    }

                }.bind(this, id, type)
            }));


            menu.add(new Ext.menu.Item({
                text: t("clone"),
                iconCls: "pimcore_icon_copy",
                handler: function (id, type, item) {
                    item.parentMenu.destroy();

                    var el = Ext.get(id);
                    var copiedData = this.hotspotMetaData[id] ? this.hotspotMetaData[id].slice() : [];

                    var windowId = this.hotspotWindow.getId();
                    var windowEl = Ext.getCmp(windowId).body;
                    var originalWidth = windowEl.getWidth(true);
                    var originalHeight = windowEl.getHeight(true);

                    var dimensions = el.getStyle(["top", "left", "width", "height"]);

                    var config = {
                        data: copiedData,
                        name: el.getAttribute("title"),
                    };

                    if (type == "hotspot") {
                        config["top"] = (intval(dimensions.top) + 30) * 100 / originalHeight;
                        config["left"] = (intval(dimensions.left) + 30) * 100 / originalWidth;
                        config["width"] = intval(dimensions.width) * 100 / originalWidth;
                        config["height"] = intval(dimensions.height) * 100 / originalHeight;
                        var elId = this.addHotspot(config);
                    } else {
                        config["top"] = (intval(dimensions.top) + 30 + 35) * 100 / originalHeight;
                        config["left"] = (intval(dimensions.left) + 30 + 12) * 100 / originalWidth;
                        var elId = this.addMarker(config);
                    }
                    this.hotspotMetaData[elId] = copiedData;

                }.bind(this, id, type)
            }));

            menu.showAt(e.getXY());
            e.stopEvent();
        }.bind(this, id));
    },

    editMarkerHotspotData: function (id) {
        var nameField = new Ext.form.field.Text(
            {
                id: "name-field-" + id,
                value: Ext.get(id).getAttribute("title")
            }
        );
        var hotspotMetaDataWin = new Ext.Window({
            width: 600,
            height: 440,
            modal: this.modal,
            resizable: false,
            autoScroll: true,
            items: [{
                xtype: "form",
                itemId: "form",
                bodyStyle: "padding: 10px;"
            }],
            tbar: [{
                xtype: "button",
                iconCls: "pimcore_icon_add",
                menu: [{
                    text: t("textfield"),
                    iconCls: "pimcore_icon_input",
                    handler: function () {
                        addItem("textfield");
                    }
                }, {
                    text: t("textarea"),
                    iconCls: "pimcore_icon_textarea",
                    handler: function () {
                        addItem("textarea");
                    }
                }, {
                    text: t("checkbox"),
                    iconCls: "pimcore_icon_checkbox",
                    handler: function () {
                        addItem("checkbox");
                    }
                }, {
                    text: t("object"),
                    iconCls: "pimcore_icon_object",
                    handler: function () {
                        addItem("object");
                    }
                }, {
                    text: t("document"),
                    iconCls: "pimcore_icon_document",
                    handler: function () {
                        addItem("document");
                    }
                }, {
                    text: t("asset"),
                    iconCls: "pimcore_icon_asset",
                    handler: function () {
                        addItem("asset");
                    }
                }]
            }, "->", {
                xtype: "tbtext",
                text: t("name") + ":"
            },
                nameField
            ],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function (id) {

                    var form = hotspotMetaDataWin.getComponent("form").getForm();
                    var data = form.getFieldValues();
                    var normalizedData = [];

                    // when only one item is in the form
                    if (typeof data["name"] == "string") {
                        data = {
                            name: [data["name"]],
                            type: [data["type"]],
                            value: [data["value"]]
                        };
                    }

                    if (data && data["name"] && data["name"].length > 0) {
                        for (var i = 0; i < data["name"].length; i++) {

                            var listItem = {
                                name: data["name"][i],
                                value: data["value"][i],
                                type: data["type"][i]
                            };

                            normalizedData.push(listItem);
                        }
                    }


                    Ext.get(id).dom.setAttribute("title", Ext.getCmp("name-field-" + id).getValue());
                    this.hotspotMetaData[id] = normalizedData;

                    hotspotMetaDataWin.close();
                }.bind(this, id)
            }],
            listeners: {
                afterrender: function (id) {
                    if (this.hotspotMetaData && this.hotspotMetaData[id]) {
                        var data = this.hotspotMetaData[id];
                        for (var i = 0; i < data.length; i++) {
                            addItem(data[i]["type"], data[i]);
                        }
                    }
                }.bind(this, id)
            }
        });

        var addItem = function (hotspotMetaDataWin, type, data) {

            var id = "item-" + uniqid();
            var valueField;

            if (!data) {
                data = {
                    name: "",
                    value: ""
                };
            }

            if (type == "textfield") {
                valueField = {
                    xtype: "textfield",
                    name: "value",
                    fieldLabel: t("value"),
                    width: 500,
                    value: data["value"]
                };
            } else if (type == "textarea") {
                valueField = {
                    xtype: "textarea",
                    name: "value",
                    fieldLabel: t("value"),
                    width: 500,
                    value: data["value"]
                };
            } else if (type == "checkbox") {
                valueField = {
                    xtype: "checkbox",
                    name: "value",
                    fieldLabel: t("value"),
                    checked: data["value"]
                };
            } else if (type == "object" || type == "asset" || type == "document") {
                var textField = new Ext.form.TextField({
                    fieldCls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 420,
                    listeners: {
                        render: this.addDataDropTarget.bind(this, type)
                    }
                });

                var items = [textField, {
                    xtype: "button",
                    iconCls: "pimcore_icon_edit",
                    handler: this.openElement.bind(this, textField, type)
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete"
                    ,
                    handler: this.empty.bind(this, textField)
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this, textField, type, hotspotMetaDataWin, nameField)
                }];

                valueField = new Ext.form.FieldContainer({
                    items: items,
                    componentCls: "object_field",
                    layout: 'hbox'
                });

            } else {
                // no valid type
                return;
            }

            hotspotMetaDataWin.getComponent("form").add({
                xtype: 'panel',
                itemId: id,
                bodyStyle: "padding-top:10px",
                items: [{
                    xtype: "hidden",
                    name: "type",
                    value: type
                }, {
                    xtype: "textfield",
                    name: "name",
                    value: data["name"],
                    fieldLabel: t("name")
                }, valueField],
                tbar: ["->", {
                    iconCls: "pimcore_icon_delete",
                    handler: function (hotspotMetaDataWin, subComponen) {
                        var form = hotspotMetaDataWin.getComponent("form");
                        form.remove(form.getComponent(id));
                        hotspotMetaDataWin.updateLayout();
                    }.bind(this, hotspotMetaDataWin)
                }]
            });

            hotspotMetaDataWin.updateLayout();
        }.bind(this, hotspotMetaDataWin);

        hotspotMetaDataWin.show();
    },

    empty: function (textfield) {
        textfield.setValue("");
    },

    openElement: function (textfield, type) {
        var value = textfield.getValue();
        if (value) {
            pimcore.helpers.openElement(value, type);
        }
    },


    addDataDropTarget: function (type, el) {
        var drop = function (el, target, dd, e, data) {

            if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                return false;
            }

            data = data.records[0].data;
            if (data.elementType == type) {
                target.component.setValue(data.path);
                return true;
            } else {
                return false;
            }
        }.bind(this, el);

        var over = function (target, dd, e, data) {
            if (data.records.length === 1 && data.records[0].data.elementType == type) {
                return Ext.dd.DropZone.prototype.dropAllowed;
            }
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        };

        if (typeof dndManager == "object") {
            // register at global DnD manager
            // in iframes, eg. document editmode
            dndManager.addDropTarget(el.getEl(), over, drop);
        } else {
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function (e) {
                    return el.getEl();
                },
                onNodeOver: over,
                onNodeDrop: drop
            });
        }
    },

    openSearchEditor: function (textfield, type, hotspotMetaDataWin, nameField) {
        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};

        allowedTypes.push(type);
        if (type == "object") {
            allowedSubtypes.object = ["object", "folder", "variant"];
        }

        var form = hotspotMetaDataWin.getComponent("form").getForm();
        var hotspotData = form.getFieldValues();

        var hotspotName = nameField.getValue();


        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this, textfield), {
                type: allowedTypes,
                subtype: allowedSubtypes,
                specific: allowedSpecific
            },
            {
                context: Ext.apply(
                    {
                        hotspotName: hotspotName,
                        hotspotData: hotspotData
                    }, this.context)
            });
    },

    addDataFromSelector: function (textfield, data) {
        if (data) {
            textfield.setValue(data.fullpath);
        }
    }

    ,

    getButtonConfig: function (type, iconCls) {


        var callbackFunctionName = "add" + ucfirst(type);
        var callbackFunction = this[callbackFunctionName].bind(this);
        var textKey = "add_" + type;

        var buttonConfig = {
            xtype: "button",
            text: t(textKey),
            iconCls: iconCls,
            handler: function () {
                callbackFunction();
            }.bind(this)
        };

        if (this.predefinedDataTemplates[type] && this.predefinedDataTemplates[type].length > 0) {
            buttonConfig.xtype = "splitbutton";
            var menu = [];
            for (var i = 0; i < this.predefinedDataTemplates[type].length; i++) {
                var templateConfig = this.predefinedDataTemplates[type][i];
                var templateConfigName = templateConfig.name;
                var templateMenuName = templateConfig.menuName ? templateConfig.menuName : templateConfigName;
                if (!templateConfigName) {
                    templateConfigName = "&nbsp";
                }
                menu.push(
                    {
                        text: t(templateMenuName),
                        iconCls: "pimcore_icon_hotspotmarker_template",
                        handler: function (templateConfig) {
                            var elId = callbackFunction(templateConfig);
                            var copiedData = templateConfig.data ? templateConfig.data.slice() : [];
                            this.hotspotMetaData[elId] = copiedData;
                        }.bind(this, templateConfig)
                    }
                );
            }
            buttonConfig.menu = menu;
        }

        return buttonConfig;
    }


});
