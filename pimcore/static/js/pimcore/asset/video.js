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

pimcore.registerNS("pimcore.asset.video");
pimcore.asset.video = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.setType("video");

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "video");

        this.addLoadingPanel();
        this.id = intval(id);

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        this.getData();
    },

    getTabPanel: function () {
        var items = [];

        items.push(this.getEditPanel());

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
            this.previewPanel = new Ext.Panel({
                region: "center",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="/admin/asset/get-preview-video/id/' + this.id + '/" frameborder="0" id="asset_video_edit_' + this.id + '" name="asset_video_edit_' + this.id + '"></iframe>'
            });
            this.previewPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_video_edit_" + this.id).setStyle({
                    width: width + "px",
                    height: (height) + "px"
                });
            }.bind(this));

            this.previewFrameId = 'asset_video_edit_' + this.id;

            this.previewImagePanel = new Ext.Panel({
                width: 300,
                region: "east",
                bodyStyle: "display:none;",
                items: [{
                    xtype: "panel",
                    id: "pimcore_asset_video_imagepreview_" + this.id,
                    title: t("select_image_preview"),
                    bodyStyle: "text-align:center; padding:10px;min-height:150px;",
                    html: '<img align="center" src="/admin/asset/get-video-thumbnail/id/' + this.id  + '/width/250/aspectratio/true" />',
                    bbar: [{
                        xtype: "button",
                        text: t("use_current_player_position_as_preview"),
                        iconCls: "pimcore_icon_videoedit",
                        handler: function () {
                            try {
                                var time = window[this.previewFrameId].player.getTime();
                                Ext.getCmp("pimcore_asset_video_imagepreview_" + this.id).update('<img align="center" src="/admin/asset/get-video-thumbnail/id/' + this.id  + '/width/250/aspectratio/true/time/' + time  + '/settime/true" />');
                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this)
                    }]
                }]
            });

            this.previewImagePanel.on("afterrender", function () {
                this.checkFlowplayerInterval = window.setInterval(function () {
                    if(window[this.previewFrameId].flowplayer) {
                        this.previewImagePanel.body.setStyle({
                            display: "block"
                        });
                        clearInterval(this.checkFlowplayerInterval);
                    }
                }.bind(this), 1000);
            }.bind(this));

            this.previewImagePanel.on("beforedestroy", function () {
                clearInterval(this.checkFlowplayerInterval);
                try {
                    delete window[this.previewFrameId];
                } catch (e) {

                }
            }.bind(this));

            this.editPanel = new Ext.Panel({
                layout: "border",
                items: [this.previewPanel, this.previewImagePanel],
                title: t("preview"),
                iconCls: "pimcore_icon_tab_edit"
            });
        }

        return this.editPanel;
    }
});

