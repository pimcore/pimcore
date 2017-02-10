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

pimcore.registerNS("pimcore.document.tags.image");
pimcore.document.tags.image = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.datax = {};
        this.options = this.parseOptions(options);

        this.originalDimensions = {
            width: this.options.width,
            height: this.options.height
        };

        if (data) {
            this.datax = data;
        }

        this.setupWrapper();

        this.element = Ext.get(id);

        if (this.options["width"]) {
            this.element.setStyle("width", this.options["width"] + "px");
        }

        if (!this.options["height"]) {
            if (this.options["defaultHeight"]){
                this.element.setStyle("min-height", this.options["defaultHeight"] + "px");
            }
        } else {
            this.element.setStyle("height", this.options["height"] + "px");
        }

        // contextmenu
        this.element.on("contextmenu", this.onContextMenu.bind(this));

        // register at global DnD manager
        if (typeof dndManager != 'undefined') {
            dndManager.addDropTarget(this.element, this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
        }

        // tooltip
        if(this.options["title"]) {
            new Ext.ToolTip({
                target: this.element,
                showDelay: 100,
                hideDelay: 0,
                trackMouse: true,
                html: this.options["title"]
            });
        }

        // alt / title
        this.altBar = document.createElement("div");
        this.element.appendChild(this.altBar);

        this.altBar = Ext.get(this.altBar);
        this.altBar.addCls("pimcore_tag_image_alt");
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

        this.element.insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');

        this.element.addCls("pimcore_tag_image_empty");

        // add additional drop targets
        if (this.options["dropClass"]) {
            var extra_drop_targets = Ext.query('.' + this.options.dropClass);

            for (var i = 0; i < extra_drop_targets.length; ++i) {
                var drop_el = Ext.get(extra_drop_targets[i]);
                dndManager.addDropTarget(drop_el, this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
                drop_el.on("contextmenu", this.onContextMenu.bind(this));
            }
        }


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
                iconCls: "pimcore_icon_image pimcore_icon_overlay_edit",
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

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function (item) {
                        item.parentMenu.destroy();
                        pimcore.treenodelocator.showInTree(this.datax.id, "asset");
                    }.bind(this)
                }));
            }
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
            iconCls: "pimcore_icon_upload",
            handler: function (item) {
                item.parentMenu.destroy();
                this.uploadDialog();
            }.bind(this)
        }));

        menu.showAt(e.pageX, e.pageY);
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
        var record = data.records[0];
        data = record.data;

        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];
        data = record.data;

        if (data.type == "image") {
            this.resetData();
            this.datax.id = data.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    dndAllowed: function(data) {

        if(data.elementType!="asset" || data.type!="image"){
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
        }, {
                context: this.getContext()
            }
        );
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
        this.element.addCls("pimcore_tag_image_empty");
        this.altBar.setStyle({
            display: "none"
        });
        this.reload();
    },
    
    updateImage: function () {

        var path = "";
        var existingImage = this.element.dom.getElementsByTagName("img")[0];
        if (existingImage) {
            Ext.get(existingImage).remove();
        }

        if (!this.datax.id) {
            return;
        }


        if (!this.options["thumbnail"]) {
            if(!this.originalDimensions["width"] && !this.originalDimensions["height"]) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/" + this.element.getWidth()
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
                    + "?" + Ext.urlEncode(this.datax) + "&pimcore_editmode=1";
            }
            else if (this.options.thumbnail.width || this.options.thumbnail.height) {
                path = "/admin/asset/get-image-thumbnail/id/" + this.datax.id + "/width/"
                    + this.options.thumbnail.width + "/height/" + this.options.thumbnail.height + "?"
                    + Ext.urlEncode(this.datax);
            }
        }

        var image = document.createElement("img");
        image.src = path;

        this.element.appendChild(image);

        // show alt input field
        this.altBar.setStyle({
            display: "block"
        });

        this.element.removeCls("pimcore_tag_image_empty");

        this.updateCounter = 0;
        this.updateDimensionsInterval = window.setInterval(this.updateDimensions.bind(this), 1000);
    },

    reload : function () {
        if (this.options.reload) {
            this.reloadDocument();
        }
    },

    updateDimensions: function () {

        var image = this.element.dom.getElementsByTagName("img")[0];
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
            this.datax.cropPercent = (undefined !== data.cropPercent) ? data.cropPercent : true;

            this.updateImage();
        }.bind(this));
        editor.open(true);
    },

    openHotspotWindow: function() {
        var editor = pimcore.helpers.openImageHotspotMarkerEditor(this.datax.id, this.datax, function (data) {
            this.datax["hotspots"] = data["hotspots"];
            this.datax["marker"] = data["marker"];
        }.bind(this));
        editor.open(false);
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