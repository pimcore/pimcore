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


            var domElement = el.getEl().dom;
            domElement.dndOver = false;

            domElement.reference = this;

            dndZones.push(domElement);
            el.getEl().on("mouseover", function (e) {
                this.dndOver = true;
            }.bind(domElement));
            el.getEl().on("mouseout", function (e) {
                this.dndOver = false;
            }.bind(domElement));

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
                iconCls: "pimcore_icon_edit",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.openEditWindow();
                }.bind(this)
            }));
        }

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
        } else return true;

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
        // get the id from the body element of the panel because there is no method to set body's html (only in configure)
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
            path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.element.getEl().getWidth() + "/aspectratio/true?" + Ext.urlEncode(this.datax);
        } else if (this.originalDimensions["width"]) {
            path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.originalDimensions["width"] + "/aspectratio/true?" + Ext.urlEncode(this.datax);
        } else if (this.originalDimensions["height"]) {
            path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/height/" + this.originalDimensions["height"] + "/aspectratio/true?" + Ext.urlEncode(this.datax);
        } else {
            if (typeof this.options.thumbnail == "string") {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/thumbnail/" + this.options.thumbnail + "?" + Ext.urlEncode(this.datax);
            }
            else if (this.options.thumbnail.width || this.options.thumbnail.height) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.options.thumbnail.width + "/height/" + this.options.thumbnail.height + "?" + Ext.urlEncode(this.datax);
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
            html: '<img id="selectorImage" src="' + imageUrl + '" /><div id="selector" style="cursor:move; position: absolute; top: 10px; left: 10px;z-index:9000;"></div>'
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

    getValue: function () {

        // alt alt value
        this.datax.alt = this.altInput.getValue();

        return this.datax;
    },

    getType: function () {
        return "image";
    }
});