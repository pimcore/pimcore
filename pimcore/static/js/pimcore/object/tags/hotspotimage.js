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

pimcore.registerNS("pimcore.object.tags.hotspotimage");
pimcore.object.tags.hotspotimage = Class.create(pimcore.object.tags.image, {
    
    type: "hotspotimage",
    data: null,

    marginTop: 10,
    marginLeft: 8,

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
            bodyCssClass: "pimcore_droptarget_image",
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
        }.bind(this));
        editor.open(true);
    },

    openHotspotWindow: function() {
        if(this.data) {
            var editor = new pimcore.element.tag.imagehotspotmarkereditor(this.data, {hotspots: this.hotspots, marker: this.marker}, function (data) {
                this.hotspots = data["hotspots"];
                this.marker = data["marker"];

                this.dirty = true;
            }.bind(this));
            editor.open(false);
        }
    },

    empty: function () {
        this.data = null;

        this.hotspots = [];
        this.marker = [];
        this.crop = [];
        this.dirty = true;
        this.component.removeAll();
        this.createImagePanel();
    },

    getValue: function () {
        return {image: this.data, hotspots: this.hotspots, marker: this.marker, crop: this.crop};
    }
});