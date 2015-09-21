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

pimcore.registerNS("pimcore.object.tags.hotspotimage");
pimcore.object.tags.hotspotimage = Class.create(pimcore.object.tags.image, {

    type: "hotspotimage",
    data: null,

    marginTop: 10,
    marginLeft: 8,
    fileinfo: null,
    previewItems: [],

    initialize: function (data, fieldConfig) {
        this.hotspots = [];
        this.marker = [];
        this.crop = [];

        this.data = null;
        if (data) {
            this.data = data.image;
            this.hotspots = data.hotspots;
            this.marker = data.marker;
            this.crop = data.crop;
        }
        this.fieldConfig = fieldConfig;
    },


    getGridColumnConfig: function(field) {

        return {header: ts(field.label), width: 100, sortable: false, dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.css += " grid_value_inherited";
                }

                if (value && value.id) {
                    return '<img src="/admin/asset/get-image-thumbnail/id/' + value.id
                        + '/width/88/height/88/frame/true" />';
                }
            }.bind(this, field.key)};
    },

    getLayoutEdit: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 100;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 100;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            cls: "object_field",
            tbar: [{
                xtype: "tbspacer",
                width: 20,
                height: 16,
                cls: "pimcore_icon_droptarget"
            },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.fieldConfig.title + "</b>"
                },"->",{
                    xtype: "button",
                    tooltip: t("crop"),
                    iconCls: "pimcore_icon_image_region",
                    handler: this.openCropWindow.bind(this)
                },{
                    xtype: "button",
                    tooltip: t("add_marker_or_hotspots"),
                    iconCls: "pimcore_icon_image_add_hotspot",
                    handler: this.openHotspotWindow.bind(this)
                },{
                    xtype: "button",
                    tooltip: t("clear_marker_or_hotspots"),
                    iconCls: "pimcore_icon_clear_marker",
                    handler: this.clearData.bind(this)
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_edit",
                    handler: this.openImage.bind(this)
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: this.empty.bind(this)
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this)
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_upload_single",
                    cls: "pimcore_inline_upload",
                    handler: this.uploadDialog.bind(this)
                }]
        };

        this.component = new Ext.Panel(conf);
        this.createImagePanel();

        return this.component;
    },

    createImagePanel: function() {
        this.panel = new Ext.Panel({
            width: this.fieldConfig.width,
            height: this.fieldConfig.height-27,
            bodyCssClass: "pimcore_droptarget_image pimcore_image_container",
            bodyStyle: "text-align: center; "
        });
        this.component.add(this.panel);


        this.panel.on("afterrender", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.component.getEl();
                },

                onNodeOver : function(target, dd, e, data) {

                    if (data.node.attributes.type == "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }
                },

                onNodeDrop : this.onNodeDrop.bind(this)
            });


            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            if (this.data) {
                this.updateImage();
            }

        }.bind(this));

        this.component.doLayout();

    },

    updateImage: function () {
        // 5px padding (-10)
        this.originalWidth = this.getBody().getWidth();
        this.originalHeight = this.getBody().getHeight();

        var width = this.getBody().getWidth()-10;
        var height = this.getBody().getHeight()-10;

        var path = "/admin/asset/get-image-thumbnail/id/" + this.data + "/width/" + width
            + "/height/" + height + "/contain/true" + "?" + Ext.urlEncode(this.crop);

        this.getBody().setStyle({
            backgroundImage: "url(" + path + ")",
            backgroundPosition: "center center",
            backgroundRepeat: "no-repeat"
        });

        this.getBody().repaint();

        this.getFileInfo(path);

        this.showPreview();
    },


    getFileInfo: function(path) {
        if (!this.fileinfo) {
            Ext.Ajax.request({
                url: path,
                params: {
                    fileinfo: 1
                },
                success: function (response) {
                    this.fileinfo = Ext.decode(response.responseText);
                    this.showPreview();
                }.bind(this)
            });
        }
    },

    openCropWindow: function () {
        var editor = new pimcore.element.tag.imagecropper(this.data, this.crop, function (data) {
            this.crop = {};
            this.crop["cropWidth"] = data.cropWidth;
            this.crop["cropHeight"] = data.cropHeight;
            this.crop["cropTop"] = data.cropTop;
            this.crop["cropLeft"] = data.cropLeft;
            this.crop["cropPercent"] = true;

            this.dirty = true;

            this.updateImage();
        }.bind(this), {
            ratioX: this.fieldConfig.ratioX,
            ratioY: this.fieldConfig.ratioY
        });
        editor.open(true);
    },


    openHotspotWindow: function() {
        if(this.data) {
            var editor = new pimcore.element.tag.imagehotspotmarkereditor(this.data,
                                    {hotspots: this.hotspots, marker: this.marker}, function (data) {
                this.hotspots = data["hotspots"];
                this.marker = data["marker"];

                this.showPreview();

                this.dirty = true;
            }.bind(this));
            editor.open(false);
        }
    },

    clearData: function() {
        this.doClearData();
        this.updateImage();
        pimcore.helpers.showNotification(t("success"), t("hotspots_cleared"), "success");

    },

    doClearData: function() {
        this.hotspots = [];
        this.marker = [];
        this.crop = [];
        this.dirty = true;
    },

    empty: function (nodeDrop) {
        this.data = null;
        this.fileinfo = null;

        if (!nodeDrop) {
            this.doClearData();
        }
        this.dirty = true;
        this.component.removeAll();
        this.createImagePanel();
    },

    getValue: function () {
        return {image: this.data, hotspots: this.hotspots, marker: this.marker, crop: this.crop};
    },

    showPreview: function() {
        if (this.fileinfo) {
            var i;
            var originalWidth = this.originalWidth;
            var originalHeight = this.originalHeight;

            var addX = (originalWidth - this.fileinfo.width) / 2;
            var addY = (originalHeight - this.fileinfo.height) / 2;

            for(i = 0; i < this.previewItems.length; i++) {
                if(Ext.get(this.previewItems[i])) {
                    Ext.get(this.previewItems[i]).remove();
                }
            }
            this.previewItems = [];


            for (i = 0; i < this.hotspots.length; i++) {
                var hotspotId = "hotspotId-" + uniqid();
                this.panel.body.insertHtml("beforeEnd", '<div id="' + hotspotId + '" class="pimcore_image_hotspot"></div>');
                this.previewItems.push(hotspotId);

                var hotspotEl = Ext.get(hotspotId);
                var config = this.hotspots[i];

                //calculate absolute size based in image-size
                var absoluteHeight = config["height"] * this.fileinfo.height / 100;
                var absoluteWidth = config["width"] * this.fileinfo.width / 100;
                var absoluteTop = config["top"] * this.fileinfo.height / 100;
                var absoluteLeft = config["left"] * this.fileinfo.width / 100;

                hotspotEl.applyStyles({
                    top: (absoluteTop + addY) + "px",
                    left: (absoluteLeft + addX) + "px",
                    height: (absoluteHeight) + "px",
                    width: (absoluteWidth) + "px"
                });

                this.addHotspotInfo(hotspotEl, config);
            }

            for (i = 0; i < this.marker.length; i++) {
                var markerId = "marker-" + uniqid();
                this.panel.body.insertHtml("beforeEnd", '<div id="' + markerId + '" class="pimcore_image_marker"></div>');
                this.previewItems.push(markerId);
                var markerEl = Ext.get(markerId);

                var config = this.marker[i];
                var top = config["top"]/100;
                var left = config["left"]/100;

                left = ((left * this.fileinfo.width) + addX) / originalWidth;
                top = ((top * this.fileinfo.height) + addY) / originalHeight;

                markerEl.applyStyles({
                    top: ((originalHeight * top) - 35) + "px",
                    left: ((originalWidth * left) - 12)+ "px"
                });

                this.addHotspotInfo(markerEl, config);
            }
        }
    },

    addHotspotInfo: function(element, config) {
        if(config["name"]) {
            element.dom.setAttribute("title", config["name"]);
        }

        var functionCallback = function () {
            this.openHotspotWindow();
        };

        element.addListener('click', functionCallback.bind(this), false);
    }
});