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

pimcore.registerNS("pimcore.document.tags.image");
pimcore.document.tags.image = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.datax = new Object();
        this.options = this.parseOptions(options);

        this.options = options;
        this.options.style = '';

        if (!this.options["height"]) {
            if (this.options["defautHeight"]){
                this.options.style += (" min-height:" + this.options["defautHeight"] + "px");
            }else{
                this.options.style += (" min-height:100px");
            }
        }

        this.originalDimensions = {
            width: this.options.width,
            height: this.options.height
        };

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

            // add additional drop targets
            if (this.options["dropClass"]) {
                var extra_drop_targets = Ext.query('.' + this.options.dropClass);

                for (var i = 0; i < extra_drop_targets.length; ++i) {
                    var drop_el = Ext.get(extra_drop_targets[i]);
                    dndManager.addDropTarget(drop_el, this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
                    drop_el.on("contextmenu", this.onContextMenu.bind(this));
                }
            }

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
            cls: "pimcore_inline_upload",
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


        if (!this.options["thumbnail"]) {
            if(!this.originalDimensions["width"] && !this.originalDimensions["height"]) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.element.getEl().getWidth()
                    + "/aspectratio/true?" + Ext.urlEncode(this.datax);
            } else if (this.originalDimensions["width"]) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.originalDimensions["width"]
                    + "/aspectratio/true?" + Ext.urlEncode(this.datax);
            } else if (this.originalDimensions["height"]) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/height/"
                + this.originalDimensions["height"] + "/aspectratio/true?" + Ext.urlEncode(this.datax);
            }
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

            if(Ext.isIE && width==28 && height==30){
                //IE missing image placeholder
                return;
            }

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
        var editor = pimcore.helpers.openImageCropper(this.datax.id, this.datax, function (data) {
            this.datax.cropWidth = data.cropWidth;
            this.datax.cropHeight = data.cropHeight;
            this.datax.cropTop = data.cropTop;
            this.datax.cropLeft = data.cropLeft;
            this.datax.cropPercent = true;

            this.updateImage();
        }.bind(this));
        editor.open(true);
    },

    openHotspotWindow: function() {
        var editor = pimcore.helpers.openImageHotspotMarkerEditor(this.datax.id, this.datax, function (data) {
            this.datax["hotspots"] = data["hotspots"];
            this.datax["marker"] = data["marker"];
        }.bind(this));
        editor.open(true);
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