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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.document.tags.pdf");
pimcore.document.tags.pdf = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.data = {};

        this.options = this.parseOptions(options);


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
                    pimcore.helpers.openAsset(this.data.id, "document");
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
                if(data["id"] && data["type"] == "document") {
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

        path = "/admin/asset/get-document-thumbnail/id/" + this.data.id + "/width/" + this.element.getEl().getWidth()
                        + "/aspectratio/true?" + Ext.urlEncode(this.data);

        var image = document.createElement("img");
        image.src = path;

        this.getBody().appendChild(image);
        this.getBody().removeClass("pimcore_tag_image_empty");

        this.updateCounter = 0;
        this.updateDimensionsInterval = window.setInterval(this.updateDimensions.bind(this), 1000);
    },

    reload : function () {
        this.reloadDocument();
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

        var thumbUrl = "";
        var pages = [];

        this.hotspotStore = {};
        this.hotspotMetaData = {};

        if(this.data["hotspots"]) {
            this.hotspotStore = this.data["hotspots"];
        }

        this.currentPage = null;

        for(var i=1; i<=this.data.pageCount; i++) {
            thumbUrl = "/admin/asset/get-document-thumbnail/id/"
                + this.data.id + "/width/400/height/400/contain/true/page/" + i;

            pages.push({
                style: "margin-bottom: 10px; text-align: center; cursor:pointer; ",
                bodyStyle: "min-height: 150px;",
                html: '<span id="' + this.getName() + '-page-' + i + '" style="font-size:35px; line-height: 150px;" data-src="' + thumbUrl + '">' + i + '</span>', // blank gif image
                listeners: {
                    afterrender: function (page, el) {
                        // unfortunately the panel element has no click event, so we have to add it to the image
                        // after the panel was rendered
                        var body = Ext.get(el.body);
                        body.on("click", this.hotspotEditPage.bind(this, page));
                    }.bind(this, i)
                }
            });
        }

        var pagesContainer = new Ext.Panel({
            width: 150,
            region: "west",
            autoScroll: true,
            bodyStyle: "padding: 10px;",
            items: pages
        });

        var loadingInterval = window.setInterval(function () {

            if(!pagesContainer || !pagesContainer.body || !pagesContainer.body.dom) {
                clearInterval(loadingInterval);
            } else {
                var el;
                var scroll = pagesContainer.body.getScroll();
                var startPage = Math.floor(scroll.top / 162); // 162 is the height of one thumbnail incl. border and margin
                for(var i=startPage; i<(startPage+5); i++) {
                    el = Ext.get(this.getName() + "-page-" + i);
                    if(el) {
                        el.parent().update('<img src="' + el.getAttribute("data-src") + '" height="150" />');
                    }
                }
            }
        }.bind(this), 1000);

        this.hotspotWindow = new Ext.Window({
            width: 700,
            height: 510,
            modal: true,
            closeAction: "close",
            resizable: false,
            layout: "border",
            items: [pagesContainer, {
                region: "center",
                layout: "fit",
                itemId: "pageContainer"
            }],
            bbar: ["->", {
                xtype: "button",
                iconCls: "pimcore_icon_apply",
                text: t("save"),
                handler: function () {
                    this.saveCurrentPage();
                    this.data["hotspots"] = this.hotspotStore;
                    this.hotspotWindow.close();
                }.bind(this)
            }]
        });

        this.hotspotWindow.show();
    },

    hotspotEditPage: function (page) {
        this.saveCurrentPage();

        this.currentPage = page;

        var pageContainer = this.hotspotWindow.getComponent("pageContainer");
        pageContainer.removeAll();

        var thumbUrl = "/admin/asset/get-document-thumbnail/id/"
                        + this.data.id +
            "/width/400/height/400/contain/true/page/" + page;

        var page = new Ext.Panel({
            border: false,
            bodyStyle: "background: #e5e5e5; ",
            html: '<div style="margin:0 auto; position:relative; overflow: hidden;" ' +
                'class="page"><img src="' + thumbUrl + '" /></div>',
            tbar: [{
                xtype: "button",
                text: t("add_hotspot"),
                iconCls: "pimcore_icon_add_hotspot",
                handler: this.addHotspot.bind(this)
            }],
            listeners: {
                afterrender: function (el) {
                    var el = el.body;
                    var checks = 0;
                    var detailInterval = window.setInterval(function () {

                        try {
                            checks++;

                            var div = Ext.get(el.query(".page")[0]);
                            var img = Ext.get(el.query("img")[0]);

                            if((img.getHeight() > 100 && img.getWidth() > 100) || checks > 300 || !div || !img) {
                                window.clearInterval(detailInterval);
                            }

                            if(img.getHeight() > 100 && img.getWidth() > 100) {
                                div.applyStyles({
                                    width: img.getWidth() + "px",
                                    height: img.getHeight() + "px",
                                    visibility: "visible",
                                    "margin-left": ((el.getWidth()-img.getWidth())/2) + "px",
                                    "margin-top": ((el.getHeight()-img.getHeight())/2) + "px"
                                });
                            }
                        } catch (e) {
                            // stop the timer when an error occours
                            window.clearInterval(detailInterval);
                        }
                    }, 200);

                    // add hotspots
                    var hotspots = this.hotspotStore[this.currentPage];
                    if(hotspots) {
                        for(var i=0; i<hotspots.length; i++) {
                            this.addHotspot(hotspots[i]);
                        }
                    }
                }.bind(this)
            }
        });

        pageContainer.add(page);

        pageContainer.doLayout();
    },

    addHotspot: function (config) {
        var hotspotId = "pdf-hotspot-" + uniqid();

        var pageContainerDiv = Ext.get(this.hotspotWindow.getComponent("pageContainer").body.query(".page")[0]);
        pageContainerDiv.insertHtml("beforeEnd", '<div id="' + hotspotId + '" class="pimcore_pdf_hotspot"></div>');

        var hotspotEl = Ext.get(hotspotId);

        // default dimensions
        hotspotEl.applyStyles({
            position: "absolute",
            cursor: "pointer",
            top: 0,
            left: 0,
            width: "50px",
            height: "50px"
        });

        if(typeof config == "object" && config["top"]) {
            var imgEl = Ext.get(this.hotspotWindow.getComponent("pageContainer").body.query("img")[0]);
            var originalWidth = imgEl.getWidth();
            var originalHeight = imgEl.getHeight();

            hotspotEl.applyStyles({
                top: (originalHeight * (config["top"]/100)) + "px",
                left: (originalWidth * (config["left"]/100)) + "px",
                width: (originalWidth * (config["width"]/100)) + "px",
                height: (originalHeight * (config["height"]/100)) + "px"
            });

            if(config["data"]) {
                this.hotspotMetaData[hotspotId] = config["data"];
            }
        }

        hotspotEl.on("contextmenu", function (id, e) {
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
        }.bind(this, hotspotId));


        var resizer = new Ext.Resizable(hotspotId, {
            pinned:true,
            minWidth:20,
            minHeight: 20,
            preserveRatio: false,
            dynamic:true,
            handles: 'all',
            draggable:true
        });


        return hotspotId;
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
                    text: t("link"),
                    iconCls: "pimcore_icon_input",
                    handler: function () {
                        addItem("link");
                    }
                },"-",{
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
            } else if(type == "document" || type == "link") {

                if(type == "link") {
                    data["name"] = "link";
                }

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

    saveCurrentPage: function () {

        if(this.currentPage) {
            var hotspots = this.hotspotWindow.getComponent("pageContainer").body.query(".pimcore_pdf_hotspot");
            var hotspot = null;
            var metaData = null;

            var imgEl = Ext.get(this.hotspotWindow.getComponent("pageContainer").body.query("img")[0]);
            var originalWidth = imgEl.getWidth();
            var originalHeight = imgEl.getHeight();

            this.hotspotStore[this.currentPage] = [];

            for(var i=0; i<hotspots.length; i++) {
                hotspot = Ext.get(hotspots[i]);

                var dimensions = hotspot.getStyles("top","left","width","height");

                metaData = null;
                if(this.hotspotMetaData[hotspot.getAttribute("id")]) {
                    metaData = this.hotspotMetaData[hotspot.getAttribute("id")];
                }

                this.hotspotStore[this.currentPage].push({
                    top: intval(dimensions.top) * 100 / originalHeight,
                    left:  intval(dimensions.left) * 100 / originalWidth,
                    width: intval(dimensions.width) * 100 / originalWidth,
                    height: intval(dimensions.height) * 100 / originalHeight,
                    data: metaData
                });
            }

            if(this.hotspotStore[this.currentPage].length < 1) {
                delete this.hotspotStore[this.currentPage];
            }
        }
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "pdf" +
            "";
    }
});