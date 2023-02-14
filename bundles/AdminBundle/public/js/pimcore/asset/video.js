/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.asset.video");
/**
 * @private
 */
pimcore.asset.video = Class.create(pimcore.asset.asset, {

    initialize: function (id, options) {

        this.options = options;
        this.id = intval(id);
        this.setType("video");
        this.addLoadingPanel();

        const preOpenAssetVideo = new CustomEvent(pimcore.events.preOpenAsset, {
            detail: {
                object: this,
                type: "video"
            },
            cancelable: true
        });

        const isAllowed = document.dispatchEvent(preOpenAssetVideo);
        if (!isAllowed) {
            this.removeLoadingPanel();
            return;
        }


        var user = pimcore.globalmanager.get("user");

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "asset");
        }

        this.tagAssignment = new pimcore.element.tag.assignment(this, "asset");
        this.metadata = new pimcore.asset.metadata.editor(this);
        this.workflows = new pimcore.element.workflows(this, "asset");
        this.embeddedMetaData = new pimcore.asset.embedded_meta_data(this);

        this.getData();
    },

    getTabPanel: function () {
        var items = [];
        var user = pimcore.globalmanager.get("user");

        items.push(this.getEditPanel());

        var embeddedMetaDataPanel = this.embeddedMetaData.getPanel();
        if(embeddedMetaDataPanel) {
            items.push(embeddedMetaDataPanel);
        }

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

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region: 'center',
            deferredRender: true,
            enableTabScroll: true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getEditPanel: function () {

        if (!this.editPanel) {
            this.previewFrameId = 'asset_video_edit_' + this.id;

            this.previewPanel = new Ext.Panel({
                region: "center",
                bodyCls: "pimcore_overflow_scrolling",
                html: ''
            });
            this.previewPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                this.initPreviewVideo();
            }.bind(this));

            var date = new Date();
            var detailsData = [];

            if (this.data.customSettings['videoWidth']) {
                detailsData[t("width")] = this.data.customSettings.videoWidth;
            }

            if (this.data.customSettings['videoHeight']) {
                detailsData[t("height")] = this.data.customSettings.videoHeight;
            }

            if (this.data.customSettings['duration']) {
                detailsData[t("duration")] = pimcore.helpers.formatTimeDuration(this.data.customSettings.duration);
            }

            var dimensionPanel = new Ext.create('Ext.grid.property.Grid', {
                title: t("details"),
                source: detailsData,
                autoHeight: true,

                clicksToEdit: 1000,
                viewConfig: {
                    forceFit: true,
                    scrollOffset: 2
                }
            });
            dimensionPanel.plugins[0].disable();
            dimensionPanel.getStore().sort("name", "DESC");

            var url = Routing.generate('pimcore_admin_asset_getvideothumbnail', {
                id: this.id,
                width: 265,
                aspectratio: true,
                '_dc': date.getTime()
            });

            var thumbnailsStore = new Ext.data.JsonStore({
                autoLoad: true,
                forceSelection: true,
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_settings_videothumbnail_list')
                },
                fields: ['id', 'text']
            });

            this.previewImagePanel = new Ext.Panel({
                width: 300,
                region: "east",
                scrollable: 'y',
                items: [{
                    title: t("tools"),
                    bodyStyle: "padding: 10px;",
                    items: [ {
                        xtype: "combo",
                        name: "thumbnail",
                        fieldLabel: t("thumbnail"),
                        width: "100%",
                        style: "margin-top: 5px",
                        store: thumbnailsStore,
                        value: "pimcore-system-treepreview",
                        editable: false,
                        valueField: "id",
                        displayField: "text",
                        listeners: {
                            select: function (el) {
                                this.previewImagePanel.getComponent("inner").getComponent("set_video_poser_button").setDisabled((el.value !== "pimcore-system-treepreview"));
                                this.initPreviewVideo(el.value);
                            }.bind(this)
                        }
                    }]
                }, dimensionPanel, {
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
                        html: '<img class="pimcore_video_preview_image" align="center" src="'+url+'" />'
                    }, {
                        xtype: "button",
                        text: t("use_current_player_position_as_preview"),
                        iconCls: "pimcore_icon_video pimcore_icon_overlay_edit",
                        itemId: "set_video_poser_button",
                        width: "100%",
                        handler: function () {
                            try {
                                this.previewImagePanel.getComponent("inner").getComponent("assetPath").setValue("");

                                var time = window[this.previewFrameId].document.getElementById("video").currentTime;
                                var date = new Date();
                                var cmp = Ext.getCmp("pimcore_asset_video_imagepreview_" + this.id);

                                var url = Routing.generate('pimcore_admin_asset_getvideothumbnail', {
                                    id: this.id,
                                    width: 265,
                                    aspectratio: true,
                                    time: time,
                                    settime: true,
                                    '_dc': date.getTime()
                                });

                                cmp.update('<img class="pimcore_video_preview_image" align="center" src="'+url+'" />');

                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this)
                    }, {
                        xtype: "container",
                        border: false,
                        style: "padding: 10px 0 10px 0;",
                        html: t("or_specify_an_asset_image_below") + ":"
                    }, {
                        xtype: "textfield",
                        itemId: "assetPath",
                        fieldCls: "input_drop_target",
                        width: "100%",
                        listeners: {
                            "render": function (el) {
                                new Ext.dd.DropZone(el.getEl(), {
                                    reference: el,
                                    ddGroup: "element",
                                    getTargetFromEvent: function (e) {
                                        return this.getEl();
                                    }.bind(el),

                                    onNodeOver: function (target, dd, e, data) {
                                        if (data.records.length == 1 && data.records[0].data.elementType == "asset") {
                                            return Ext.dd.DropZone.prototype.dropAllowed;
                                        }
                                    },

                                    onNodeDrop: function (el, target, dd, e, data) {
                                        if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                            data = data.records[0].data;
                                            if (data.elementType == "asset") {
                                                el.setValue(data.path);
                                                var date = new Date();
                                                var url = Routing.generate('pimcore_admin_asset_getvideothumbnail', {
                                                    id: this.id,
                                                    image: data.id,
                                                    width: 265,
                                                    aspectratio: true,
                                                    setimage: true,
                                                    '_dc': date.getTime()
                                                });
                                                var cmp = Ext.getCmp("pimcore_asset_video_imagepreview_" + this.id);
                                                cmp.update('<img align="center" src="'+url+'" />');
                                                return true;
                                            }
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
                iconCls: "pimcore_material_icon_devices pimcore_material_icon"
            });
        }

        return this.editPanel;
    },

    initPreviewVideo: function (config = "pimcore-system-treepreview") {
        var frameUrl = Routing.generate('pimcore_admin_asset_getpreviewvideo', {id: this.id, config: config});
        var html = '<iframe src="' + frameUrl + '" frameborder="0" id="' + this.previewFrameId + '" name="' + this.previewFrameId + '" style="width:100%;"></iframe>';
        this.previewPanel.update(html);

        Ext.get(this.previewFrameId).setStyle({
            height: (this.previewPanel.getHeight() - 7) + "px"
        });

        this.checkVideoplayerInterval = window.setInterval(function () {
            if (window[this.previewFrameId] && window[this.previewFrameId].document.getElementById("video")) {
                this.previewImagePanel.getComponent("inner").show();
                clearInterval(this.checkVideoplayerInterval);
            }
        }.bind(this), 1000);
    }
});

