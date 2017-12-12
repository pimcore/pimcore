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
            this.data = {};
        } else {
            this.data = {};
        }
        this.fieldConfig = fieldConfig;
    },


    getGridColumnConfig: function(field) {

        return {header: ts(field.label), width: 100, sortable: false, dataIndex: field.key,
            getEditor:this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }

                //TODO

                // if (value && value.id) {
                //     return '<img src="/admin/asset/get-image-thumbnail?id=' + value.id
                //         + '&width=88&height=88&frame=true" />';
                // }
            }.bind(this, field.key)};
    },

    wrap: function(hotspotImageTag) {

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

        var dragableComponent = Ext.create('Ext.panel.Panel', dragConf);
        return dragableComponent;

    },

    getDefaultFieldConfig: function() {
        var itemWidth = this.fieldConfig.width ? this.fieldConfig.width : 150;
        var itemHeight = this.fieldConfig.height ? this.fieldConfig.height : 150;

        var fieldConfig = {
            width: itemWidth,
            height: itemHeight,
        };

        return fieldConfig;
    },

    getLayoutEdit: function () {

        this.items= [];

        for (var i = 0; i < 15; i++) {
            var data = {
                id: 40 + i
            }
            var fieldConfig = this.getDefaultFieldConfig();
            fieldConfig.title = i;

            var hotspotImage = new pimcore.object.tags.hotspotimage(data, fieldConfig, {
                condensed: true
            });

            var dragableComponent = this.wrap(hotspotImage);
            this.items.push(dragableComponent);
        }

        var placeholderComponent = this.createPlaceholder(fieldConfig);
        this.items.push(placeholderComponent);

        var defaultFieldConfig = this.getDefaultFieldConfig();

        var conf = {
            border: true,
            layout: {
                type: 'column',
                columns: 1
            },
            title: this.fieldConfig.title,
            items: this.items,
            proxyConfig: {
                width: defaultFieldConfig.width,
                height: defaultFieldConfig.height,
                respectPlaceholder: true
            }
        };

        this.component = new pimcore.object.helpers.ImageGalleryPanel(conf);

        // setTimeout(function() {
        //     // var item = this.items1[5];
        //     // this.component.remove(item);
        //     // this.component.updateLayout();
        //
        // }.bind(this), 5000);

        return this.component;
    },

    createPlaceholder: function(fieldConfig) {
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
            layout : {
                type : 'table',
                columns : 1,
                tableAttrs : {
                    style : {
                        width : '100%',
                        height : '100%'
                    }
                },
                tdAttrs : {
                    align : 'center',
                    valign : 'middle',
                },
            },
            items : [{
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

                    var record = data.records[0];

                    if (record.data.type == "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }
                },

                onNodeDrop: function (target, dd, e, data) {

                    var record = data.records[0];
                    var data = {
                        id: record.data.id
                    }

                    var fieldConfig = this.getDefaultFieldConfig();
                    fieldConfig.title = record.data.path;

                    var hotspotImage = new pimcore.object.tags.hotspotimage(data, fieldConfig, {
                        condensed: true
                    });
                    var itemCount = this.component.items.length;

                    var dragableComponent = this.wrap(hotspotImage);
                    this.items.push(dragableComponent);
                    this.component.insert(itemCount - 1 , dragableComponent);
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

        return value
    },

    getCellEditValue: function () {
        //TODO
        return this.getValue();
    },

    isDirty: function() {
        //TODO
        return true;
    }
});