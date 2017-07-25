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

    getGridColumnConfig: function (field) {

        return {
            header: ts(field.label), width: 100, sortable: false, dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited
                    == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                if (value && value.id) {
                    return '<img src="/admin/asset/get-image-thumbnail?id=' + value.id
                        + '&width=88&height=88&frame=true" />';
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
            tbar: [{
                xtype: "tbspacer",
                width: 20,
                height: 16,
                cls: "pimcore_icon_droptarget"
            },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.fieldConfig.title + "</b>"
                }, "->", {
                    xtype: "button",
                    iconCls: "pimcore_icon_upload",
                    cls: "pimcore_inline_upload",
                    handler: this.uploadDialog.bind(this)
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_edit",
                    handler: this.openImage.bind(this)
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: this.empty.bind(this)
                }, {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this)
                }],
            componentCls: "object_field",
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

                    var record = data.records[0];
                    if (record.data.type == "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }
                },

                onNodeDrop: this.onNodeDrop.bind(this)
            });

            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            if (this.data) {
                this.updateImage();
            }

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
            title: this.fieldConfig.title,
            border: true,
            style: "padding-bottom: 10px",
            cls: "object_field",
            bodyCls: "pimcore_droptarget_image pimcore_image_container"
        };

        this.component = new Ext.Panel(conf);

        this.component.on("afterrender", function (el) {
            if (this.data) {
                this.updateImage();
            }
        }.bind(this));

        return this.component;
    },

    onNodeDrop: function (target, dd, e, data) {

        this.empty(true);

        var record = data.records[0];
        if (record.data.type == "image") {
            if (this.data.id != record.data.id) {
                this.dirty = true;
            }
            this.data.id = record.data.id;

            this.updateImage();
            return true;
        }
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
                this.empty();

                var data = Ext.decode(res.response.responseText);
                if (data["id"] && data["type"] == "image") {
                    this.data.id = data["id"];
                    this.dirty = true;
                }
                this.updateImage();
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    addDataFromSelector: function (item) {

        this.empty();

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


        // 5px padding (-10)
        var body = this.getBody();
        var width = body.getWidth() - 10;
        var height = this.fieldConfig.height - 60; // strage body.getHeight() returns 2? so we use the config instead

        var path = "/admin/asset/get-image-thumbnail?id=" + this.data.id + "&width=" + width + "&height=" + height
            + "&contain=true";

        body = body.down('.x-autocontainer-innerCt');
        body.setStyle({
            backgroundImage: "url(" + path + ")",
            backgroundPosition: "center center",
            backgroundRepeat: "no-repeat"
        });
        body.repaint();
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
            menu.add(new Ext.menu.Item({
                text: t('empty'),
                iconCls: "pimcore_icon_delete",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.empty();
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.openImage();
                }.bind(this)
            }));

            if (this instanceof pimcore.object.tags.hotspotimage) {

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

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    empty: function () {
        if (this.data) {
            this.data = {};
        }

        this.getBody().down('.x-autocontainer-innerCt').setStyle({
            backgroundImage: ""
        });
        this.dirty = true;
        this.getBody().repaint();
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        if (this.getValue()) {
            return false;
        }
        return true;
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