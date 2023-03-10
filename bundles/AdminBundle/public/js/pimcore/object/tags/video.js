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

pimcore.registerNS("pimcore.object.tags.video");
/**
 * @private
 */
pimcore.object.tags.video = Class.create(pimcore.object.tags.abstract, {

    type: "video",
    dirty: false,

    initialize: function (data, fieldConfig) {
        if (data) {
            this.data = data;
        } else {
            this.data = {};
        }

        this.data.allowedTypes = fieldConfig.allowedTypes;

        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function (field) {

        return {
            text: t(field.label), width: 100, sortable: false, dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited
                    == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                if (value && value.id) {
                    var path = Routing.generate('pimcore_admin_asset_getvideothumbnail', {
                        id: value.id,
                        width: 88,
                        height: 88,
                        frame: true
                    });
                    return '<img src="' + path + '" loading="lazy" />';
                }
            }.bind(this, field.key)
        };
    },

    getLayoutEdit: function () {
        if (!this.fieldConfig.width) {
            this.fieldConfig.width = 300;
        }
        if (!this.fieldConfig.height) {
            this.fieldConfig.height = 300;
        }

        const allowedTypes = this.fieldConfig.allowedTypes;

        const toolbarItems = [];

        if (allowedTypes.includes("asset")) {
            toolbarItems.push(
                {
                    xtype: "tbspacer",
                    width: 48,
                    height: 24,
                    cls: "pimcore_icon_droptarget_upload"
                }
            )
        }

        toolbarItems.push({
            xtype: "tbtext",
            text: "<b>" + this.fieldConfig.title + "</b>",
        }, "->", {
            xtype: "button",
            overflowText: this.fieldConfig.title,
            iconCls: "pimcore_icon_video pimcore_icon_overlay_edit",
            handler: this.openEdit.bind(this)
        });

        if (allowedTypes.includes("asset")) {
            toolbarItems.push(
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_upload",
                    overflowText: t("upload"),
                    cls: "pimcore_inline_upload",
                    handler: this.uploadDialog.bind(this)
                }
            )
        }

        toolbarItems.push(
            {
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                overflowText: t('delete'),
                handler: this.empty.bind(this)
            }
        );

        let bodyClass = "pimcore_video_container";

        if (allowedTypes.includes("asset")) {
            bodyClass = "pimcore_droptarget_image " + bodyClass;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            border: true,
            style: "padding-bottom: 10px",
            tbar: {
                overflowHandler: 'menu',
                items: toolbarItems
            },
            componentCls: this.getWrapperClassNames(),
            bodyCls: bodyClass
        };

        this.component = new Ext.Panel(conf);


        this.component.on("afterrender", function (el) {
            if (allowedTypes.includes("asset")) {

                // add drop zone
                new Ext.dd.DropZone(el.getEl(), {
                    reference: this,
                    ddGroup: "element",
                    getTargetFromEvent: function (e) {
                        return this.reference.component.getEl();
                    },

                    onNodeOver: function (target, dd, e, data) {
                        if (data.records.length === 1 && data.records[0].data.type === "video") {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }
                    },

                    onNodeDrop: this.onNodeDrop.bind(this)
                });

                el.getEl().on("contextmenu", this.onContextMenu.bind(this));

                pimcore.helpers.registerAssetDnDSingleUpload(el.getEl().dom, this.fieldConfig.uploadPath, 'path', function (e) {
                    if (e['asset']['type'] === "video") {
                        this.empty(true);
                        this.dirty = true;

                        this.data.type = "asset";
                        this.data.id = e['asset']['id'];
                        this.data.data = e['asset']['path'];

                        this.data.poster = null;
                        this.data.title = '';
                        this.data.description = '';

                        this.updateVideo();

                        return true;
                    } else {
                        pimcore.helpers.showNotification(t("error"), t('unsupported_filetype'), "error");
                    }
                }.bind(this), null, this.context);
            }

            if (this.data) {
                this.updateVideo();
            }
        }.bind(this));

        return this.component;
    },

    getLayoutShow: function () {
        if (!this.fieldConfig.width) {
            this.fieldConfig.width = 300;
        }
        if (!this.fieldConfig.height) {
            this.fieldConfig.height = 300;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            title: this.fieldConfig.title,
            border: true,
            style: "padding-bottom: 10px",
            cls: "object_field object_field_type_" + this.type,
            bodyCls: "pimcore_video_container"
        };

        this.component = new Ext.Panel(conf);

        this.component.on("afterrender", function (el) {
            if (this.data) {
                this.updateVideo();
            }
        }.bind(this));

        return this.component;
    },

    addDataFromSelector: function (item) {

        this.empty();

        if (item) {
            this.fieldData.setValue(item.fullpath);
            return true;
        }
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.fieldConfig.uploadPath, "path", function (res) {
                try {
                    this.empty(true);

                    var data = Ext.decode(res.response.responseText);
                    if (data["id"] && data["type"] == "video") {
                        this.data.id = data["id"];
                        this.dirty = true;
                    }
                    this.updateVideo();
                } catch (e) {
                    console.log(e);
                }
            }.bind(this),
            function (res) {
                const response = Ext.decode(res.response.responseText);
                if (response && response.success === false) {
                    pimcore.helpers.showNotification(t("error"), response.message, "error",
                        res.response.responseText);
                } else {
                    pimcore.helpers.showNotification(t("error"), res, "error",
                        res.response.responseText);
                }
            }.bind(this), this.context);
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        data = data.records[0].data;
        if (data.type === "video") {
            this.empty(true);

            if (this.data.id !== data.id) {
                this.dirty = true;
            }
            this.data.id = data.id;
            this.data.type = "asset";
            this.data.data = data.path;

            this.data.poster = null;
            this.data.title = '';
            this.data.description = '';

            this.updateVideo();
            return true;
        }

        return false;
    },

    openEdit: function () {
        this.data["path"] = this.data["data"];
        this.window = pimcore.helpers.editmode.openVideoEditPanel(this.data, {

            save: function () {
                this.window.hide();

                var values = this.window.getComponent("form").getForm().getFieldValues();
                values["data"] = values["path"];
                delete values["path"];
                values["allowedTypes"] = this.data.allowedTypes;

                var match, regExp;

                if (values["type"] == "youtube") {
                    regExp = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
                    match = values["data"].match(regExp);
                    if (match && match[2].length == 11) {
                        values["data"] = match[2];
                    }
                } else if (values["type"] == "vimeo") {
                    regExp = /vimeo.com\/(\d+)($|\/)/;
                    match = values["data"].match(regExp);
                    if (match && match[1]) {
                        values["data"] = match[1];
                    }
                } else if (values["type"] == "dailymotion") {
                    regExp = /dailymotion.*\/video\/([^_]+)/;
                    match = values["data"].match(regExp);
                    if (match && match[1]) {
                        values["data"] = match[1];
                    }
                }

                this.data = values;

                this.dirty = true;
                this.updateVideo();
            }.bind(this),
            cancel: function () {
                this.window.hide();
            }.bind(this)
        });
    },

    updateVideo: function () {

        var width = this.component.getWidth();
        //need to geht height this way, because element has no height at afterrender (whyever)
        var height = this.fieldConfig.height - 55;

        var content = '';

        if (this.data.type == "asset" && pimcore.settings.videoconverter) {
            var path = Routing.generate('pimcore_admin_asset_getvideothumbnail', {
                path: this.data.data,
                width: width,
                height: height,
                frame: true
            });

            content = '<img src="'+path+'" />';
        } else if (this.data.type == "youtube") {
            if (this.data.data.indexOf('PL') === 0) {
                content = '<iframe width="' + width + '" height="' + height + '" src="https://www.youtube-nocookie.com/embed/videoseries?list=' + this.data.data + '" frameborder="0" allowfullscreen></iframe>';
            } else {
                content = '<iframe width="' + width + '" height="' + height + '" src="https://www.youtube-nocookie.com/embed/' + this.data.data + '" frameborder="0" allowfullscreen></iframe>';
            }
        } else if (this.data.type == "vimeo") {
            content = '<iframe src="https://player.vimeo.com/video/' + this.data.data + '?title=0&amp;byline=0&amp;portrait=0" width="' + width + '" height="' + height + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        } else if (this.data.type == "dailymotion") {
            content = '<iframe src="https://www.dailymotion.com/embed/video/' + this.data.data + '" width="' + width + '" height="' + height + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        }

        this.component.setHtml(content);
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
        }

        if (!this.fieldConfig.noteditable && pimcore.helpers.hasSearchImplementation()) {
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
        let allowedTypes = this.fieldConfig.allowedTypes;
        this.data = {
            type: "",
            data: "",
            allowedTypes: allowedTypes,
        };

        this.component.setHtml("");

        this.dirty = true;
    },

    getValue: function () {
        delete this.data["path"];
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
