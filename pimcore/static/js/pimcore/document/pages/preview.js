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
        this.mode = "desktop";
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
                    toggleGroup: "preview-type-" + this.page.id,
                    pressed: true,
                    allowDepress: false,
                    handler: function () {
                        this.setMode("desktop");
                    }.bind(this)
                }, {
                    text: t("mobile"),
                    iconCls: "pimcore_icon_mobile",
                    toggleGroup: "preview-type-" + this.page.id,
                    allowDepress: false,
                    handler: function () {
                        this.setMode("mobile");
                    }.bind(this)
                }];
            }

            this.layout = new Ext.Panel({
                title: t('preview'),
                border: false,
                tbar: tbar,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_preview",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
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
        this.loadMask.show();
        this.mode = mode;
        this.loadCurrentPreview();
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("document_preview_iframe_" + this.page.id).setStyle({
            height: (height-30) + "px"
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

        if(this.mode == "desktop") {
            path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime();
        } else {
            path = "/admin/page/mobile-preview/id/" + this.page.id;
        }

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