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

pimcore.registerNS("pimcore.element.tag.imagehotspotmarkereditor");
pimcore.element.tag.imagehotspotmarkereditor = Class.create({

    initialize: function (imageId, data, saveCallback) {
        this.imageId = imageId;
        this.data = data;
        this.saveCallback = saveCallback;
        this.modal = true;

        // we need some space for the surrounding area (button, dialog frame, etc...)
        this.width = Math.min(1000, window.innerWidth - 100);
        this.height = Math.min(800, window.innerHeight - 100);

    },

    open: function (modal) {
        var imageUrl = '/admin/asset/get-image-thumbnail/id/' + this.imageId + '/width/' + this.width + '/height/'
            + this.height + '/contain/true';

        if(typeof modal != "undefined") {
            this.modal = modal;
        }

        this.hotspotStore = [];
        this.hotspotMetaData = {};

        this.hotspotWindow = new Ext.Window({
            width: this.width + 100,
            height: this.height + 100,
            modal: this.modal,
            closeAction: "close",
            resizable: false,
            bodyStyle: "background: url(" + imageUrl + ") center center no-repeat; position:relative; ",
            tbar: [{
                xtype: "button",
                text: t("add_marker"),
                iconCls: "pimcore_icon_add_marker",
                handler: function () {
                    this.addMarker();

                }.bind(this)
            }, {
                xtype: "button",
                text: t("add_hotspot"),
                iconCls: "pimcore_icon_add_hotspot",
                handler: function () {
                    this.addHotspot();
                }.bind(this)
            }],
            bbar: ["->", {
                xtype: "button",
                iconCls: "pimcore_icon_apply",
                text: t("save"),
                handler: function () {

                    var el;
                    var dataHotspot = [];
                    var dataMarker = [];
                    var originalWidth = this.hotspotWindow.getInnerWidth();
                    var originalHeight = this.hotspotWindow.getInnerHeight();

                    for(var i=0; i<this.hotspotStore.length; i++) {
                        el = this.hotspotStore[i];

                        if(Ext.get(el["id"])) {
                            var dimensions = Ext.get(el["id"]).getStyles("top","left","width","height");
                            var name = Ext.get(el["id"]).getAttribute("title");
                            var metaData = [];
                            if(this.hotspotMetaData && this.hotspotMetaData[el["id"]]) {
                                metaData = this.hotspotMetaData[el["id"]];
                            }

                            if(el.type == "marker") {
                                dataMarker.push({
                                    top:(intval(dimensions.top)+35) * 100 / originalHeight, //the marker el is 35px high
                                    left:(intval(dimensions.left)+12) * 100 / originalWidth,//the marker el is 25px wide
                                    data: metaData,
                                    name: name
                                });
                            } else if (el.type == "hotspot") {
                                dataHotspot.push({
                                    top: intval(dimensions.top) * 100 / originalHeight,
                                    left:  intval(dimensions.left) * 100 / originalWidth,
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

                    if(typeof this.saveCallback == "function") {
                        this.saveCallback(this.data);
                    }

                    this.hotspotWindow.close();
                }.bind(this)
            }],
            html: '<img id="hotspotImage" src="' + imageUrl + '" />'
        });

        this.hotspotWindowInitCount = 0;

        this.hotspotWindow.on("afterrender", function ( ){
            this.hotspotWindowInterval = window.setInterval(function () {
                var el = Ext.get("hotspotImage");
                if(!el) {
                    clearInterval(this.hotspotWindowInterval);
                    return;
                }
                var imageWidth = el.getWidth();
                var imageHeight = el.getHeight();
                var i;
                var elId;

                if(el) {
                    if(el.getWidth() > 30) {
                        clearInterval(this.hotspotWindowInterval);
                        this.hotspotWindowInitCount = 0;

                        var winBodyInnerSize = this.hotspotWindow.body.getSize();
                        var winOuterSize = this.hotspotWindow.getSize();
                        var paddingWidth = winOuterSize["width"] - winBodyInnerSize["width"];
                        var paddingHeight = winOuterSize["height"] - winBodyInnerSize["height"];

                        this.hotspotWindow.setSize(imageWidth + paddingWidth, imageHeight + paddingHeight);
                        Ext.get("hotspotImage").remove();

                        if(this.data && this.data["hotspots"]) {
                            for(i=0; i<this.data.hotspots.length; i++) {
                                elId = this.addHotspot(this.data.hotspots[i]);
                                if(this.data.hotspots[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.data.hotspots[i]["data"];
                                }
                            }
                        }

                        if(this.data && this.data["marker"]) {
                            for(i=0; i<this.data.marker.length; i++) {
                                elId = this.addMarker(this.data.marker[i]);
                                if(this.data.marker[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.data.marker[i]["data"];
                                }
                            }
                        }

                        return;

                    } else if (this.hotspotWindowInitCount > 60) {
                        // if more than 30 secs cancel and close the window
                        this.resizer = null;
                        this.hotspotWindow.close();
                    }

                    this.hotspotWindowInitCount++;
                }
            }.bind(this), 500);

        }.bind(this));

        this.hotspotWindow.show();
    },

    addMarker: function (config) {

        var markerId = "marker-" + (this.hotspotStore.length+1);
        this.hotspotWindow.body.insertHtml("beforeEnd", '<div id="' + markerId
            + '" class="pimcore_image_marker"></div>');

        var markerEl = Ext.get(markerId);

        if(typeof config == "object" && config["top"]) {
            var originalWidth = this.hotspotWindow.getInnerWidth();
            var originalHeight = this.hotspotWindow.getInnerHeight();

            markerEl.applyStyles({
                top: (originalHeight * (config["top"]/100) - 35) + "px",
                left: (originalWidth * (config["left"]/100) - 12) + "px"
            });

            if(config["name"]) {
                markerEl.dom.setAttribute("title", config["name"]);
            }
        }

        this.addMarkerHotspotContextMenu(markerId, markerEl);

        var markerDD = new Ext.dd.DD(markerEl);
        this.hotspotStore.push({
            id: markerId,
            type: "marker"
        });

        return markerId;
    },

    addHotspot: function (config) {
        var hotspotId = "hotspot-" + (this.hotspotStore.length+1);
        this.hotspotWindow.body.insertHtml("beforeEnd", '<div id="' + hotspotId + '" class="pimcore_image_hotspot"></div>');

        var hotspotEl = Ext.get(hotspotId);

        // default dimensions
        hotspotEl.applyStyles({
            width: "50px",
            height: "50px"
        });

        if(typeof config == "object" && config["top"]) {
            var originalWidth = this.hotspotWindow.getInnerWidth();
            var originalHeight = this.hotspotWindow.getInnerHeight();

            hotspotEl.applyStyles({
                top: (originalHeight * (config["top"]/100)) + "px",
                left: (originalWidth * (config["left"]/100)) + "px",
                width: (originalWidth * (config["width"]/100)) + "px",
                height: (originalHeight * (config["height"]/100)) + "px"
            });

            if(config["name"]) {
                hotspotEl.dom.setAttribute("title", config["name"]);
            }
        }

        this.addMarkerHotspotContextMenu(hotspotId, hotspotEl);

        var resizer = new Ext.Resizable(hotspotId, {
            pinned:true,
            minWidth:20,
            minHeight: 20,
            preserveRatio: false,
            dynamic:true,
            handles: 'all',
            draggable:true
        });


        this.hotspotStore.push({
            id: hotspotId,
            type: "hotspot"
        });

        return hotspotId;
    },

    addMarkerHotspotContextMenu: function (id, el) {
        el.on("contextmenu", function (id, e) {
            var menu = new Ext.menu.Menu();

            menu.add(new Ext.menu.Item({
                text: t("add_data"),
                iconCls: "pimcore_icon_add_data",
                handler: function (id, item) {
                    item.parentMenu.destroy();

                    this.editMarkerHotspotData(id);
                }.bind(this, id)
            }));

            menu.add(new Ext.menu.Item({
                text: t("remove"),
                iconCls: "pimcore_icon_delete",
                handler: function (id, item) {
                    item.parentMenu.destroy();
                    Ext.get(id).remove();
                }.bind(this, id)
            }));

            menu.showAt(e.getXY());
            e.stopEvent();
        }.bind(this, id));
    },

    editMarkerHotspotData: function (id) {
        var hotspotMetaDataWin = new Ext.Window({
            width: 600,
            height: 440,
            modal: this.modal,
            closeAction: "close",
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
            }, {
                xtype: "textfield",
                id: "name-field-" + id,
                value: Ext.get(id).getAttribute("title")
            }],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function (id) {

                    var form = hotspotMetaDataWin.getComponent("form").getForm();
                    var data = form.getFieldValues();
                    var normalizedData = [];

                    // when only one item is in the form
                    if(typeof data["name"] == "string") {
                        data = {
                            name: [data["name"]],
                            type: [data["type"]],
                            value: [data["value"]]
                        };
                    }

                    if(data && data["name"] && data["name"].length > 0) {
                        for(var i=0; i<data["name"].length; i++) {

                            var listItem = {
                                name: data["name"][i],
                                value: data["value"][i],
                                type: data["type"][i]
                            }

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
                    if(this.hotspotMetaData && this.hotspotMetaData[id]) {
                        var data = this.hotspotMetaData[id];
                        for(var i=0; i<data.length; i++) {
                            addItem(data[i]["type"], data[i]);
                        }
                    }
                }.bind(this, id)
            }
        });

        var addItem = function (hotspotMetaDataWin, type, data) {

            var id = "item-" + uniqid();
            var valueField;

            if(!data || !data["name"]) {
                data = {
                    name: "",
                    value: ""
                };
            }

            if(type == "textfield") {
                valueField = {
                    xtype: "textfield",
                    name: "value",
                    fieldLabel: t("value"),
                    width: 400,
                    value: data["value"]
                };
            } else if(type == "textarea") {
                valueField = {
                    xtype: "textarea",
                    name: "value",
                    fieldLabel: t("value"),
                    width: 400,
                    value: data["value"]
                };
            } else if(type == "checkbox") {
                valueField = {
                    xtype: "checkbox",
                    name: "value",
                    fieldLabel: t("value"),
                    checked: data["value"]
                };
            } else if(type == "object" || type == "asset" || type == "document") {
                var textField = new Ext.form.TextField({
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 320,
                    listeners: {
                        render: this.addDataDropTarget.bind(this, type)
                    }
                });

                var items = [textField, {
                    xtype: "button",
                    iconCls: "pimcore_icon_edit",
                    handler: this.openElement.bind(this, textField, type)
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_delete"
                    ,
                    handler: this.empty.bind(this, textField)
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this, textField, type)
                }];

                valueField = new Ext.form.CompositeField({
                    items: items,
                    itemCls: "object_field"
                });

            } else {
                // no valid type
                return;
            }

            hotspotMetaDataWin.getComponent("form").add({
                xtype: "fieldset",
                style: "padding: 0;",
                bodyStyle: "padding: 5px;",
                itemId: id,
                items: [{
                    xtype: "hidden",
                    name: "type",
                    value: type
                },{
                    xtype: "textfield",
                    name: "name",
                    value: data["name"],
                    fieldLabel: t("name")
                }, valueField],
                tbar: ["->", {
                    iconCls: "pimcore_icon_delete",
                    handler: function (hotspotMetaDataWin) {
                        var form = hotspotMetaDataWin.getComponent("form");
                        form.remove(form.getComponent(id));
                        hotspotMetaDataWin.doLayout();
                    }.bind(this, hotspotMetaDataWin)
                }]
            });

            hotspotMetaDataWin.doLayout();
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
            if(data.node.attributes.elementType == type) {
                target.dom.value = data.node.attributes.path;
                return true;
            } else {
                return false;
            }
        }.bind(this, el);

        var over = function (target, dd, e, data) {
            if(data.node.attributes.elementType == type) {
                return Ext.dd.DropZone.prototype.dropAllowed;
            }
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        };

        if(typeof dndManager == "object") {
            // register at global DnD manager
            // in iframes, eg. document editmode
            dndManager.addDropTarget(el.getEl(), over, drop);
        } else {
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return el.getEl();
                },
                onNodeOver : over,
                onNodeDrop : drop
            });
        }
    },

    openSearchEditor: function (textfield, type) {
        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};
        var i;

        allowedTypes.push(type);
        if (type == "object") {
            allowedSubtypes.object = ["object","folder","variant"];
        }

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this, textfield), {
            type: allowedTypes,
            subtype: allowedSubtypes,
            specific: allowedSpecific
        });
    },

    addDataFromSelector: function (textfield, data) {
        if (data) {
            textfield.setValue(data.fullpath);
        }
    }


});
