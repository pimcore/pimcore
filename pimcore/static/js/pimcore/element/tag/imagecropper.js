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
        var imageUrl = '/admin/asset/get-image-thumbnail/id/' + this.imageId + '/width/500/height/400/contain/true';

        if(typeof modal != "undefined") {
            this.modal = modal;
        }

        this.editWindow = new Ext.Window({
            width: 500,
            height: 400,
            modal: this.modal,
            closeAction: "close",
            resizable: false,
            bodyStyle: "background: url(" + imageUrl + ") center center no-repeat;position:relative;",
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

                    this.resizer = null;
                    this.editWindow.close();
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

                        var winBodyInnerSize = this.editWindow.body.getSize();
                        var winOuterSize = this.editWindow.getSize();
                        var paddingWidth = winOuterSize["width"] - winBodyInnerSize["width"];
                        var paddingHeight = winOuterSize["height"] - winBodyInnerSize["height"];

                        this.editWindow.setSize(imageWidth + paddingWidth, imageHeight + paddingHeight);

                        Ext.get("selectorImage").remove();

                        var checkSize = function () {
                            // this function checks if the selected area fits into the image
                            var sel = Ext.get("selector");
                            var dimensions;
                            var originalWidth = this.editWindow.getInnerWidth();
                            var originalHeight = this.editWindow.getInnerHeight();
                            var skip = false;

                            while(!skip) {
                                skip = true;
                                 dimensions = sel.getStyles("top","left","width","height");

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
                                dimensions = sel.getStyles("width","height");

                                var height = intval(dimensions.width) * (this.ratioY / this.ratioX);
                                sel.setStyle("height", (height) + "px");
                            }
                        };

                        this.resizer = new Ext.Resizable('selector', {
                            pinned:true,
                            minWidth:50,
                            minHeight: 50,
                            preserveRatio: false,
                            dynamic:true,
                            handles: 'all',
                            draggable:true,
                            width: 100,
                            height: 100,
                            listeners: {
                                resize: checkSize.bind(this)
                            }
                        });

                        this.resizer.dd.endDrag = checkSize.bind(this);

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
                        this.resizer = null;
                        this.editWindow.close();
                    }

                    this.editWindowInitCount++;
                }
            }.bind(this), 500);

        }.bind(this));

        this.editWindow.show();
    }

});
