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
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('document_" + this.page.id + "').preview.iFrameLoaded()";

            this.layout = new Ext.Panel({
                title: t('preview'),
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_preview",
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

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("document_preview_iframe_" + this.page.id).setStyle({
            height: (height) + "px"
        });
    },

    iFrameLoaded: function () {
        this.loadMask.hide();
    },

    loadCurrentPreview: function () {
        var date = new Date();

        var path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime();
        
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