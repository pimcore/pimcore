/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.asset.image");
pimcore.asset.image = Class.create(pimcore.asset.asset, {

    initialize: function (id) {

        this.id = intval(id);
        this.setType("image");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "image");

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

        items.push(this.getDisplayPanel());

        if (!pimcore.settings.asset_hide_edit && (this.isAllowed("save") || this.isAllowed("publish"))) {
            items.push(this.getEditPanel());
        }

        var exifPanel = this.getExifPanel();
        if(exifPanel) {
            items.push(exifPanel);
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
            this.editPanel = new Ext.Panel({
                title: t("edit"),
                html: '<iframe src="/admin/asset/image-editor/id/' + this.id + '" frameborder="0" ' +
                'style="width: 100%;" id="asset_image_edit_' + this.id + '"></iframe>',
                iconCls: "pimcore_icon_edit"
            });
            this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_image_edit_" + this.id).setStyle({
                    height: (height - 7) + "px"
                });
            }.bind(this));
        }

        return this.editPanel;
    },

    getExifPanel: function () {
        if (!this.exifPanel) {

            if(!this.data["imageInfo"] || (!this.data["imageInfo"]["exif"] && !this.data["imageInfo"]["iptc"])) {
                return false;
            }

            var exifPanel = new Ext.grid.PropertyGrid({
                title: 'EXIF',
                flex: 1,
                border: true,
                source: this.data["imageInfo"]["exif"] || [],
                clicksToEdit: 1000
            });
            exifPanel.plugins[0].disable();

            var iptcPanel = new Ext.grid.PropertyGrid({
                title: 'IPTC',
                flex: 1,
                border: true,
                source: this.data["imageInfo"]["iptc"] || [],
                clicksToEdit: 1000
            });
            iptcPanel.plugins[0].disable();

            this.exifPanel = new Ext.Panel({
                title: "EXIF/IPTC",
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                iconCls: "pimcore_icon_exif",
                items: [exifPanel, iptcPanel]
            });
        }

        return this.exifPanel;
    },

    getDisplayPanel: function () {

        if (!this.displayPanel) {

            var date = new Date();
            var dc = date.getTime();

            var details = [];


            if (this.data.imageInfo.dimensions) {

                var dimensionPanel = new Ext.create('Ext.grid.property.Grid', {
                    title: t("dimensions"),
                    source: this.data.imageInfo.dimensions,
                    autoHeight: true,

                    clicksToEdit: 1000,
                    viewConfig: {
                        forceFit: true,
                        scrollOffset: 2
                    }
                });
                dimensionPanel.plugins[0].disable();
                dimensionPanel.getStore().sort("name", "DESC");

                details.push(dimensionPanel);
            }

            var downloadDefaultWidth = 800;

            if (this.data.imageInfo && this.data.imageInfo) {
                if (this.data.imageInfo.dimensions && this.data.imageInfo.dimensions.width) {
                    downloadDefaultWidth = intval(this.data.imageInfo.dimensions.width);
                }
            }

            var downloadShortcutsHandler = function (type) {
                pimcore.helpers.download("/admin/asset/download-image-thumbnail/id/" + this.id   + "/?type=" + type);
            };

            this.downloadBox = new Ext.Panel({
                title: t("download"),
                bodyStyle: "padding: 10px;",
                style: "margin: 10px 0 10px 0",
                items: [{
                    xtype: "button",
                    iconCls: "pimcore_icon_image",
                    width: 260,
                    textAlign: "left",
                    style: "margin-bottom: 5px",
                    text: t("original_file"),
                    handler: function () {
                        pimcore.helpers.download("/admin/asset/download/id/" + this.data.id);
                    }.bind(this)
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_world",
                    width: 260,
                    textAlign: "left",
                    style: "margin-bottom: 5px",
                    text: t("web_format"),
                    handler: downloadShortcutsHandler.bind(this, "web")
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_print",
                    width: 260,
                    textAlign: "left",
                    style: "margin-bottom: 5px",
                    text: t("print_format"),
                    handler: downloadShortcutsHandler.bind(this, "print")
                },{
                    xtype: "button",
                    iconCls: "pimcore_icon_docx",
                    width: 260,
                    textAlign: "left",
                    style: "margin-bottom: 5px",
                    text: t("office_format"),
                    handler: downloadShortcutsHandler.bind(this, "office")
                }]
            });
            details.push(this.downloadBox);


            this.customDownloadBox = new Ext.form.FormPanel({
                title: t("custom_download"),
                bodyStyle: "padding: 10px;",
                style: "margin: 10px 0 10px 0",
                items: [{
                    xtype: "combo",
                    triggerAction: "all",
                    name: "format",
                    fieldLabel: t("format"),
                    store: [["JPEG", "JPEG"], ["PNG", "PNG"]],
                    mode: "local",
                    value: "JPEG",
                    editable: false,
                    listeners: {
                        select: function (el) {
                            var dpiField = this.customDownloadBox.getComponent("dpi");
                            if(el.getValue() == "JPEG") {
                                dpiField.enable();
                            } else {
                                dpiField.disable();
                            }
                        }.bind(this)
                    }
                }, {
                    xtype: "combo",
                    triggerAction: "all",
                    name: "resize_mode",
                    itemId: "resize_mode",
                    fieldLabel: t("resize_mode"),
                    forceSelection: true,
                    store: [["scaleByWidth", t("scalebywidth")], ["scaleByHeight", t("scalebyheight")], ["resize", t("resize")]],
                    mode: "local",
                    value: "scaleByWidth",
                    editable: false,
                    listeners: {
                        select: function (el) {
                            var widthField = this.customDownloadBox.getComponent("width");
                            var heightField = this.customDownloadBox.getComponent("height");

                            if(el.getValue() == "scalebywidth") {
                                widthField.enable();
                                heightField.disable();
                            } else if(el.getValue() == "scalebyheight") {
                                widthField.disable();
                                heightField.enable();
                            } else {
                                widthField.enable();
                                heightField.enable();
                            }
                        }.bind(this)
                    }
                }, {
                    xtype: "numberfield",
                    name: "width",
                    itemId: "width",
                    fieldLabel: t("width"),
                    value: downloadDefaultWidth
                }, {
                    xtype: "numberfield",
                    name: "height",
                    itemId: "height",
                    fieldLabel: t("height"),
                    disabled: true
                }, {
                    xtype: "numberfield",
                    name: "quality",
                    fieldLabel: t("quality"),
                    value: 95
                }, {
                    xtype: "numberfield",
                    name: "dpi",
                    itemId: "dpi",
                    fieldLabel: "DPI",
                    value: 300,
                    disabled: !this.data.imageInfo["exiftoolAvailable"]
                }],
                buttons: [{
                    text: t("download"),
                    iconCls: "pimcore_icon_download",
                    handler: function () {
                        var config = this.customDownloadBox.getForm().getFieldValues();
                        pimcore.helpers.download("/admin/asset/download-image-thumbnail/id/" + this.id
                            + "/?config=" + Ext.encode(config));
                    }.bind(this)
                }]
            });
            details.push(this.customDownloadBox);

            this.displayPanel = new Ext.Panel({
                title: t("view"),
                layout: "border",
                iconCls: "pimcore_icon_view",
                items: [{
                    region: "center",
                    html: '&nbsp;',
                    bodyStyle: "background: url(/admin/asset/get-image-thumbnail/id/" + this.id +
                    "/treepreview/true_dc=" + dc + ") center center no-repeat;"
                }, {
                    region: "east",
                    width: 300,
                    items: details,
                    scrollable: "y"
                }]
            });
        }

        return this.displayPanel;
    }
});
