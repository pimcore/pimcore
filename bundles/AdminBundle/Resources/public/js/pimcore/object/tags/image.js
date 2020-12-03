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

pimcore.registerNS("pimcore.object.tags.image");
pimcore.object.tags.image = Class.create(pimcore.object.tags.abstract, {

    type: "image",
    dirty: false,

    initialize: function (data, fieldConfig) {
        if (data) {
            this.data = data;
        } else {
            this.data = {};
        }

        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function (field, forGridConfigPreview) {

        return {
            text: t(field.label), width: 100, sortable: false, dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record, rowIndex, colIndex, store, view) {
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                if (value && value.id) {

                    if (forGridConfigPreview) {
                        var params = {
                            id: value.id,
                            width: 88,
                            height: 20,
                            frame: true
                        };
                        var path = Routing.generate('pimcore_admin_asset_getimagethumbnail', params);
                        return '<img src="'+path+'" />';
                    }

                    var params = {
                        id: value.id,
                        width: 88,
                        height: 88,
                        frame: true
                    };

                    var path = Routing.generate('pimcore_admin_asset_getimagethumbnail', params);

                    return '<img src="'+path+'" style="width:88px; height:88px;"  />';
                }
            }.bind(this, field.key)
        };
    },

    getLayoutEdit: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 300;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 300;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            border: true,
            style: "padding-bottom: 10px",
            tbar: {
                overflowHandler: 'menu',
                items:
                    [{
                        xtype: "tbspacer",
                        width: 48,
                        height: 24,
                        cls: "pimcore_icon_droptarget_upload"

                    },
                        {
                            xtype: "tbtext",
                            text: "<b>" + this.fieldConfig.title + "</b>"
                        }, "->", {
                        xtype: "button",
                        iconCls: "pimcore_icon_upload",
                        overflowText: t("upload"),
                        cls: "pimcore_inline_upload",
                        handler: this.uploadDialog.bind(this)
                    }, {
                        xtype: "button",
                        iconCls: "pimcore_icon_open",
                        overflowText: t("open"),
                        handler: this.openImage.bind(this)
                    }, {
                        xtype: "button",
                        iconCls: "pimcore_icon_delete",
                        overflowText: t("delete"),
                        handler: this.empty.bind(this)
                    }, {
                        xtype: "button",
                        iconCls: "pimcore_icon_search",
                        overflowText: t("search"),
                        handler: this.openSearchEditor.bind(this)
                    }]
            },
            componentCls: "object_field object_field_type_" + this.type,
            bodyCls: "pimcore_droptarget_image pimcore_image_container"
        };

        this.component = new Ext.Panel(conf);


        this.component.on("afterrender", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function (e) {
                    return this.reference.component.getEl();
                },

                onNodeOver: function (target, dd, e, data) {
                    if (data.records.length === 1 && data.records[0].data.type === "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                },

                onNodeDrop: this.onNodeDrop.bind(this)
            });

            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            pimcore.helpers.registerAssetDnDSingleUpload(el.getEl().dom, this.fieldConfig.uploadPath, 'path', function (e) {
                if (e['asset']['type'] === "image") {
                    this.empty(true);
                    this.dirty = true;
                    this.data.id = e['asset']['id'];
                    this.updateImage();

                    return true;
                } else {
                    pimcore.helpers.showNotification(t("error"), t('unsupported_filetype'), "error");
                }
            }.bind(this), null, this.context);

            this.updateImage();

        }.bind(this));

        return this.component;
    },

    getLayoutShow: function () {
        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 300;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 300;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            border: true,
            style: "padding-bottom: 10px",
            tbar: {
                overflowHandler: 'menu',
                items:
                    [{
                        xtype: "tbtext",
                        text: "<b>" + this.fieldConfig.title + "</b>"
                    }, "->",{
                        xtype: "button",
                        iconCls: "pimcore_icon_open",
                        overflowText: t("open"),
                        handler: this.openImage.bind(this)
                    }]
            },
            cls: "object_field",
            bodyCls: "pimcore_droptarget_image pimcore_image_container"
        };

        this.component = new Ext.Panel(conf);

        this.component.on("afterrender", function (el) {
            el.getEl().on("contextmenu", this.onContextMenu.bind(this));
            this.updateImage();
        }.bind(this));

        return this.component;
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        data = data.records[0].data;
        if (data.type === "image") {
            this.empty(true);

            if (this.data.id !== data.id) {
                this.dirty = true;
            }
            this.data.id = data.id;

            this.updateImage();
            return true;
        }

        return false;
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
                type: ["asset"],
                subtype: {
                    asset: ["image"]
                }
            },
            {
                context: Ext.apply({scope: "objectEditor"}, this.getContext())
            });
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.fieldConfig.uploadPath, "path", function (res) {
            try {
                this.empty(true);

                var data = Ext.decode(res.response.responseText);
                if (data["id"] && data["type"] == "image") {
                    this.data.id = data["id"];
                    this.dirty = true;
                }
                this.updateImage();
            } catch (e) {
                console.log(e);
            }
        }.bind(this), null, this.context);
    },

    addDataFromSelector: function (item) {

        this.empty(true);

        if (item) {
            if (!this.data || this.data.id != item.id) {
                this.dirty = true;
            }

            this.data = this.data || {};
            this.data.id = item.id;

            this.updateImage();
            return true;
        }
    },

    openImage: function () {
        if (this.data) {
            pimcore.helpers.openAsset(this.data.id, "image");
        }
    },

    updateImage: function () {
        if(this.data && this.data["id"]) {
            // 5px padding (-10)
            var body = this.getBody();
            var width = body.getWidth() - 10;
            var height = this.fieldConfig.height - 60; // strage body.getHeight() returns 2? so we use the config instead

            var path = Routing.generate('pimcore_admin_asset_getimagethumbnail', {
                id: this.data.id,
                width: width,
                height: height,
                contain: true
            });

            body.removeCls("pimcore_droptarget_image");
            var innerBody = body.down('.x-autocontainer-innerCt');
            innerBody.setStyle({
                backgroundImage: "url(" + path + ")",
                backgroundPosition: "center center",
                backgroundRepeat: "no-repeat"
            });
            body.repaint();
        }
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)

        var elements = Ext.get(this.component.getEl().dom).query(".pimcore_image_container");
        var bodyId = elements[0].getAttribute("id");
        var body = Ext.get(bodyId);
        return body;

    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();

        if (this.data) {
            if(!this.fieldConfig.noteditable) {
                menu.add(new Ext.menu.Item({
                    text: t('empty'),
                    iconCls: "pimcore_icon_delete",
                    handler: function (item) {
                        item.parentMenu.destroy();

                        this.empty();
                    }.bind(this)
                }));
            }

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.openImage();
                }.bind(this)
            }));

            if (!this.fieldConfig.noteditable && this instanceof pimcore.object.tags.hotspotimage) {
                menu.add(new Ext.menu.Item({
                    text: t('select_specific_area_of_image'),
                    iconCls: "pimcore_icon_image_region",
                    handler: function (item) {
                        item.parentMenu.destroy();

                        this.openCropWindow();
                    }.bind(this)
                }));

                menu.add(new Ext.menu.Item({
                    text: t('add_marker_or_hotspots'),
                    iconCls: "pimcore_icon_image pimcore_icon_overlay_edit",
                    handler: function (item) {
                        item.parentMenu.destroy();

                        this.openHotspotWindow();
                    }.bind(this)
                }));
            }
        }

        if(!this.fieldConfig.noteditable) {
            menu.add(new Ext.menu.Item({
                text: t('search'),
                iconCls: "pimcore_icon_search",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.openSearchEditor();
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('upload'),
                cls: "pimcore_inline_upload",
                iconCls: "pimcore_icon_upload",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.uploadDialog();
                }.bind(this)
            }));
        }

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    empty: function () {
        if (this.data) {
            this.data = {};
        }

        var body = this.getBody();
        body.down('.x-autocontainer-innerCt').setStyle({
            backgroundImage: ""
        });
        body.addCls("pimcore_droptarget_image");
        this.dirty = true;
        body.repaint();
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
    },


    getCellEditValue: function () {
        return this.getValue();
    }
});
