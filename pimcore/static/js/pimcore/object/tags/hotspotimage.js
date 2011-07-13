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
    selectorCount: 0,
    type: "hotspotimage",
//    dirty: false,
//
//    initialize: function (data, layoutConf) {
//        if (data) {
//            this.data = data;
//        }
//        this.layoutConf = layoutConf;
//
//    },
//
//    getGridColumnConfig: function(field) {
//
//        return {header: ts(field.label), width: 100, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
//            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
//                metaData.css += " grid_value_inherited";
//            }
//
//            if (value && value.id) {
//                return '<img src="/admin/asset/get-image-thumbnail/id/' + value.id + '/width/88/aspectratio/true" />';
//            }
//        }.bind(this, field.key)};
//    },
//
    getLayoutEdit: function () {

        if (intval(this.layoutConf.width) < 1) {
            this.layoutConf.width = 100;
        }
        if (intval(this.layoutConf.height) < 1) {
            this.layoutConf.height = 100;
        }

        this.panel = new Ext.Panel({
            width: this.layoutConf.width,
            height: this.layoutConf.height-27,
            bodyCssClass: "pimcore_droptarget_image"}
        );
        
        var conf = {
            width: this.layoutConf.width,
            height: this.layoutConf.height,
            tbar: [{
                xtype: "tbspacer",
                width: 20,
                height: 16,
                cls: "pimcore_icon_droptarget"
            },
            {
                xtype: "tbtext",
                text: "<b>" + this.layoutConf.title + "</b>"
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
            }],
            items: [this.panel]
//            cls: "object_field"
//            bodyCssClass: "pimcore_droptarget_image"
        };

        this.layout = new Ext.Panel(conf);


        this.panel.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.layout.getEl();
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
                this.updateImage();
            }

        }.bind(this));


        return this.layout;
    },
//
//
//    getLayoutShow: function () {
//
//        if (intval(this.layoutConf.width) < 1) {
//            this.layoutConf.width = 100;
//        }
//        if (intval(this.layoutConf.height) < 1) {
//            this.layoutConf.height = 100;
//        }
//
//        var conf = {
//            width: this.layoutConf.width,
//            height: this.layoutConf.height,
//            title: this.layoutConf.title,
//            cls: "object_field"
//        };
//
//        this.layout = new Ext.Panel(conf);
//
//        this.layout.on("render", function (el) {
//            if (this.data) {
//                this.updateImage();
//            }
//        }.bind(this));
//
//        return this.layout;
//    },
//
//    onNodeDrop: function (target, dd, e, data) {
//        if (data.node.attributes.type == "image") {
//            if(this.data != data.node.attributes.id) {
//                this.dirty = true;
//            }
//            this.data = data.node.attributes.id;
//
//            this.updateImage();
//            return true;
//        }
//    },
//
//    openSearchEditor: function () {
//        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
//            type: ["asset"],
//            subtype: {
//                asset: ["image"]
//            }
//        });
//    },
//
//    addDataFromSelector: function (item) {
//        if (item) {
//            if(this.data != item.id) {
//                this.dirty = true;
//            }
//
//            this.data = item.id;
//
//            this.updateImage();
//            return true;
//        }
//    },
//
//    openImage: function () {
//        if(this.data) {
//            pimcore.helpers.openAsset(this.data, "image");
//        }
//    },
//
    updateImage: function () {
        var path = "/admin/asset/get-image-thumbnail/id/" + this.data + "/width/" + (this.layoutConf.width - 20) + "/aspectratio/true";
        this.panel.getEl().update('<img id="selectorImage" style="padding: 10px 0;padding-left:8px" class="pimcore_droptarget_image" src="' + path + '" />');

//        this.getBody().setStyle({
//            backgroundImage: "url(" + path + ")"
//        });
//        this.panel.getBody().repaint();
    },

    addSelector: function() {
        this.selectorCount++;
        console.log(this.panel.getEl());
//        this.panel.getEl().appendChild('<div id="selector" style="cursor:move; position: absolute; top: 10px; left: 10px;z-index:9000;"></div>');

        this.panel.getEl().createChild({
            tag: 'div',
            id: 'selector' + this.selectorCount,
            style: 'cursor:move; position: absolute; top: 10px; left: 10px;z-index:9000;'
        });

        this.resizer = new Ext.Resizable('selector' + this.selectorCount, {
            pinned:true,
            minWidth:50,
            minHeight: 50,
            preserveRatio: false,
            dynamic:true,
            handles: 'all',
            draggable:true,
            width: 100,
            height: 100
        });

        this.resizer.addListener("resize", function(item, width, height, e) {
            var dimensions = Ext.get("selector1").getStyles("top","left","width","height");
            console.log(dimensions);

            console.log(item);
            console.log(width);
            console.log(height);
            console.log(e);
        });

//        Ext.get('selector' + this.selectorCount).on('click', function(){
//            console.log("meins");
//        });
//
//        Ext.get('selector' + this.selectorCount).on('dblclick', function(){
//            console.log("meins22");
//        });

        Ext.get('selector' + this.selectorCount).on("contextmenu", this.onContextMenu2.bind(this, this.selectorCount));
    },

//
//    getBody: function () {
//        // get the id from the body element of the panel because there is no method to set body's html (only in configure)
//        var bodyId = Ext.get(this.layout.getEl().dom).query(".x-panel-body")[0].getAttribute("id");
//        return Ext.get(bodyId);
//    },
//
    onContextMenu2: function (id, e) {
        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                console.log(id);
                console.log(item);
                Ext.get('selector' + id).hide();
                Ext.get('selector' + id).remove();
            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('edit'),
            iconCls: "pimcore_icon_open",
            handler: function (item) {
                item.parentMenu.destroy();

                this.openImage();
            }.bind(this)
        }));

        menu.showAt(e.getXY());

        e.stopEvent();
    },
//
//    empty: function () {
//        this.data = null;
//        this.getBody().setStyle({
//            backgroundImage: "url(/pimcore/static/img/icon/drop-40.png)"
//        });
//        this.dirty = true;
//        this.getBody().repaint();
//    },
//
//    getValue: function () {
//        return this.data;
//    },
//
//    getName: function () {
//        return this.layoutConf.name;
//    },
//
//    isInvalidMandatory: function () {
//        if (this.getValue()) {
//            return false;
//        }
//        return true;
//    },
//
//    isDirty: function() {
//        if(!this.layout.rendered) {
//            return false;
//        }
//
//        return this.dirty;
//    }
});