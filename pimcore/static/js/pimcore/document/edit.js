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

pimcore.registerNS("pimcore.document.edit");
pimcore.document.edit = Class.create({

    initialize: function(document) {
        this.document = document;

    },

    getEditLink: function () {
        var date = new Date();
        return  this.document.data.path + this.document.data.key + '?pimcore_editmode=true&systemLocale='+pimcore.settings.language+'&_dc=' + date.getTime();
    },

    getLayout: function () {

        if (this.layout == null) {
            this.reloadInProgress = true;
            this.iframeName = 'document_iframe_' + this.document.id;

            var html = '<iframe id="' + this.iframeName + '" width="100%" name="' + this.iframeName + '" src="' + this.getEditLink() + '" frameborder="0"></iframe>';
            this.layout = new Ext.Panel({
                id: "document_content_" + this.document.id,
                html: html,
                title: t('edit'),
                autoScroll: true,
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                forceLayout: true,
                hideMode: "offsets",
                iconCls: "pimcore_icon_tab_edit"
            });
            this.layout.on("resize", this.onLayoutResize.bind(this));
        }

        return this.layout;

    },
    
    /*
    iFrameLoaded: function () {
        console.log("finished");
    },
    */
    
    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get(this.iframeName).setStyle({
            height: height + "px"
        });
    },

    onClose: function () {

        try {
            this.reloadInProgress = true;
            window[this.iframeName].location.href = "about:blank";
            Ext.get(this.iframeName).remove();
            delete window[this.iframeName];

        } catch (e) {
        }
    },

    protectLocation: function () {
        if (this.reloadInProgress != true) {
            window.setTimeout(this.reload.bind(this), 200);
        }
    },

    iframeOnbeforeunload: function () {
        if(!this.reloadInProgress && !pimcore.globalmanager.get("pimcore_reload_in_progress")) {
            return t("do_you_really_want_to_leave_the_editmode");
        }
    },

    reload: function (disableSaveToSession) {

        if (this.reloadInProgress) {
            disableSaveToSession = true;
        }

        this.reloadInProgress = true;

        try {
            this.lastScrollposition = this.frame.Ext.getBody().getScroll();
        }
        catch (e) {
            console.log(e);
        }

        if (disableSaveToSession === true) {
            Ext.get(this.iframeName).dom.src = this.getEditLink();
        }
        else {
            this.document.saveToSession(function () {
                Ext.get(this.iframeName).dom.src = this.getEditLink();
            }.bind(this));
        }
    },

    maskFrames: function () {
        
        // this is for dnd over iframes, with this method it's not nessercery to register the dnd manager in each iframe (wysiwyg)
        var width;
        var height;
        var offset;
        var element;


        // mask frames (iframes)
        try {
            // mask iframes
            if (typeof this.frame.Ext != "object") {
                return;
            }

            var iFrames = this.frame.document.getElementsByTagName("iframe");
            for (var i = 0; i < iFrames.length; i++) {
                width = Ext.get(iFrames[i]).getWidth();
                height = Ext.get(iFrames[i]).getHeight();

                offset = Ext.get(iFrames[i]).getOffsetsTo(this.frame.Ext.getBody());
                
                var element = this.frame.Ext.getBody().createChild({
                    tag: "div",
                    id: Ext.id()
                })
                
                element.setStyle({
                    width: width + "px",
                    height: height + "px",
                    left: offset[0] + "px",
                    top: offset[1] + "px"
                });              
                
                element.addClass("pimcore_iframe_mask");
            }
        }
        catch (e) {
            console.log(e); 
            console.log("there is no frame to mask");
        }

        // mask fields
        this.fieldsToMask = [];
        try {
            if (this.frame && this.frame.editables) {
                var editables = this.frame.editables;

                for (var i = 0; i < editables.length; i++) {
                    try {
                        if (typeof editables[i].mask == "function") {
                            editables[i].mask();
                            this.fieldsToMask.push(editables[i]);
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            }
        } catch (e) {
            console.log(e);
        }
    },

    unmaskFrames: function () {

        // unmask frames
        try {
            if (typeof this.frame.Ext != "object") {
                return;
            }

            // remove the masks from iframes
            var masks = this.frame.Ext.query(".pimcore_iframe_mask");
            for (var i = 0; i < masks.length; i++) {
                Ext.get(masks[i]).remove();
            }
        } catch (e) {
            console.log(e);
            console.log("there is no frame to unmask");
        }

        // unmask editables
        try {
            for (var i = 0; i < this.fieldsToMask.length; i++) {
                this.fieldsToMask[i].unmask();
            }
        } catch (e) {
            console.log(e);
        }
    },

    getValues: function () {

        var values = {};
        
        if (!this.frame || !this.frame.editables) {
            throw "edit not available";
        }

        try {
            var editables = this.frame.editables;
            var editableName = "";

            for (var i = 0; i < editables.length; i++) {
                try {
                    if (editables[i].getName() && !editables[i].getInherited()) {
                        editableName = editables[i].getName();
                        values[editableName] = {};
                        values[editableName].data = editables[i].getValue();
                        values[editableName].type = editables[i].getType();
                    }
                } catch (e) {
                }
            }
        }
        catch (e) {
        }

        return values;
    }

});




