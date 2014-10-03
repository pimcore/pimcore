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

pimcore.registerNS("pimcore.document.edit");
pimcore.document.edit = Class.create({

    initialize: function(document) {
        this.document = document;

    },

    getEditLink: function () {
        var date = new Date();
        var link =  this.document.data.path + this.document.data.key + '?pimcore_editmode=true&systemLocale='
                                                            + pimcore.settings.language+'&_dc=' + date.getTime();

        if(this.persona && this.persona.getValue()) {
            link += "&_ptp=" + this.persona.getValue();
        }

        return link;
    },

    getLayout: function (additionalConfig) {

        if (this.layout == null) {
            this.reloadInProgress = true;
            this.iframeName = 'document_iframe_' + this.document.id;

            var html = '<iframe id="' + this.iframeName + '" width="100%" name="' + this.iframeName
                                                    + '" src="' + this.getEditLink() + '" frameborder="0"></iframe>';


            var cleanupFunction = function () {
                Ext.Ajax.request({
                    url: "/admin/page/clear-editable-data",
                    params: {
                        persona: this["persona"] ? this.persona.getValue() : "",
                        id: this.document.id
                    },
                    success: function () {
                        this.reload(true);
                    }.bind(this)
                });
            };

            var tbar = [{
                text: t("refresh"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            },"-",{
                tooltip: t("highlight_editable_elements"),
                iconCls: "pimcore_icon_highlight",
                enableToggle: true,
                handler: function (el) {
                    var editables = this.frame.Ext.getBody().query(".pimcore_editable");
                    var ed;
                    for(var i=0; i<editables.length; i++) {
                        ed = Ext.get(editables[i]);
                        if(!ed.hasClass("pimcore_tag_inc") && !ed.hasClass("pimcore_tag_areablock")
                            && !ed.hasClass("pimcore_tag_block") && !ed.hasClass("pimcore_tag_area")) {
                            if(el.pressed) {
                                var mask = ed.mask();
                                mask.setStyle("background-color","#f5d833");
                            } else {
                                ed.unmask();
                            }
                        }
                    }
                }.bind(this)
            }, "-", {
                tooltip: t("clear_content_of_current_view"),
                iconCls: "pimcore_icon_cleanup",
                handler: cleanupFunction.bind(this)
            }];

            // add persona selection to toolbar
            if(this.document.getType() == "page" && pimcore.globalmanager.get("personas").getCount() > 0) {

                this.persona = new Ext.form.ComboBox({
                    displayField:'text',
                    valueField: "id",
                    store: {
                        xtype: "jsonstore",
                        url: "/admin/reports/targeting/persona-list/?add-default=true",
                        fields: ["id", "text"]
                    },
                    editable: false,
                    triggerAction: 'all',
                    width: 240,
                    cls: "pimcore_icon_persona_select",
                    emptyText: t("edit_content_for_persona"),
                    listeners: {
                        select: function (el) {
                            if(this.document.isDirty()) {
                                Ext.Msg.confirm(t('warning'), t('you_have_unsaved_changes')
                                    + "<br />" + t("continue") + "?",
                                    function(btn){
                                        if (btn == 'yes'){
                                            this.reload(true);
                                        }
                                    }.bind(this)
                                );
                            } else {
                                this.reload(true);
                            }
                        }.bind(this)
                    }
                });

                tbar.push("->", this.persona, {
                    tooltip: t("clear_content_of_selected_persona"),
                    iconCls: "pimcore_icon_cleanup",
                    handler: cleanupFunction.bind(this)
                });
            }

            // edit panel configuration
            var config = {
                id: "document_content_" + this.document.id,
                html: html,
                title: t('edit'),
                autoScroll: true,
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                forceLayout: true,
                hideMode: "offsets",
                iconCls: "pimcore_icon_tab_edit",
                tbar: tbar
            };

            if(typeof additionalConfig == "object") {
                config = Ext.apply(config, additionalConfig);
            }

            this.layout = new Ext.Panel(config);
            this.layout.on("resize", this.onLayoutResize.bind(this));

            this.layout.on("afterrender", function () {

                // unfortunately we have to do this in jQuery, because Ext doesn'T offer this functionality
                $("#" + this.iframeName).load(function () {
                    // this is to hide the mask if edit/startup.js isn't executed (eg. in case an error is shown)
                    // otherwise edit/startup.js will disable the loading mask
                    if(!this["frame"]) {
                        this.loadMask.hide();
                    }
                }.bind(this));

                this.loadMask = new Ext.LoadMask(this.layout.body, {msg: t("please_wait")});
                this.loadMask.enable();
                this.loadMask.show();
            }.bind(this));
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
            height: (height-32) + "px"
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
            if(this["frame"]) {
                this.lastScrollposition = this.frame.Ext.getBody().getScroll();
            }
        }
        catch (e) {
            console.log(e);
        }

        this.loadMask.show();

        if (disableSaveToSession === true) {
            this.frame = null;
            Ext.get(this.iframeName).dom.src = this.getEditLink();
        }
        else {
            this.document.saveToSession(function () {
                this.frame = null;
                Ext.get(this.iframeName).dom.src = this.getEditLink();
            }.bind(this));
        }
    },

    maskFrames: function () {
        
        // this is for dnd over iframes, with this method it's not nessercery to register the dnd manager in each
        // iframe (wysiwyg)
        var width;
        var height;
        var offset;
        var element;
        var i;


        // mask frames (iframes)
        try {
            // mask iframes
            if (typeof this.frame.Ext != "object") {
                return;
            }

            var iFrames = this.frame.document.getElementsByTagName("iframe");
            for (i = 0; i < iFrames.length; i++) {
                width = Ext.get(iFrames[i]).getWidth();
                height = Ext.get(iFrames[i]).getHeight();

                offset = Ext.get(iFrames[i]).getOffsetsTo(this.frame.Ext.getBody());

                var parentElement = this.frame.Ext.get(iFrames[i]).parent();

                parentElement.applyStyles({
                    position: "relative"
                });

                element = parentElement.createChild({
                    tag: "div",
                    id: Ext.id()
                });

                element.setStyle({
                    width: width + "px",
                    height: height + "px",
                    left: 0,
                    top: 0
                });              
                
                element.addClass("pimcore_iframe_mask");
            }
        }
        catch (e) {
            console.log(e); 
            console.log("there is no frame to mask");
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
        catch (e2) {
        }

        return values;
    }

});




