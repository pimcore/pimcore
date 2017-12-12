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

    getLayoutEdit: function () {

        var itemWidth = this.fieldConfig.width ? this.fieldConfig.width : 150;
        var itemHeight = this.fieldConfig.height ? this.fieldConfig.height : 150;

        this.items= [];

        for (var i = 0; i < 15; i++) {
            var data = {
                id: 40 + i
            }
            var fieldConfig = {
                width: itemWidth,
                height: itemHeight,
                title: i
            };

            var hotspotImage = new pimcore.object.tags.hotspotimage(data, fieldConfig, {
                condensed: true
            });

            hotspotImageEditPanel = hotspotImage.getLayoutEdit();

            var dragConf = {
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
            this.items.push(dragableComponent);
        }

        var placeholderComponent = this.createPlaceholder(fieldConfig);
        this.items.push(placeholderComponent);

        var conf = {
            border: true,
            layout: {
                type: 'column',
                columns: 1
            },
            title: this.fieldConfig.title,
            items: this.items,
            proxyConfig: {
                width: itemWidth,
                height: itemHeight,
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

        return Ext.create('Ext.panel.Panel', placeholderConf);
    },

    getValue: function () {
        //TODO
        return {};
        // return {id: this.data.id, hotspots: this.hotspots, marker: this.marker, crop: this.crop};
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