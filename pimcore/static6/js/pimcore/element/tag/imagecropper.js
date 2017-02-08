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
pimcore.registerNS("pimcore.element.tag.imagecropper");
pimcore.element.tag.imagecropper = Class.create({

    initialize: function (imageId, data, saveCallback, config) {
        this.imageId = imageId;
        this.data = data;
        this.saveCallback = saveCallback;
        this.modal = true;

        this.ratioX = null;
        this.ratioY = null;
        if(typeof config == "object") {
            if(config["ratioX"] && config["ratioY"]) {
                this.ratioX = config["ratioX"];
                this.ratioY = config["ratioY"];
            }
        }
    },

    open: function (modal) {
        var validImage = this.imageId !== null,
            imageUrl = '/admin/asset/get-image-thumbnail/id/' + this.imageId + '/width/500/height/400/contain/true',
            button = {};

        if(typeof modal != "undefined") {
            this.modal = modal;
        }

        if( validImage )
        {
            button = {
                xtype: "button",
                iconCls: "pimcore_icon_apply",
                text: t("save"),
                handler: function () {

                    var originalWidth = this.editWindow.body.getWidth();
                    var originalHeight = this.editWindow.body.getHeight();

                    var dimensions = Ext.get("selector").getStyle(["top","left","width","height"]);

                    var newWidth = intval(dimensions.width);
                    var newHeight = intval(dimensions.height);
                    var top = intval(dimensions.top);
                    var left = intval(dimensions.left);

                    this.data = {
                        cropWidth: newWidth * 100 / originalWidth,
                        cropHeight: newHeight * 100 / originalHeight,
                        cropTop: top * 100 / originalHeight,
                        cropLeft: left * 100 / originalWidth,
                        cropPercent: true
                    };

                    if(typeof this.saveCallback == "function") {
                        this.saveCallback(this.data);
                    }

                    this.editWindow.close();
                }.bind(this)
            }
        }
        this.editWindow = new Ext.Window({
            width: 500,
            height: 400,
            modal: this.modal,
            resizable: false,
            bodyStyle: validImage ? "background: url(" + imageUrl + ") center center no-repeat;position:relative;" : "",
            bbar: ["->", button],
            html: validImage ? '<img id="selectorImage" src="' + imageUrl + '" />' : '<span style="padding:10px;">' + t("no_image_assigned") + '</span>'
        });

        var checkSize = function () {
            // this function checks if the selected area fits into the image
            var sel = Ext.get("selector");
            var dimensions;

            var windowId = this.editWindow.getId();
            var originalWidth = Ext.getCmp(windowId).getEl().getWidth(true);
            var originalHeight = Ext.getCmp(windowId).getEl().getHeight(true);

            var skip = false;

            while(!skip) {
                skip = true;
                dimensions = sel.getStyle(["top","left","width","height"]);

                if(intval(dimensions.top) < 0) {
                    sel.setStyle("top", "0");
                    skip = false;
                }
                if(intval(dimensions.left) < 0) {
                    sel.setStyle("left", "0");
                    skip = false;
                }
                if((intval(dimensions.left) + intval(dimensions.width)) > originalWidth) {
                    if(intval(dimensions.left) < originalWidth || intval(dimensions.left) > originalWidth) {
                        sel.setStyle("left", (originalWidth-intval(dimensions.width)) + "px");
                    }
                    if(intval(dimensions.width) > originalWidth) {
                        sel.setStyle("width", (originalWidth) + "px");
                    }
                    skip = false;
                }
                if((intval(dimensions.top) + intval(dimensions.height)) > originalHeight) {
                    if(intval(dimensions.top) < originalHeight || intval(dimensions.top) > originalHeight) {
                        sel.setStyle("top", (originalHeight-intval(dimensions.height)) + "px");
                    }
                    if(intval(dimensions.height) > originalHeight) {
                        sel.setStyle("height", (originalHeight) + "px");
                    }
                    skip = false;
                }
            }


            // check the ratio if given
            if(this.ratioX && this.ratioY) {
                dimensions = sel.getStyle(["width","height"]);

                var height = intval(dimensions.width) * (this.ratioY / this.ratioX);
                sel.setStyle("height", (height) + "px");
            }
        };

        if( validImage ) {

            this.editWindow.add({
                xtype: 'component',
                id: "selector",
                resizable: {
                    target: "selector",
                    pinned: true,
                    width: 100,
                    height: 100,
                    preserveRatio: false,
                    dynamic: true,
                    handles: 'all',
                    listeners: {
                        resize: checkSize.bind(this)
                    }
                },
                style: "cursor:move; position: absolute; top: 10px; left: 10px;z-index:9000;",
                draggable: true,
                listeners: {
                    afterrender: function (el) {

                    }
                }
            });

        }

        this.editWindowInitCount = 0;

        this.editWindow.on("afterrender", function ( ){
            this.editWindowInterval = window.setInterval(function () {
                var el = Ext.get("selectorImage");

                if(el) {

                    var imageWidth = el.getWidth();
                    var imageHeight = el.getHeight();

                    if(el.getWidth() > 30) {
                        clearInterval(this.editWindowInterval);
                        this.editWindowInitCount = 0;

                        var winBodyInnerSize = this.editWindow.body.getSize();
                        var winOuterSize = this.editWindow.getSize();
                        var paddingWidth = winOuterSize["width"] - winBodyInnerSize["width"];
                        var paddingHeight = winOuterSize["height"] - winBodyInnerSize["height"];

                        this.editWindow.setSize(imageWidth + paddingWidth, imageHeight + paddingHeight);

                        Ext.get("selectorImage").remove();

                        if(this.data && this.data["cropPercent"]) {
                            Ext.get("selector").applyStyles({
                                width: (imageWidth * (this.data.cropWidth / 100)) + "px",
                                height: (imageHeight * (this.data.cropHeight / 100)) + "px",
                                top: (imageHeight * (this.data.cropTop / 100)) + "px",
                                left: (imageWidth * (this.data.cropLeft / 100)) + "px"
                            });
                        }

                        return;

                    } else if (this.editWindowInitCount > 60) {
                        // if more than 30 secs cancel and close the window
                        this.editWindow.close();
                    }

                    this.editWindowInitCount++;
                } else {
                    clearInterval(this.editWindowInterval);
                }
            }.bind(this), 500);

        }.bind(this));

        this.editWindow.show();
    }

});
