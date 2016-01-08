/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.document.pages.preview");
pimcore.document.pages.preview = Class.create({

    initialize: function(page) {
        this.page = page;
        this.mode = "full";
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('document_" + this.page.id + "').preview.iFrameLoaded()";

            // preview switcher only for pages not for emails
            var tbar = [];
            if(this.page.getType() == "page") {

                var previewModes = [
                    {type: "desktop", name: '10" Netbook', width: 1024, height: 600, icon: ""},
                    {type: "desktop", name: '12" Netbook', width: 1024, height: 768, icon: ""},
                    {type: "desktop", name: '13" Netbook', width: 1280, height: 800, icon: ""},
                    {type: "desktop", name: '15" Netbook', width: 1366, height: 768, icon: ""},
                    {type: "desktop", name: '19" Desktop', width: 1440, height: 900, icon: ""},
                    {type: "desktop", name: '20" Desktop', width: 1600, height: 900, icon: ""},
                    {type: "desktop", name: '22" Desktop', width: 1680, height: 1050, icon: ""},
                    {type: "desktop", name: '23" Desktop', width: 1920, height: 1080, icon: ""},
                    {type: "desktop", name: '24" Desktop', width: 1920, height: 1200, icon: ""},
                    {type: "tablet", name: 'Velocity Cruz', width: 800, height: 600, icon: ""},
                    {type: "tablet", name: 'Samsung Galaxy', width: 1024, height: 600, icon: ""},
                    {type: "tablet", name: 'Apple iPad (mini)', width: 1024, height: 768, icon: ""},
                    {type: "tablet", name: 'Google Nexus 10', width: 1280, height: 800, icon: ""},
                    {type: "tablet", name: 'Google Nexus 7', width: 960, height: 600, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 3/4', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 5 (c/s)', width: 320, height: 568, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 6', width: 375, height: 667, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 6 Plus', width: 414, height: 736, icon: ""},
                    {type: "mobile", name: 'LG Optimus S', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'Google Nexus S', width: 480, height: 800, icon: ""},
                    {type: "mobile", name: 'Google Nexus 5 (five)', width: 360, height: 598, icon: ""},
                    {type: "tv", name: '480p TV', width: 640, height: 480, icon: ""},
                    {type: "tv", name: '720p TV', width: 1280, height: 720, icon: ""},
                    {type: "tv", name: '1080p TV', width: 1920, height: 1080, icon: ""}
                ];

                var menues = {
                    desktop: [],
                    tablet: [],
                    mobile: [],
                    tv: []
                };


                for(var i=0; i<previewModes.length; i++) {
                    menues[previewModes[i]["type"]].push({
                        text: previewModes[i]["name"] + " (" + previewModes[i]["width"] + "x"
                                                                            + previewModes[i]["height"] + ")",
                        handler: this.setMode.bind(this, previewModes[i])
                    });
                }

                tbar = [{
                    text: "Desktop",
                    iconCls: "pimcore_icon_desktop",
                    menu: menues["desktop"]
                }, {
                    text: "Tablet",
                    iconCls: "pimcore_icon_tablet",
                    menu: menues["tablet"]
                }, {
                    text: "Mobile",
                    iconCls: "pimcore_icon_mobile",
                    menu: menues["mobile"]
                }, {
                    text: "Smart TV",
                    iconCls: "pimcore_icon_tv",
                    menu: menues["tv"]
                }, "-", {
                    text: t("qr_codes"),
                    iconCls: "pimcore_icon_qrcode",
                    handler: function () {
                        var codeUrl = "/admin/reports/qrcode/code/documentId/" + this.page.id;
                        var download = function (format) {
                            var codeUrl = "/admin/reports/qrcode/code/documentId/"
                                + this.page.id + "/renderer/" + format + "/download/true" +
                                "/moduleSize/20";
                            pimcore.helpers.download(codeUrl);
                        }

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
                                    text: "PNG",
                                    iconCls: "pimcore_icon_png",
                                    handler: download.bind(this, "image")
                                },{
                                    text: "EPS",
                                    iconCls: "pimcore_icon_eps",
                                    handler: download.bind(this, "eps")
                                }, {
                                    text: "SVG",
                                    iconCls: "pimcore_icon_svg",
                                    handler: download.bind(this, "svg")
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
                html: '<iframe src="about:blank" width="100%" onload="' + iframeOnLoad + '" frameborder="0" id="'
                    + this.iframeName + '" name="' + this.iframeName + '"' +
                    'style="background: #fff;"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t("preview"),
                border: false,
                layout: "border",
                tbar: tbar,
                iconCls: "pimcore_icon_tab_preview",
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

    setMode: function (mode) {
        var iframe = this.getIframe();
        var availableWidth = this.framePanel.getWidth()-50;
        var availableHeight = this.framePanel.getHeight()-50;

        if(availableWidth < mode["width"] || availableHeight < mode["height"]) {
            Ext.MessageBox.alert(t("error"), t("screen_size_to_small"));
            return;
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
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        if(this.mode == "full") {
            this.setLayoutFrameDimensions(width, height);
        }
    },

    setLayoutFrameDimensions: function (width, height) {
        this.getIframe().setStyle({
            height: (height) + "px"
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


    loadCurrentPreview: function () {
        var date = new Date();
        var path;

        path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime();

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