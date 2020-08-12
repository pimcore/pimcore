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

pimcore.registerNS("pimcore.document.edit");
pimcore.document.edit = Class.create({

    initialize: function(document) {
        this.document = document;
        this.lastScrollposition = null;
    },

    getEditLink: function () {
        var date = new Date();
        var link =  this.document.data.path + this.document.data.key + '?pimcore_editmode=true&systemLocale='
            + pimcore.settings.language+'&_dc=' + date.getTime();

        if (pimcore.settings.disableMinifyJs) {
            link += "&unminified_js";
        }

        if(this.targetGroup && this.targetGroup.getValue()) {
            link += "&_ptg=" + this.targetGroup.getValue();
        }

        return link;
    },

    getLayout: function (additionalConfig) {

        if (this.layout == null) {
            this.reloadInProgress = true;
            this.iframeName = 'document_iframe_' + this.document.id;

            var html = '<iframe id="' + this.iframeName + '" style="width: 100%;" name="' + this.iframeName
                + '" src="' + this.getEditLink() + '" frameborder="0"></iframe>';


            var cleanupFunction = function () {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_document_page_cleareditabledata'),
                    method: "PUT",
                    params: {
                        targetGroup: this["targetGroup"] ? this.targetGroup.getValue() : "",
                        id: this.document.id
                    },
                    success: function () {
                        this.reload(true);
                    }.bind(this)
                });
            };

            this.areaToolbarTrigger = new Ext.button.Button({
                iconCls: "pimcore_icon_plus",
                tooltipType: 'title',
                cls: "pimcore_button_black",
                enableToggle: true,
                hidden: true,
                toggleHandler: function () {
                    var el = this.areaToolbarTrigger.areaToolbarElement;
                    if(el.getLocalX() < 0) {
                        el.setLocalX(0);
                        this.layout.addCls('pimcore_document_edit_panel_areatoolbar_button_pressed');
                    } else {
                        el.setLocalX(-1000);
                        this.layout.removeCls('pimcore_document_edit_panel_areatoolbar_button_pressed');
                    }
                }.bind(this)
            });

            this.highlightTagButton = new Ext.Button({
                tooltip: t("highlight_editable_elements"),
                iconCls: "pimcore_icon_highlight",
                enableToggle: true,
                handler: this.toggleTagHighlighting.bind(this)
            });

            var lbar = [this.areaToolbarTrigger, {
                iconCls: "pimcore_icon_reload",
                tooltip: t("refresh"),
                handler: this.reload.bind(this)
            },
            this.highlightTagButton,
            {
                tooltip: t("clear_content_of_current_view"),
                iconCls: "pimcore_icon_cleanup",
                handler: cleanupFunction.bind(this)
            }];

            this.addTargetingPanel(lbar, cleanupFunction);

            // edit panel configuration
            var config = {
                id: "document_content_" + this.document.id,
                html: html,
                title: t('edit'),
                scrollable: false,
                bodyCls: "pimcore_overflow_scrolling pimcore_document_edit_panel",
                forceLayout: true,
                hideMode: "offsets",
                iconCls: "pimcore_material_icon_edit pimcore_material_icon",
                lbar: lbar
            };

            if(typeof additionalConfig == "object") {
                config = Ext.apply(config, additionalConfig);
            }

            this.layout = new Ext.Panel(config);
            this.layout.on("resize", this.setLayoutFrameDimensions.bind(this));

            this.layout.on("afterrender", function () {
                Ext.get(this.iframeName).on('load', function() {
                    // this is to hide the mask if edit/startup.js isn't executed (eg. in case an error is shown)
                    // otherwise edit/startup.js will disable the loading mask
                    if(!this["frame"]) {
                        this.loadMask.hide();
                    }
                }.bind(this));

                this.loadMask = new Ext.LoadMask({
                    target: this.layout,
                    msg: t("please_wait")
                });

                this.loadMask.show();
            }.bind(this));
        }

        return this.layout;

    },

    toggleTagHighlighting: function (force) {

        if(!this['tagHighlightingActive']) {
            this.tagHighlightingActive = false;
        }

        if(this.tagHighlightingActive === force) {
            // noting to do in this case
            return;
        }

        var editables = this.frame.editables;
        var ed;
        for(var i=0; i<editables.length; i++) {
            ed = this.frame.Ext.get(editables[i].getId());

            if(!ed.hasCls("pimcore_tag_inc") && !ed.hasCls("pimcore_tag_areablock")
                && !ed.hasCls("pimcore_tag_block") && !ed.hasCls("pimcore_tag_area")) {
                if(!this.tagHighlightingActive) {
                    var mask = ed.mask();
                    mask.setStyle("background-color","#f5d833");
                    mask.setStyle("opacity","0.5");
                    mask.setStyle("pointer-events","none");
                } else {
                    // bring editables back to their state they were before
                    editables[i].setInherited(editables[i].getInherited());
                }
            }
        }

        this.tagHighlightingActive = !this.tagHighlightingActive;

        this.highlightTagButton.toggle(this.tagHighlightingActive);
    },

    addTargetingPanel: function(lbar, cleanupFunction) {
        if (!Ext.Array.contains(['page', 'snippet'], this.document.getType())) {
            return;
        }

        if (pimcore.globalmanager.get("target_group_store").getCount() === 0) {
            return;
        }

        this.targetGroupText = Ext.create('Ext.toolbar.TextItem', {
            scale: "medium",
            style: "-webkit-transform: rotate(270deg); -moz-transform: rotate(270deg); -o-transform: rotate(270deg); writing-mode: lr-tb;"
        });

        this.targetGroupStore = Ext.create('Ext.data.JsonStore', {
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_targeting_targetgrouplist', {'add-default': true})
            },
            fields: ["id", "text"],
            listeners: {
                load: function() {
                    this.updateTargetGroupText(this.targetGroup.getValue());
                }.bind(this)
            }
        });

        // add target group selection to toolbar
        this.targetGroup = new Ext.form.ComboBox({
            displayField:'text',
            valueField: "id",
            store: this.targetGroupStore,
            editable: false,
            triggerAction: 'all',
            width: 240,
            listeners: {
                select: function (el) {
                    if(this.document.isDirty()) {
                        Ext.Msg.confirm(t('warning'), t('you_have_unsaved_changes')
                            + "<br />" + t("continue") + "?",
                            function(btn){
                                if (btn === 'yes'){
                                    this.reload(true);
                                    this.updateTargetGroupText(this.targetGroup.getValue());
                                }
                            }.bind(this)
                        );
                    } else {
                        this.reload(true);
                        this.updateTargetGroupText(this.targetGroup.getValue());
                    }
                }.bind(this)
            }
        });

        this.targetGroupStore.load();

        lbar.push("->",
            this.targetGroupText,
            {
                tooltip: t("edit_content_for_target_group"),
                iconCls: "pimcore_icon_target_groups",
                arrowVisible: false,
                menuAlign: "tl",
                menu: [this.targetGroup]
            },
            {
                tooltip: t("clear_content_of_selected_target_group"),
                iconCls: "pimcore_icon_cleanup",
                handler: cleanupFunction.bind(this)
            }
        );
    },

    updateTargetGroupText: function(targetgroup) {
        var record = this.targetGroupStore.getById(targetgroup);

        if(record) {
            this.targetGroupText.update('&nbsp;&nbsp;<img src="/bundles/pimcoreadmin/img/flat-color-icons/manager.svg" style="height: 16px;" align="absbottom" />&nbsp;&nbsp;'
                + record.data.text);
        } else {
            this.targetGroupText.update('');
        }
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {
        Ext.get(this.iframeName).setStyle({
            height: (height-7) + "px"
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

        this.areaToolbarTrigger.toggle(false);

        if (this.reloadInProgress) {
            disableSaveToSession = true;
        }

        this.reloadInProgress = true;

        try {
            if(this["frame"] && !this.lastScrollposition) {
                this.lastScrollposition = this.frame.Ext.getBody().getScroll();
            }
        }
        catch (e) {
            console.log(e);
        }

        try {
            this.loadMask.show();
        } catch (e) {
            console.log(e);
        }

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
        var element;
        var iFrameEl;
        var i;


        // mask frames (iframes)
        try {
            // mask iframes
            if (typeof this.frame.Ext != "object") {
                return;
            }

            var iFrames = this.frame.document.getElementsByTagName("iframe");
            for (i = 0; i < iFrames.length; i++) {
                iFrameEl = Ext.get(iFrames[i]);
                width = iFrameEl.getWidth();
                height = iFrameEl.getHeight();

                var parentElement = iFrameEl.parent();
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

                element.addCls("pimcore_iframe_mask");
            }
        }
        catch (e) {
            console.log(e);
            console.log("there is no frame to mask");
        }
    },

    getValues: function () {

        var values = {};

        if (!this.frame || !this.frame.editablesReady) {
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
    },

    getEmptyRequiredEditables: function () {
        var emptyRequiredEditables = [];

        if (!this.frame || !this.frame.editablesReady) {
            throw "edit not available";
        }

        try {
            var requiredEditables = this.frame.requiredEditables;
            var editableName = "";

            for (var i = 0; i < requiredEditables.length; i++) {
                try {
                    if(requiredEditables[i].requiredError) {
                        editableName = requiredEditables[i].getName();
                        requiredEditables[i].checkValue(true);
                        emptyRequiredEditables.push(editableName);
                    }
                } catch (e) {
                }
            }
        }
        catch (e2) {
        }

        return emptyRequiredEditables;
    }

});

