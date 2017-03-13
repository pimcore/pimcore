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

pimcore.registerNS("pimcore.document.pages.preview");
pimcore.document.pages.preview = Class.create({

    initialize: function(page) {
        this.page = page;
        this.mode = "full";

        this.availableHeight = null;
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('document_" + this.page.id + "').preview.iFrameLoaded()";

            // preview switcher only for pages not for emails
            var tbar = [];
            if(this.page.getType() == "page") {

                tbar = [{
                    text: t("desktop"),
                    iconCls: "pimcore_icon_desktop",
                    handler: this.setFullMode.bind(this)
                }, {
                    text: t("tablet"),
                    iconCls: "pimcore_icon_tablet",
                    handler: this.setMode.bind(this, {device: "tablet", width: 1024, height: 768})
                }, {
                    text: t("phone"),
                    iconCls: "pimcore_icon_mobile",
                    handler: this.setMode.bind(this, {device: "phone", width: 375, height: 667})
                },{
                    text: t("phone"),
                    iconCls: "pimcore_icon_tv",
                    handler: this.setMode.bind(this, {device: "phone", width: 667, height: 375})
                }, "-", {
                    text: t("qr_codes"),
                    iconCls: "pimcore_icon_qrcode",
                    handler: function () {
                        var codeUrl = "/admin/reports/qrcode/code?documentId=" + this.page.id;
                        var download = function () {
                            var codeUrl = "/admin/reports/qrcode/code?documentId=" + this.page.id + "/download/true";
                            pimcore.helpers.download(codeUrl);
                        };

                        var qrWindow = new Ext.Window({
                            width: 280,
                            border:false,
                            title: t("qr_codes"),
                            modal: true,
                            autoScroll: true,
                            bodyStyle: "padding: 10px; text-align:center;",
                            items: [{
                                    html: '<img src="' + codeUrl + '" style="padding:10px; height:250px;" />',
                                    border: true,
                                    height: 250
                                }, {
                                border: false,
                                buttons: [{
                                    width: "100%",
                                    text: t("download"),
                                    iconCls: "pimcore_icon_png",
                                    handler: download.bind(this)
                                }]
                            }]
                        });

                        qrWindow.show();

                    }.bind(this)
                }];
            }

            this.iframeName = "document_preview_iframe_" + this.page.id;

            this.framePanel = new Ext.Panel({
                border: false,
                region: "center",
                scrollable: false,
                bodyStyle: "background:#323232;",
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" onload="' + iframeOnLoad + '" frameborder="0" ' +
                    'style="width: 100%;background: #fff;" id="' + this.iframeName + '" ' +
                    'name="' + this.iframeName + '"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t("preview"),
                border: false,
                layout: "border",
                tbar: tbar,
                iconCls: "pimcore_icon_preview",
                bodyCls: "pimcore_preview_body",
                items: [this.framePanel]
            });

            this.layout.on("activate", function () {
                this.refresh();
            }.bind(this));


            this.framePanel.on("resize", this.onLayoutResize.bind(this));
            this.framePanel.on("afterrender", function () {
                this.loadMask = new Ext.LoadMask({
                    target: this.layout,
                    msg: t("please_wait")
                });

                this.loadMask.enable();
            }.bind(this));
        }

        return this.layout;
    },

    setFullMode: function () {
        this.getIframe().applyStyles({
            position: "relative",
            border: "0",
            width: "100%",
            height: (this.availableHeight-7) + "px",
            top: "initial",
            left: "initial"
        });

        this.loadCurrentPreview("desktop");
    },

    setMode: function (mode) {
        var iframe = this.getIframe();
        var availableWidth = this.framePanel.getWidth()-10;
        var availableHeight = this.framePanel.getHeight()-10;

        if(availableWidth < mode["width"]) {
            Ext.MessageBox.alert(t("error"), t("screen_size_to_small"));
            return;
        }

        if(availableHeight < mode["height"]) {
            mode["height"] = availableHeight;
        }

        var top = Math.floor((availableHeight - mode["height"])/2);
        var left = Math.floor((availableWidth - mode["width"])/2);

        iframe.applyStyles({
            position: "absolute",
            border: "5px solid #323232",
            width: mode["width"] + "px",
            height: mode["height"] + "px",
            top: top + "px",
            left: left + "px"
        });

        this.loadCurrentPreview(mode["device"]);
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        if(this.mode == "full") {
            this.setLayoutFrameDimensions(width, height);
        }

        this.availableHeight = height;
    },

    setLayoutFrameDimensions: function (width, height) {
        this.getIframe().setStyle({
            height: (height-7) + "px"
        });
    },

    iFrameLoaded: function () {
        if(this.loadMask && this.getIframe().getAttribute("src").indexOf("pimcore_preview") > 0){
            this.loadMask.hide();
        }
    },

    getIframe: function () {
        var iframe = Ext.get(this.iframeName);
        return iframe;
    },

    getIframeWindow: function () {
        return window[this.iframeName];
    },

    getIframeDocument: function () {
        return this.getIframeWindow().document;
    },

    getIframeBody: function () {
        return Ext.get(this.getIframeDocument().getElementsByTagName("body")[0]);
    },


    loadCurrentPreview: function (device) {
        var date = new Date();
        var path;

        path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime() + "&forceDeviceType=" + device;

        // add persona parameter if available
        if(this.page["edit"] && this.page.edit["persona"]) {
            if(this.page.edit.persona && this.page.edit.persona.getValue()) {
                path += "&_ptp=" + this.page.edit.persona.getValue();
            }
        }

        try {
            this.getIframe().dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
    },

    onClose: function () {
        try {
            window[this.iframeName].location.href = "about:blank";
            Ext.get(this.iframeName).remove();
            delete window[this.iframeName];
        } catch (e) { }
    },

    refresh: function () {
        this.loadMask.show();
        this.page.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.page));
    }
});