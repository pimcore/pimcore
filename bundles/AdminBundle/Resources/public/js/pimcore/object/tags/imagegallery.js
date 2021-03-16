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

pimcore.registerNS("pimcore.object.tags.imageGallery");
pimcore.object.tags.imageGallery = Class.create(pimcore.object.tags.abstract, {

    type: "imageGallery",
    data: null,

    initialize: function (data, fieldConfig) {
        if (data) {
            this.data = data;
        } else {
            this.data = [];
        }
        this.dirty = false;
        this.fieldConfig = fieldConfig;
        this.hotspotConfig = {
            condensed: true,
            gallery: true,
            callback: this
        };
    },

    getGridColumnConfig: function (field) {

        return {
            text: t(field.label), width: 100, sortable: false, dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                var content = '';

                if(value && value.length > 0) {

                    for(var i = 0; i < value.length; i++) {

                        var item = value[i];

                        var route = 'pimcore_admin_asset_getimagethumbnail';
                        var params = {
                            width: 88,
                            height: 88,
                            frame: true,
                            id: item.id
                        };
                        if (item.crop) {
                            params = Ext.merge(params, item.crop);
                        }
                        var url = Routing.generate(route, params);
                        var tag = '<img style="padding-left: 3px" src="'+url+'">';

                        content += tag;
                    }

                }

                return content;
            }.bind(this, field.key)
        };
    },

    wrap: function (hotspotImageTag) {

        hotspotImageEditPanel = hotspotImageTag.getLayoutEdit();

        var fieldConfig = this.getDefaultFieldConfig();
        var dragConf = {
            __tag: hotspotImageTag,
            width: fieldConfig.width,
            height: fieldConfig.height,
            items: [hotspotImageEditPanel],
            anchor: '100%',
            style: {
                float: 'left'
            },
            draggable: {
                moveOnDrag: false
            }
        };

        var draggableComponent = Ext.create('Ext.panel.Panel', dragConf);
        hotspotImageTag.setContainer(draggableComponent);
        return draggableComponent;

    },

    getDefaultFieldConfig: function () {
        var itemWidth = this.fieldConfig.width ? this.fieldConfig.width : 150;
        var itemHeight = this.fieldConfig.height ? this.fieldConfig.height : 150;

        let fieldConfig = Object.assign({}, this.fieldConfig, {
            width: itemWidth,
            height: itemHeight,
        });

        return fieldConfig;
    },

    getFakeItems: function () {
        var items = [];

        for (var i = 0; i < 15; i++) {
            var data = {
                id: 40 + i
            }

            var fieldConfig = this.getDefaultFieldConfig();
            var hotspotImage = new pimcore.object.tags.hotspotimage(data, fieldConfig, this.hotspotConfig);
            hotspotImage.updateContext(this.context);
            var draggableComponent = this.wrap(hotspotImage);
            items.push(draggableComponent);
        }
        return items;
    },

    getLayoutShow: function() {
        var layout = this.getLayoutEdit();
        layout.disable();
        return layout;
    },

    getLayoutEdit: function () {

        var items = [];

        for (var i = 0; i < this.data.length; i++) {
            var itemData = this.data[i];
            var fieldConfig = this.getDefaultFieldConfig();
            var hotspotImage = new pimcore.object.tags.hotspotimage(itemData, fieldConfig, this.hotspotConfig);
            hotspotImage.updateContext(this.context);
            var draggableComponent = this.wrap(hotspotImage);
            items.push(draggableComponent);

        }
        // var items = this.getFakeItems();

        var fieldConfig = this.getDefaultFieldConfig();

        var placeholderComponent = this.createPlaceholder(fieldConfig);
        items.push(placeholderComponent);

        var toolbarCfg = {
            region: "north",
            border: false,
            items: [
                {
                    xtype: "tbtext",
                    text: "<b>" + this.fieldConfig.title + "</b>"
                },
                {
                    xtype: "button",
                    tooltip: t("add"),
                    overflowText: t('add'),
                    iconCls: "pimcore_icon_add",

                    handler: function () {
                        this.add(null);
                    }.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: function () {
                        this.openSearchEditor();
                    }.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    overflowText: t('empty'),
                    handler: function() {
                        Ext.suspendLayouts();
                        while (this.component.items.length > 1) {
                            var item = this.component.items.getAt(0);
                            this.component.remove(item);
                            this.markDirty();
                        }
                        Ext.resumeLayouts();
                        this.component.updateLayout();
                    }.bind(this)
                }
            ]
        };

        var toolbar = new Ext.Toolbar(toolbarCfg);

        var conf = {
            border: true,
            layout: {
                type: 'column',
                columns: 1
            },
            // title: this.fieldConfig.title,
            items: items,
            proxyConfig: {
                width: fieldConfig.width,
                height: fieldConfig.height,
                respectPlaceholder: true,
                callback: this
            },
            componentCls: "object_field object_field_type_" + this.type,
            style: {
                margin: '0 0 10px 0',
            },
            tbar: toolbar
        };

        this.component = new pimcore.object.helpers.ImageGalleryPanel(conf);

        return this.component;
    },

    createPlaceholder: function (fieldConfig) {
        var placeholderConf = {
            width: fieldConfig.width,
            height: fieldConfig.height,
            anchor: '100%',
            style: {
                float: 'left',
                border: 1,
                borderWidth: 1,
                borderColor: 'lightGray',
                borderStyle: 'dashed'
            },
            layout: {
                type: 'table',
                columns: 1,
                tableAttrs: {
                    style: {
                        width: '100%',
                        height: '100%'
                    }
                },
                tdAttrs: {
                    align: 'center',
                    valign: 'middle',
                },
            },
            items: [{
                xtype: 'label',
                layout: 'fit',
                text: t('drop_me_here')
            }]
        };


        var placeHolder = Ext.create('Ext.panel.Panel', placeholderConf);

        placeHolder.on("afterrender", function (el) {
            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function (e) {
                    return this.reference.component.getEl();
                },

                onNodeOver: function (target, dd, e, data) {
                    var dropAllowed=true;
                    data.records.forEach(function (record) {
                        if (record.data.type !== "image") {
                            dropAllowed=false;
                        }
                    });
                    if (dropAllowed) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                },

                onNodeDrop: function (target, dd, e, data) {

                    var objectField=this;
                    objectField.dirty = true;
                    data.records.forEach(function (record) {
                        if (record.data.type !== "image") {
                            return;
                        }

                        var recordData = {
                            id: record.data.id
                        };

                        var fieldConfig = objectField.getDefaultFieldConfig();
                        fieldConfig.title = record.data.path;

                        var hotspotImage = new pimcore.object.tags.hotspotimage(recordData, fieldConfig, objectField.hotspotConfig);
                        hotspotImage.updateContext(objectField.context);
                        var itemCount = objectField.component.items.length;

                        var draggableComponent = objectField.wrap(hotspotImage);
                        objectField.component.insert(itemCount - 1, draggableComponent);
                    });
                }.bind(this)
            });


        }.bind(this));

        return placeHolder;
    },

    getValue: function () {

        var value = [];

        var itemCount = this.component.items.length;
        for (var i = 0; i < itemCount; i++) {
            var item = this.component.items.getAt(i);
            var tag = item.__tag;
            if (tag) {
                value.push(tag.getValue());
            }
        }

        return value;
    },

    getCellEditValue: function () {
        return this.getValue();
    },

    isDirty: function () {
        if (this.dirty) {
            return true;
        }
        var itemCount = this.component.items.length;
        for (var i = 0; i < itemCount; i++) {
            var item = this.component.items.getAt(i);
            var tag = item.__tag;
            if (tag) {
                if (tag.isDirty()) {
                    return true;
                }
            }
        }

        return false;
    },

    move: function (direction, item) {

        if (direction == 1) {
            if (this.component.items.getAt(this.component.items.length - 2) != item) {
                this.component.moveAfter(item, item.nextSibling());
            }
        } else {
            if (this.component.items.getAt(0) != item) {
                this.component.moveBefore(item, item.previousSibling());
            }
        }
        this.markDirty();
    },

    add: function (me) {

        this.markDirty();
        var pos = 0;

        var itemCount = this.component.items.length;
        if (me) {


            for (var i = 0; i < itemCount; i++) {
                var item = this.component.items.getAt(i);
                if (item == me) {
                    pos = i;
                    break;
                }
            }
        } else {
            pos = itemCount - 2;
        }

        var hotspotImage = new pimcore.object.tags.hotspotimage({}, this.getDefaultFieldConfig(), this.hotspotConfig);
        hotspotImage.updateContext(this.context);
        var itemCount = this.component.items.length;
        var draggableComponent = this.wrap(hotspotImage);
        this.component.insert(pos + 1, draggableComponent);
    },

    addDataFromSelector: function (item) {

        if (item) {
            this.markDirty();
            var hotspotImage = new pimcore.object.tags.hotspotimage({id: item.id}, this.getDefaultFieldConfig(), this.hotspotConfig);
            hotspotImage.updateContext(this.context);
            var itemCount = this.component.items.length;
            var draggableComponent = this.wrap(hotspotImage);
            this.component.insert(this.component.items.length - 1, draggableComponent);

        }
    },

    delete: function (item) {
        this.markDirty();
        this.component.remove(item);
    },

    notifyDrop: function() {
        this.markDirty();
    },

    markDirty: function() {
        this.dirty = true;
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
    }

});
