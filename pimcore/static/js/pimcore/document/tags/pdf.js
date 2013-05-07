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

pimcore.registerNS("pimcore.document.tags.pdf");
pimcore.document.tags.pdf = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.data = {};

        if (!options) {
            options = {};
        }

        this.options = options;


        // set width
        if (!this.options["height"]) {
            this.options.height = 100;
        }

        if (data) {
            this.data = data;
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

            this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
            this.getBody().addClass("pimcore_tag_image_empty");
        }.bind(this));

        this.element.render(id);


        // insert image
        if (this.data) {
            this.updateImage();
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();

        if(this.data.id) {
            menu.add(new Ext.menu.Item({
                text: t('add_hotspots'),
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
                    pimcore.helpers.openAsset(this.data.id, "image");
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_fileexplorer",
                handler: function (item) {
                    item.parentMenu.destroy();
                    pimcore.helpers.selectElementInTree("asset", this.data.id);
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
                    this.data.id = data["id"];

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

        if (data.node.attributes.type == "document") {
            this.resetData();
            this.data.id = data.node.attributes.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    dndAllowed: function(data) {

        if(data.node.attributes.elementType!="asset" || data.node.attributes.type!="document"){
            return false;
        } else {
            return true;
        }

    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["document"]
            }
        });
    },
    
    addDataFromSelector: function (item) {        
        if(item) {
            this.resetData();
            this.data.id = item.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    resetData: function () {
        this.data = {
            id: null
        };
    },

    empty: function () {

        this.resetData();

        this.updateImage();
        this.getBody().addClass("pimcore_tag_image_empty");
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

        if (!this.data.id) {
            return;
        }

        path = "/admin/asset/get-image-thumbnail/id/6624/width/" + this.element.getEl().getWidth()
                        + "/aspectratio/true?" + Ext.urlEncode(this.data);

        var image = document.createElement("img");
        image.src = path;

        this.getBody().appendChild(image);
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
            this.element.setWidth(width);
            this.element.setHeight(height);

            clearInterval(this.updateDimensionsInterval);
        }

        if (this.updateCounter > 20) {
            // only wait 20 seconds until image must be loaded
            clearInterval(this.updateDimensionsInterval);
        }

        this.updateCounter++;
    },

    openHotspotWindow: function() {

        var imageUrl = '/admin/asset/get-image-thumbnail/id/' + this.data.id + '/width/500/height/400/contain/true';

        this.hotspotStore = [];
        this.hotspotMetaData = {};

        this.hotspotWindow = new Ext.Window({
            width: 500,
            height: 400,
            modal: true,
            closeAction: "close",
            resizable: false,
            bodyStyle: "background: url(" + imageUrl + ") center center no-repeat; position:relative; ",
            tbar: [{
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

                            dataHotspot.push({
                                top: intval(dimensions.top) * 100 / originalHeight,
                                left:  intval(dimensions.left) * 100 / originalWidth,
                                width: intval(dimensions.width) * 100 / originalWidth,
                                height: intval(dimensions.height) * 100 / originalHeight,
                                data: metaData
                            });
                        }
                    }

                    this.data.hotspots = dataHotspot;
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

                        this.hotspotWindow.setSize(imageWidth + 14, imageHeight + 32 + 27 + 27); // 27px is the toolbar
                        Ext.get("hotspotImage").remove();

                        if(this.data && this.data["hotspots"]) {
                            for(i=0; i<this.data.hotspots.length; i++) {
                                elId = this.addHotspot(this.data.hotspots[i]);
                                if(this.data.hotspots[i]["data"]) {
                                    this.hotspotMetaData[elId] = this.data.hotspots[i]["data"];
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

        this.addHotspotContextMenu(hotspotId, hotspotEl);

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

    addHotspotContextMenu: function (id, el) {
        el.on("contextmenu", function (id, e) {
            var menu = new Ext.menu.Menu();

            menu.add(new Ext.menu.Item({
                text: t("add_data"),
                iconCls: "pimcore_icon_add_data",
                handler: function (id, item) {
                    item.parentMenu.destroy();

                    this.editHotspotData(id);
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

    editHotspotData: function (id) {
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
        return this.data;
    },

    getType: function () {
        return "pdf" +
            "";
    }
});