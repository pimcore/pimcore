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

pimcore.registerNS("pimcore.object.tags.hotspotimage");
pimcore.object.tags.hotspotimage = Class.create(pimcore.object.tags.image, {
    
    hotspotCount: 0,
    type: "hotspotimage",

    marginTop: 10,
    marginLeft: 8,

    data: null,
    hotspots: {},


    initialize: function (data, fieldConfig) {
        this.hotspots = {};
        this.data = null;
        if (data) {
            this.data = data.image;
            if(data.hotspots && data.hotspots != "null") {
                this.loadedHotspots = data.hotspots;
            }
        }
        this.fieldConfig = fieldConfig;
        this.uniqeFieldId = uniqid();
    },


    getGridColumnConfig: function(field) {

        return {header: ts(field.label), width: 100, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }

            if (value && value.id) {
                return '<img src="/admin/asset/get-image-thumbnail/id/' + value.id + '/width/88/height/88/frame/true" />';
            }
        }.bind(this, field.key)};
    },

    getLayoutEdit: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 100;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 100;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: [{
                xtype: "tbspacer",
                width: 20,
                height: 16,
                cls: "pimcore_icon_droptarget"
            },
            {
                xtype: "tbtext",
                text: "<b>" + this.fieldConfig.title + "</b>"
            },"->",{
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: this.addSelector.bind(this)
            },{
                xtype: "button",
                iconCls: "pimcore_icon_edit",
                handler: this.openImage.bind(this)
            }, {
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                handler: this.empty.bind(this)
            },{
                xtype: "button",
                iconCls: "pimcore_icon_search",
                handler: this.openSearchEditor.bind(this)
            },{
                xtype: "button",
                iconCls: "pimcore_icon_upload_single",
                handler: this.uploadDialog.bind(this)
            }]
        };

        this.component = new Ext.Panel(conf);
        this.createImagePanel();

        return this.component;
    },

    createImagePanel: function() {
        this.panel = new Ext.Panel({
            width: this.fieldConfig.width,
            height: this.fieldConfig.height-27,
            bodyCssClass: "pimcore_droptarget_image"}
        );
        this.component.add(this.panel);


        this.panel.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.component.getEl();
                },

                onNodeOver : function(target, dd, e, data) {

                    if (data.node.attributes.type == "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }

                },

                onNodeDrop : this.onNodeDrop.bind(this)
            });


            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            if (this.data) {
                this.updateImage(true);
            }

        }.bind(this));

        this.component.doLayout();

    },

    updateImage: function (initialLoad) {
        var path = "/admin/asset/get-image-thumbnail/id/" + this.data + "/width/" + (this.fieldConfig.width - 20) + "/height/" + (this.fieldConfig.height - 40) + "/aspectratio/true";
        var name = this.getName();
        this.panel.getEl().update(
            '<img id="' + name + this.uniqeFieldId + '_selectorImage" style="margin: ' + this.marginTop + 'px 0;margin-left:' + this.marginLeft + 'px" class="pimcore_droptarget_image" src="' + path + '" />',
            false,
            this.loadHotspots.bind(this, initialLoad)
        );
    },

    loadHotspots: function(initialLoad) {
        this.hotspots = {};
        var box = Ext.get(this.getName() + this.uniqeFieldId + '_selectorImage');
        if(box && (box.getHeight() < 1 || box.getWidth() < 1)) {
            setTimeout(this.loadHotspots.bind(this, initialLoad), 1000);
        } else {
            if(this.loadedHotspots && this.loadedHotspots.length > 0) {
                for(var i = 0; i < this.loadedHotspots.length; i++) {
                    this.addHotspot(this.loadedHotspots[i]);
                }
            }
        }

        if(initialLoad) {
            this.dirty = false;
        }
    },

    addSelector: function() {
        if(this.data) {
            Ext.MessageBox.prompt(t('hotspotimage_add_selector'), t('hotspotimage_enter_name_of_new_hotspot'), this.completeAddSelector.bind(this), null, null, "");
        } else {
            Ext.MessageBox.alert(t("hotspotimage_no_image"));
        }
    },

    completeAddSelector: function(button, value, object) {
        if(button == "ok") {
            var hotspot = {
                name: value,
                top: 0,
                left: 0,
                width: 20,
                height: 20
            };
            this.addHotspot(hotspot);
        }
    },

    addHotspot: function(hotspot) {
        this.hotspotCount++;
        var number = this.hotspotCount;

        if(!Ext.get(this.getName() + this.uniqeFieldId + '_selectorImage')) {
            return;
        }

        var box = Ext.get(this.getName() + this.uniqeFieldId + '_selectorImage').getBox();

        var height = this.convertToAbsolute(hotspot.height, box.height);
        var width = this.convertToAbsolute(hotspot.width, box.width);
        var left = this.convertToAbsolute(hotspot.left, box.width) + this.marginLeft;
        var top = this.convertToAbsolute(hotspot.top, box.height) + this.marginTop;

        if(intval(height) < 1 || intval(width) < 1) {
            return;
        }

        this.panel.getEl().createChild({
            tag: 'div',
            id: 'selector' + number + this.uniqeFieldId,
            style: 'cursor:move; position: absolute; top: ' + top + 'px; left: ' + left + 'px;z-index:9000;',
            html: this.getSelectorHtml(number, hotspot.name)
        });

        var resizer = new Ext.Resizable('selector' + number + this.uniqeFieldId, {
            pinned:true,
            minWidth:50,
            minHeight: 50,
            preserveRatio: false,
            resizeChild: true,
            dynamic:true,
            handles: 'all',
            draggable:true,
            width: width,
            height: height
        });

        this.hotspots[number] = hotspot;
        this.dirty = true;

        resizer.addListener("resize", function(number, item, width, height, e) {
            this.handleSelectorChanged(number);
            this.updateSelectorBody(number);
        }.bind(this, number));

        Ext.get('selector' + number + this.uniqeFieldId).on('mouseup', function(number){
            this.handleSelectorChanged(number);
        }.bind(this, number));

        Ext.get('selector' + number + this.uniqeFieldId).on("contextmenu", this.onSelectorContextMenu.bind(this, number));

        this.updateSelectorBody(number);
    },

    getSelectorHtml: function(number, text) {
        return '<p id="selectorbody' + number + this.uniqeFieldId + '" class="pimcore_hotspot_body">' + text + '</p>';
    },

    handleSelectorChanged: function(selectorNumber) {
        var dimensions = Ext.get("selector" + selectorNumber + this.uniqeFieldId).getStyles("top","left","width","height");
        var box = Ext.get(this.getName() + this.uniqeFieldId + '_selectorImage').getBox();
        this.hotspots[selectorNumber].top = this.convertToRelative(intval(dimensions.top) - this.marginTop, box.height);
        this.hotspots[selectorNumber].left = this.convertToRelative(intval(dimensions.left) - this.marginLeft, box.width);
        this.hotspots[selectorNumber].width = this.convertToRelative(intval(dimensions.width), box.width);
        this.hotspots[selectorNumber].height = this.convertToRelative(intval(dimensions.height), box.height);

        this.dirty = true;
    },

    convertToRelative: function(value, totalValue) {
        return value * 100 / totalValue;
    },

    convertToAbsolute: function(value, totalValue) {
        return value / 100 * totalValue;
    },

    updateSelectorBody: function (selectorNumber) {
        // update selector body dimensions
        var dimensions = Ext.get("selector" + selectorNumber + this.uniqeFieldId).getStyles("top","left","width","height");
        Ext.get('selectorbody' + selectorNumber + this.uniqeFieldId).applyStyles({
            width: dimensions.width,
            height: (intval(dimensions.height)-5) + "px"
        });
    },

    onSelectorContextMenu: function (id, e) {
        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                Ext.get('selector' + id + this.uniqeFieldId).hide();
                Ext.get('selector' + id + this.uniqeFieldId).remove();
                delete this.hotspots[id];
            }.bind(this)
        }));

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    empty: function () {
        this.data = null;

        this.hotspots = {};
        this.loadedHotspots = null;
        this.dirty = true;
        this.component.removeAll();
        this.createImagePanel();
    },

    getValue: function () {
        return {image: this.data, hotspots: this.hotspots};
    }
});