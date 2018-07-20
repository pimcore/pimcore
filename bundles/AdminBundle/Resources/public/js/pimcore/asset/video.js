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

pimcore.registerNS("pimcore.asset.video");
pimcore.asset.video = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("video");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "video");

        var user = pimcore.globalmanager.get("user");

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "asset");
        }

        this.tagAssignment = new pimcore.element.tag.assignment(this, "asset");
        this.metadata = new pimcore.asset.metadata(this);

        this.getData();
    },

    getTabPanel: function () {
        var items = [];
        var user = pimcore.globalmanager.get("user");

        items.push(this.getEditPanel());

        if (this.isAllowed("publish")) {
            items.push(this.metadata.getLayout());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getEditPanel: function () {

        if (!this.editPanel) {
            this.previewFrameId = 'asset_video_edit_' + this.id;
            this.previewMode = 'video';
            if(this.data['videoInfo'] && this.data['videoInfo']['isVrVideo']) {
                this.previewMode = 'vr';
            }

            this.previewPanel = new Ext.Panel({
                region: "center",
                bodyCls: "pimcore_overflow_scrolling",
                html: ''
            });
            this.previewPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                if(this.previewMode == 'vr') {
                    this.initPreviewVr();
                } else {
                    this.initPreviewVideo();
                }
            }.bind(this));

            var date = new Date();

            this.previewImagePanel = new Ext.Panel({
                width: 300,
                region: "east",
                scrollable: 'y',
                items: [{
                    title: t("tools"),
                    bodyStyle: "padding: 10px;",
                    items: [{
                        xtype: "button",
                        text: t("standard_preview"),
                        iconCls: "pimcore_icon_image",
                        width: "100%",
                        textAlign: "left",
                        style: "margin-top: 5px",
                        handler: function () {
                            if(this.previewMode != 'video') {
                                this.initPreviewVideo();
                            }
                        }.bind(this)
                    }, {
                        xtype: "button",
                        text: t("360_viewer"),
                        iconCls: "pimcore_icon_vr",
                        width: "100%",
                        textAlign: "left",
                        style: "margin-top: 5px",
                        hidden: !(this.data['videoInfo'] && this.data['videoInfo']['previewUrl']),
                        handler: function () {
                            this.initPreviewVr();
                        }.bind(this)
                    }]
                }, {
                    xtype: "panel",
                    title: t("select_image_preview"),
                    height: 400,
                    bodyStyle: "padding:10px",
                    itemId: "inner",
                    hidden: true,
                    items: [{
                        xtype: "container",
                        style: "margin: 10px 0 10px 0;",
                        height: 200,
                        id: "pimcore_asset_video_imagepreview_" + this.id,
                        html: '<img class="pimcore_video_preview_image" align="center" src="/admin/asset/get-video-thumbnail?id='
                                        + this.id  + '&width=265&aspectratio=true&_dc=' + date.getTime() + '" />'
                    },{
                        xtype: "button",
                        text: t("use_current_player_position_as_preview"),
                        iconCls: "pimcore_icon_video pimcore_icon_overlay_edit",
                        width: "100%",
                        handler: function () {
                            try {
                                this.previewImagePanel.getComponent("inner").getComponent("assetPath").setValue("");

                                var time = window[this.previewFrameId].document.getElementById("video").currentTime;
                                var date = new Date();
                                var cmp = Ext.getCmp("pimcore_asset_video_imagepreview_"  + this.id);
                                cmp.update('<img class="pimcore_video_preview_image" align="center" src="/admin/asset/get-video-thumbnail?id='
                                    + this.id  + '&width=265&aspectratio=true&time=' + time  + '&settime=true&_dc='
                                    + date.getTime() + '" />');

                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this)
                    },{
                        xtype: "container",
                        border:false,
                        style: "padding: 10px 0 10px 0;",
                        html: t("or_specify_an_asset_image_below") + ":"
                    },{
                        xtype: "textfield",
                        itemId: "assetPath",
                        fieldCls: "input_drop_target",
                        width: "100%",
                        listeners: {
                            "render": function (el) {
                                new Ext.dd.DropZone(el.getEl(), {
                                    reference: el,
                                    ddGroup: "element",
                                    getTargetFromEvent: function(e) {
                                        return this.getEl();
                                    }.bind(el),

                                    onNodeOver : function(target, dd, e, data) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    },

                                    onNodeDrop : function (el, target, dd, e, data) {
                                        data = data.records[0].data;
                                        if (data.elementType == "asset") {
                                            el.setValue(data.path);

                                            var date = new Date();
                                            var cmp = Ext.getCmp("pimcore_asset_video_imagepreview_"  + this.id);
                                            cmp.update('<img align="center" src="/admin/asset/get-video-thumbnail?id='
                                                                + this.id  + '&width=265&aspectratio=true&image='
                                                                + data.id  + '&setimage=true&_dc='
                                                                + date.getTime() + '" />');
                                            return true;
                                        }
                                        return false;
                                    }.bind(this, el)
                                });
                            }.bind(this)
                        }
                    }]
                }]
            });

            this.previewImagePanel.on("beforedestroy", function () {
                clearInterval(this.checkVideoplayerInterval);
                try {
                    delete window[this.previewFrameId];
                } catch (e) {

                }
            }.bind(this));

            this.editPanel = new Ext.Panel({
                layout: "border",
                items: [this.previewPanel, this.previewImagePanel],
                title: t("preview"),
                iconCls: "pimcore_icon_preview"
            });
        }

        return this.editPanel;
    },

    initPreviewVr: function () {
        var previewContainerId = 'pimcore_video_preview_vr_' + this.id;
        this.previewPanel.update('<div id="' + previewContainerId + '" class="pimcore_asset_image_preview"></div>');
        var vrView = new VRView.Player('#' + previewContainerId, {
            video: this.data['videoInfo']['previewUrl'],
            is_stereo: (this.data['videoInfo']['width'] === this.data['videoInfo']['height']),
            width: 500,
            height: 350,
            hide_fullscreen_button: true
        });

        this.previewImagePanel.getComponent("inner").hide();
        this.previewMode = 'vr';
    },

    initPreviewVideo: function () {
        var frameUrl = pimcore.helpers.addCsrfTokenToUrl('/admin/asset/get-preview-video?id=' + this.id);
        var html = '<iframe src="' + frameUrl + '" frameborder="0" id="' + this.previewFrameId + '" name="' + this.previewFrameId + '" style="width:100%;"></iframe>';
        this.previewPanel.update(html);

        Ext.get(this.previewFrameId).setStyle({
            height: (this.previewPanel.getHeight() - 7) + "px"
        });

        this.previewMode = 'video';

        this.checkVideoplayerInterval = window.setInterval(function () {
            if(window[this.previewFrameId] && window[this.previewFrameId].document.getElementById("video")) {
                this.previewImagePanel.getComponent("inner").show();
                clearInterval(this.checkVideoplayerInterval);
            }
        }.bind(this), 1000);
    }
});

