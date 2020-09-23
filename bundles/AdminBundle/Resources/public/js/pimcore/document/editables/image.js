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

pimcore.registerNS("pimcore.document.editables.image");
pimcore.document.editables.image = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.datax = {};
        this.inherited = inherited;
        this.config = this.parseConfig(config);

        this.originalDimensions = {
            width: this.config.width,
            height: this.config.height
        };

        if (data) {
            this.datax = data;
        }
    },

    render: function () {
        this.setupWrapper();

        this.element = Ext.get(this.id);

        if (this.config["width"]) {
            this.element.setStyle("width", this.config["width"] + "px");
        }

        if (!this.config["height"]) {
            if (this.config["defaultHeight"]){
                this.element.setStyle("min-height", this.config["defaultHeight"] + "px");
            }
        } else {
            this.element.setStyle("height", this.config["height"] + "px");
        }

        // contextmenu
        this.element.on("contextmenu", this.onContextMenu.bind(this));

        // register at global DnD manager
        if (typeof dndManager != 'undefined') {
            dndManager.addDropTarget(this.element, this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
        }

        // tooltip
        if(this.config["title"]) {
            new Ext.ToolTip({
                target: this.element,
                showDelay: 100,
                hideDelay: 0,
                trackMouse: true,
                html: this.config["title"]
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
            width: this.config.width
        });
        this.altInput.render(this.altBar);

        if (this.datax.alt) {
            this.altInput.setValue(this.datax.alt);
        }

        if (this.config.hidetext === true) {
            this.altBar.setStyle({
                display: "none",
                visibility: "hidden"
            });
        }

        // add additional drop targets
        if (this.config["dropClass"]) {
            var extra_drop_targets = Ext.query('.' + this.config.dropClass);

            for (var i = 0; i < extra_drop_targets.length; ++i) {
                var drop_el = Ext.get(extra_drop_targets[i]);
                dndManager.addDropTarget(drop_el, this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
                drop_el.on("contextmenu", this.onContextMenu.bind(this));
            }
        }

        if(this.config["disableInlineUpload"] !== true) {
            this.element.insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget_upload"></div>');
            this.element.addCls("pimcore_tag_image_empty");
            pimcore.helpers.registerAssetDnDSingleUpload(this.element.dom, this.config["uploadPath"], 'path', function (e) {
                if (e['asset']['type'] === "image" && !this.inherited) {
                    this.resetData();
                    this.datax.id = e['asset']['id'];

                    this.updateImage();
                    this.reload();

                    return true;
                } else {
                    pimcore.helpers.showNotification(t("error"), t('unsupported_filetype'), "error");
                }
            }.bind(this), null, this.getContext());
        } else {
            this.element.insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
            this.element.addCls("pimcore_tag_image_no_upload_empty");
        }

        // insert image
        if (this.datax) {
            this.updateImage();
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();

        if(this.datax.id) {

            if(this.config['focal_point_context_menu_item']) {
                menu.add(new Ext.menu.Item({
                    text: t('set_focal_point'),
                    iconCls: "pimcore_icon_focal_point",
                    handler: function (item) {
                        pimcore.helpers.openAsset(this.datax.id, 'image');
                    }.bind(this)
                }));
            }

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

        if(this.config["disableInlineUpload"] !== true) {
            menu.add(new Ext.menu.Item({
                text: t('upload'),
                cls: "pimcore_inline_upload",
                iconCls: "pimcore_icon_upload",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.uploadDialog();
                }.bind(this)
            }));
        }

        menu.showAt(e.pageX, e.pageY);
        e.stopEvent();
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.config["uploadPath"], "path", function (res) {
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
        if (data.records.length === 1 && this.dndAllowed(data.records[0].data) && !this.inherited) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        data = data.records[0].data;

        if (data.type === "image" && this.dndAllowed(data) && !this.inherited) {
            this.resetData();
            this.datax.id = data.id;

            this.updateImage();
            this.reload();

            return true;
        }

        return false;
    },

    dndAllowed: function(data) {

        if(data.elementType !== "asset" || data.type !== "image"){
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

    getThumbnailConfig: function(additionalConfig) {
        let merged = Ext.merge(this.datax, additionalConfig);
        merged = Ext.clone(merged);
        delete merged["hotspots"];
        delete merged["path"];
        return merged;

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


        if (!this.config["thumbnail"]) {
            if(!this.originalDimensions["width"] && !this.originalDimensions["height"]) {
                path = Routing.generate('pimcore_admin_asset_getimagethumbnail', this.getThumbnailConfig({
                    'width': this.element.getWidth(),
                    'aspectratio': true
                }));
            } else if (this.originalDimensions["width"]) {
                path = Routing.generate('pimcore_admin_asset_getimagethumbnail', this.getThumbnailConfig({
                    'width': this.originalDimensions["width"],
                    'aspectratio': true
                }));
            } else if (this.originalDimensions["height"]) {
                path = Routing.generate('pimcore_admin_asset_getimagethumbnail', this.getThumbnailConfig({
                    'height': this.originalDimensions["height"],
                    'aspectratio': true
                }));
            }
        } else if (typeof this.config.thumbnail == "string" || typeof this.config.thumbnail == "object") {
                path = Routing.generate('pimcore_admin_asset_getimagethumbnail', this.getThumbnailConfig({
                    'height': this.originalDimensions["height"],
                    'thumbnail': this.config.thumbnail,
                    'pimcore_editmode': '1'
                }));
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
        if (this.config.reload) {
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
            if(typeof this.config.minWidth != "undefined") {
                if(width < this.config.minWidth) {
                    dimensionError = true;
                }
            }
            if(typeof this.config.minHeight != "undefined") {
                if(height < this.config.minHeight) {
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

        var config = {};
        if(this.config["ratioX"] && this.config["ratioY"]) {
            config["ratioX"] = this.config["ratioX"];
            config["ratioY"] = this.config["ratioY"];
        }

        var editor = pimcore.helpers.openImageCropper(this.datax.id, this.datax, function (data) {
            this.datax.cropWidth = data.cropWidth;
            this.datax.cropHeight = data.cropHeight;
            this.datax.cropTop = data.cropTop;
            this.datax.cropLeft = data.cropLeft;
            this.datax.cropPercent = (undefined !== data.cropPercent) ? data.cropPercent : true;

            this.updateImage();
        }.bind(this), config);
        editor.open(true);
    },

    openHotspotWindow: function() {
        var editor = pimcore.helpers.openImageHotspotMarkerEditor(
            this.datax.id,
            this.datax,
            function (data) {
                this.datax["hotspots"] = data["hotspots"];
                this.datax["marker"] = data["marker"];
            }.bind(this),
            {
                crop: {
                    cropWidth: this.datax.cropWidth,
                    cropHeight: this.datax.cropHeight,
                    cropTop: this.datax.cropTop,
                    cropLeft: this.datax.cropLeft,
                    cropPercent: this.datax.cropPercent
                },
                predefinedDataTemplates : this.config.predefinedDataTemplates
            }

        );
        editor.open(false);
    },

    getValue: function () {

        // alt alt value
        if(this.altInput) {
            this.datax.alt = this.altInput.getValue();
        }

        return this.datax;
    },

    getType: function () {
        return "image";
    }
});
