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
            if(this.page.getType() == "page" && !Ext.isIE8) {

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
                    {type: "tablet", name: 'Apple iPad 1&2', width: 1024, height: 768, icon: ""},
                    {type: "tablet", name: 'Motorola Xoom', width: 1280, height: 800, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 3/4', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'LG Optimus S', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'Google Nexus S', width: 480, height: 800, icon: ""},
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
                        text: previewModes[i]["name"] + " (" + previewModes[i]["width"] + "x" + previewModes[i]["height"] + ")",
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
                }];
            }

            this.layout = new Ext.Panel({
                title: t('preview'),
                border: false,
                tbar: tbar,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_preview",
                bodyStyle: "-webkit-overflow-scrolling:touch; background:#323232;",
                html: '<iframe src="about:blank" width="100%" onload="' + iframeOnLoad + '" frameborder="0" id="document_preview_iframe_' + this.page.id + '"></iframe>'
            });

            this.layout.on("resize", this.onLayoutResize.bind(this));
            this.layout.on("activate", this.refresh.bind(this));
            this.layout.on("afterrender", function () {
                this.loadMask = new Ext.LoadMask(this.layout.getEl(), {msg: t("please_wait")});
                this.loadMask.enable();
            }.bind(this));
        }

        return this.layout;
    },

    setMode: function (mode) {
        var iframe = Ext.get("document_preview_iframe_" + this.page.id);
        var availableWidth = this.layout.getWidth()-50;
        var availableHeight = this.layout.getHeight()-50;
        var positioningHeight = mode["height"];
        var positioningWidth = mode["width"];

        zoom = 1;

        if(mode["width"] > availableWidth || mode["height"] > availableHeight) {
            if(mode["height"] > availableHeight) {
                zoom = availableHeight / mode["height"];
            } else {
                zoom = availableWidth / mode["width"];
            }

            zoom = zoom-0.1;

            positioningHeight = Math.floor(mode["height"] * zoom);
            positioningWidth = Math.floor(mode["width"] * zoom);
        }

        var top = Math.floor((availableHeight - positioningHeight)/2);
        var left = Math.floor((availableWidth - positioningWidth)/2);

        iframe.applyStyles({
            position: "absolute",
            "transform-origin": "0 0",
            border: "5px solid #323232",
            transform: "scale(" + zoom + ")",
            zoom: zoom,
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
        Ext.get("document_preview_iframe_" + this.page.id).setStyle({
            height: (height-35) + "px"
        });
    },

    iFrameLoaded: function () {
        if(this.loadMask){
            this.loadMask.hide();
        }
    },

    loadCurrentPreview: function () {
        var date = new Date();
        var path;

        path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime();

        try {
            Ext.get("document_preview_iframe_" + this.page.id).dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
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