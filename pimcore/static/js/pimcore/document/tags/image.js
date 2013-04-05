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

pimcore.registerNS("pimcore.document.tags.image");
pimcore.document.tags.image = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.datax = new Object();

        if (!options) {
            options = {};
        }

        this.options = options;

        this.originalDimensions = {
            width: this.options.width,
            height: this.options.height
        };

        // set width & height
        /*if(!this.options.width) {
         this.options.width = 100;
         }*/
        if (!this.options.height) {
            this.options.height = 100;
        }

        if (data) {
            this.datax = data;
        }

        this.setupWrapper();

        this.options.name = id + "_editable";
        this.element = new Ext.Panel(this.options);


        this.element.on("render", function (el) {

            // contextmenu
            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            el.getEl().setStyle({
                position: "relative"
            });

            // alt / title
            this.altBar = document.createElement("div");
            this.getBody().appendChild(this.altBar);

            this.altBar = Ext.get(this.altBar);
            this.altBar.addClass("pimcore_tag_image_alt");
            this.altBar.setStyle({
                opacity: 0.8,
                display: "none"
            });

            this.altInput = new Ext.form.TextField({
                name: "altText",
                width: this.options.width
            });
            this.altInput.render(this.altBar);

            if (this.datax.alt) {
                this.altInput.setValue(this.datax.alt);
            }

            if (this.options.hidetext == true) {
                this.altBar.setStyle({
                    display: "none",
                    visibility: "hidden"
                });
            }

            this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');

            this.getBody().addClass("pimcore_tag_image_empty");

        }.bind(this));

        this.element.render(id);


        // insert image
        if (this.datax) {
            this.updateImage();
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();

        if(this.datax.id) {
            menu.add(new Ext.menu.Item({
                text: t('select_specific_area_of_image'),
                iconCls: "pimcore_icon_image_region",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.openEditWindow();
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('add_marker_or_hotspots'),
                iconCls: "pimcore_icon_image_add_hotspot",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.openHotspotWindow();
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('empty'),
                iconCls: "pimcore_icon_delete",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.empty();

                }.bind(this)
            }));
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (item) {
                    item.parentMenu.destroy();
                    pimcore.helpers.openAsset(this.datax.id, "image");
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_fileexplorer",
                handler: function (item) {
                    item.parentMenu.destroy();
                    pimcore.helpers.selectElementInTree("asset", this.datax.id);
                }.bind(this)
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this) 
        }));

        menu.add(new Ext.menu.Item({
            text: t('upload'),
            iconCls: "pimcore_icon_upload_single",
            handler: function (item) {
                item.parentMenu.destroy();
                this.uploadDialog();
            }.bind(this)
        }));

        menu.showAt(e.getXY());
        e.stopEvent();
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.options["uploadPath"], "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if(data["id"] && data["type"] == "image") {
                    this.resetData();
                    this.datax.id = data["id"];

                    this.updateImage();
                    this.reload();
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    onNodeOver: function(target, dd, e, data) {
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if (data.node.attributes.type == "image") {
            this.resetData();
            this.datax.id = data.node.attributes.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    dndAllowed: function(data) {

        if(data.node.attributes.elementType!="asset" || data.node.attributes.type!="image"){
            return false;
        } else {
            return true;
        }

    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["image"]
            }
        });
    },
    
    addDataFromSelector: function (item) {        
        if(item) {
            this.resetData();
            this.datax.id = item.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    resetData: function () {
        this.datax = {
            id: null
        };
    },

    empty: function () {

        this.resetData();

        this.updateImage();
        this.getBody().addClass("pimcore_tag_image_empty");
        this.altBar.setStyle({
            display: "none"
        });
        this.reload();
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)
        var body = Ext.get(this.element.getEl().query(".x-panel-body")[0]);
        return body;
    },

    updateImage: function () {

        var path = "";
        var existingImage = this.getBody().dom.getElementsByTagName("img")[0];
        if (existingImage) {
            Ext.get(existingImage).remove();
        }

        if (!this.datax.id) {
            return;
        }


        if (!this.options["thumbnail"] && !this.originalDimensions["width"] && !this.originalDimensions["height"]) {
            path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.element.getEl().getWidth()
                            + "/aspectratio/true?" + Ext.urlEncode(this.datax);
        } else if (this.originalDimensions["width"]) {
            path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.originalDimensions["width"]
                            + "/aspectratio/true?" + Ext.urlEncode(this.datax);
        } else if (this.originalDimensions["height"]) {
            path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/height/"
                            + this.originalDimensions["height"] + "/aspectratio/true?" + Ext.urlEncode(this.datax);
        } else {
            if (typeof this.options.thumbnail == "string") {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/thumbnail/" + this.options.thumbnail
                            + "?" + Ext.urlEncode(this.datax);
            }
            else if (this.options.thumbnail.width || this.options.thumbnail.height) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/"
                            + this.options.thumbnail.width + "/height/" + this.options.thumbnail.height + "?"
                            + Ext.urlEncode(this.datax);
            }
        }

        var image = document.createElement("img");
        image.src = path;

        this.getBody().appendChild(image);

        // show alt input field
        this.altBar.setStyle({
            display: "block"
        });

        this.getBody().removeClass("pimcore_tag_image_empty");

        this.updateCounter = 0;
        this.updateDimensionsInterval = window.setInterval(this.updateDimensions.bind(this), 1000);
    },

    reload : function () {
        if (this.options.reload) {
            this.reloadDocument();
        }
    },

    updateDimensions: function () {

        var image = this.element.getEl().dom.getElementsByTagName("img")[0];
        if (!image) {
            return;
        }
        image = Ext.get(image);

        var width = image.getWidth();
        var height = image.getHeight();

        if (width > 1 && height > 1) {

            var dimensionError = false;
            if(typeof this.options.minWidth != "undefined") {
                if(width < this.options.minWidth) {
                    dimensionError = true;
                }
            }
            if(typeof this.options.minHeight != "undefined") {
                if(height < this.options.minHeight) {
                    dimensionError = true;
                }
            }

            if(dimensionError) {
                this.empty();
                clearInterval(this.updateDimensionsInterval);

                Ext.MessageBox.alert(t("error"), t("image_is_too_small"));

                return;
            }

            if (typeof this.originalDimensions.width == "undefined") {
                this.element.setWidth(width);
            }
            if (typeof this.originalDimensions.height == "undefined") {
                this.element.setHeight(height);
            }

            this.altInput.setWidth(width);

            // show alt input field
            this.altBar.setStyle({
                display: "block"
            });

            clearInterval(this.updateDimensionsInterval);
        }
        else {
            this.altBar.setStyle({
                display: "none"
            });
        }

        if (this.updateCounter > 20) {
            // only wait 20 seconds until image must be loaded
            clearInterval(this.updateDimensionsInterval);
        }

        this.updateCounter++;
    },

    openEditWindow: function() {

        var imageUrl = '/admin/asset/get-image-thumbnail/id/' + this.datax.id + '/width/500/height/400/contain/true';

        this.editWindow = new Ext.Window({
            width: 500,
            height: 400,
            modal: true,
            closeAction: "close",
            resizable: false,
            bodyStyle: "background: url(" + imageUrl + ") center center no-repeat;",
            bbar: ["->", {
                xtype: "button",
                iconCls: "pimcore_icon_apply",
                text: t("save"),
                handler: function () {

                    var originalWidth = this.editWindow.getInnerWidth();
                    var originalHeight = this.editWindow.getInnerHeight();

                    var dimensions = Ext.get("selector").getStyles("top","left","width","height");

                    var newWidth = intval(dimensions.width);
                    var newHeight = intval(dimensions.height);
                    var top = intval(dimensions.top);
                    var left = intval(dimensions.left);

                    this.datax.cropWidth = newWidth * 100 / originalWidth;
                    this.datax.cropHeight = newHeight * 100 / originalHeight;
                    this.datax.cropTop = top * 100 / originalHeight;
                    this.datax.cropLeft = left * 100 / originalWidth;
                    this.datax.cropPercent = true;
                    
                    this.resizer = null;
                    this.editWindow.close();

                    this.updateImage();
                }.bind(this)
            }],
            html: '<img id="selectorImage" src="' + imageUrl + '" />' +
                '<div id="selector" style="cursor:move; position: absolute; top: 10px; left: 10px;z-index:9000;"></div>'
        });

        this.editWindowInitCount = 0;

        this.editWindow.on("afterrender", function ( ){
            this.editWindowInterval = window.setInterval(function () {
                var el = Ext.get("selectorImage");
                var imageWidth = el.getWidth();
                var imageHeight = el.getHeight();
                
                if(el) {
                    if(el.getWidth() > 30) {
                        clearInterval(this.editWindowInterval);
                        this.editWindowInitCount = 0;
                        
                        this.editWindow.setSize(imageWidth + 14, imageHeight + 32 + 27);
                        Ext.get("selectorImage").remove();

                        this.resizer = new Ext.Resizable('selector', {
                            pinned:true,
                            minWidth:50,
                            minHeight: 50,
                            preserveRatio: false,
                            dynamic:true,
                            handles: 'all',
                            draggable:true,
                            width: 100,
                            height: 100
                        });

                        if(this.datax.cropPercent) {
                            Ext.get("selector").applyStyles({
                                width: (imageWidth * (this.datax.cropWidth / 100)) + "px",
                                height: (imageHeight * (this.datax.cropHeight / 100)) + "px",
                                top: (imageHeight * (this.datax.cropTop / 100)) + "px",
                                left: (imageWidth * (this.datax.cropLeft / 100)) + "px"
                            });
                        }
                        
                        return;
                        
                    } else if (this.editWindowInitCount > 60) {
                        // if more than 30 secs cancel and close the window
                        this.resizer = null;
                        this.editWindow.close();
                    }

                    this.editWindowInitCount++;
                }
            }.bind(this), 500);
            
        }.bind(this));

        this.editWindow.show();
    },

    openHotspotWindow: function() {

        var imageUrl = '/admin/asset/get-image-thumbnail/id/' + this.datax.id + '/width/500/height/400/contain/true';

        this.hotspotStore = [];
        this.hotspotMetaData = {};

        this.hotspotWindow = new Ext.Window({
            width: 500,
            height: 400,
            modal: true,
            closeAction: "close",
            resizable: false,
            bodyStyle: "background: url(" + imageUrl + ") center center no-repeat;",
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
                            var metaData = [];
                            if(this.hotspotMetaData && this.hotspotMetaData[el["id"]]) {
                                metaData = this.hotspotMetaData[el["id"]];
                            }

                            if(el.type == "marker") {
                                dataMarker.push({
                                    top:(intval(dimensions.top)+35) * 100 / originalHeight, //the marker el is 35px high
                                    left:(intval(dimensions.left)+12) * 100 / originalWidth,//the marker el is 25px wide
                                    data: metaData
                                });
                            } else if (el.type == "hotspot") {
                                dataHotspot.push({
                                    top: intval(dimensions.top) * 100 / originalHeight,
                                    left:  intval(dimensions.left) * 100 / originalWidth,
                                    width: intval(dimensions.width) * 100 / originalWidth,
                                    height: intval(dimensions.height) * 100 / originalHeight,
                                    data: metaData
                                });
                            }
                        }
                    }

                    this.datax.hotspots = dataHotspot;
                    this.datax.marker = dataMarker;

                    this.hotspotWindow.close();
                }.bind(this)
            }],
            html: '<img id="hotspotImage" src="' + imageUrl + '" />'
        });

        this.hotspotWindowInitCount = 0;

        this.hotspotWindow.on("afterrender", function ( ){
            this.hotspotWindowInterval = window.setInterval(function () {
                var el = Ext.get("hotspotImage");
                var imageWidth = el.getWidth();
                var imageHeight = el.getHeight();
                var i;
                var elId;

                if(el) {
                    if(el.getWidth() > 30) {
                        clearInterval(this.hotspotWindowInterval);
                        this.hotspotWindowInitCount = 0;

                        this.hotspotWindow.setSize(imageWidth + 14, imageHeight + 32 + 27);
                        Ext.get("hotspotImage").remove();

                        if(this.datax && this.datax["hotspots"]) {
                            for(i=0; i<this.datax.hotspots.length; i++) {
                                elId = this.addHotspot(this.datax.hotspots[i]);
                                if(this.datax.hotspots[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.datax.hotspots[i]["data"];
                                }
                            }
                        }

                        if(this.datax && this.datax["marker"]) {
                            for(i=0; i<this.datax.marker.length; i++) {
                                elId = this.addMarker(this.datax.marker[i]);
                                if(this.datax.marker[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.datax.marker[i]["data"];
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
                width: (originalHeight * (config["width"]/100)) + "px",
                height: (originalHeight * (config["height"]/100)) + "px"
            });
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
            modal: true,
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
            }],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function (id) {

                    var data = hotspotMetaDataWin.getComponent("form").getForm().getFieldValues();
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
                            normalizedData.push({
                                name: data["name"][i],
                                value: data["value"][i],
                                type: data["type"][i]
                            });
                        }
                    }

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
            } else if(type == "object") {
                valueField = {
                    xtype: "textfield",
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 400,
                    listeners: {
                        render: function (el) {
                            // register at global DnD manager
                            dndManager.addDropTarget(el.getEl(), function (target, dd, e, data) {
                                if(data.node.attributes.elementType == "object") {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                                return Ext.dd.DropZone.prototype.dropNotAllowed;
                            }, function (target, dd, e, data) {
                                if(data.node.attributes.elementType == "object") {
                                    target.dom.value = data.node.attributes.path;
                                    return true;
                                } else {
                                    return false;
                                }
                            }.bind(this));
                        }.bind(this)
                    }
                };
            } else if(type == "asset") {
                valueField = {
                    xtype: "textfield",
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 400,
                    listeners: {
                        render: function (el) {
                            // register at global DnD manager
                            dndManager.addDropTarget(el.getEl(), function (target, dd, e, data) {
                                if(data.node.attributes.elementType == "asset") {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                                return Ext.dd.DropZone.prototype.dropNotAllowed;
                            }, function (target, dd, e, data) {
                                if(data.node.attributes.elementType == "asset") {
                                    target.dom.value = data.node.attributes.path;
                                    return true;
                                } else {
                                    return false;
                                }
                            }.bind(this));
                        }.bind(this)
                    }
                };
            } else if(type == "document") {
                valueField = {
                    xtype: "textfield",
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 400,
                    listeners: {
                        render: function (el) {
                            // register at global DnD manager
                            dndManager.addDropTarget(el.getEl(), function (target, dd, e, data) {
                                if(data.node.attributes.elementType == "document") {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                                return Ext.dd.DropZone.prototype.dropNotAllowed;
                            }, function (target, dd, e, data) {
                                if(data.node.attributes.elementType == "document") {
                                    target.dom.value = data.node.attributes.path;
                                    return true;
                                } else {
                                    return false;
                                }
                            }.bind(this));
                        }.bind(this)
                    }
                };
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

    getValue: function () {

        // alt alt value
        this.datax.alt = this.altInput.getValue();

        return this.datax;
    },

    getType: function () {
        return "image";
    }
});