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

pimcore.registerNS("pimcore.object.preview");
pimcore.object.preview = Class.create({

    initialize: function(object) {
        this.object = object;
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('object_"
                                        + this.object.data.general.o_id + "').preview.iFrameLoaded()";

            this.layout = Ext.create('Ext.panel.Panel', {
                title: t('preview'),
                border: false,
                autoScroll: true,
                closable: false,
                iconCls: "pimcore_icon_tab_preview",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" width="100%" onload="' + iframeOnLoad
                    + '" frameborder="0" id="object_preview_iframe_' + this.object.data.general.o_id + '"></iframe>'
            });

            this.layout.on("resize", this.onLayoutResize.bind(this));
            this.layout.on("activate", this.refresh.bind(this));
        }

        return this.layout;
    },


    createLoadingMask: function() {
        if (!this.loadMask) {
            this.loadMask = new Ext.LoadMask(
                {
                    target: this.layout,
                    msg:t("please_wait")
                });

             //= new Ext.LoadMask(this.layout.getEl(), {msg: t("please_wait")});
            this.loadMask.enable();
        }
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("object_preview_iframe_" + this.object.data.general.o_id).setStyle({
            height: (height) + "px"
        });
    },

    iFrameLoaded: function () {
        if (this.loadMask) {
            this.loadMask.hide();
        }
    },

    loadCurrentPreview: function () {
        var date = new Date();

        var path = "/admin/object/preview?id=" + this.object.data.general.o_id + "&time=" + date.getTime();
        
        try {
            Ext.get("object_preview_iframe_" + this.object.data.general.o_id).dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
    },

    refresh: function () {
        this.createLoadingMask();
        this.loadMask.enable();
        this.object.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.object));
    }
});