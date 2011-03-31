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

pimcore.registerNS("pimcore.object.tags.href");
pimcore.object.tags.href = Class.create(pimcore.object.tags.abstract, {

    type: "href",
    dataChanged:false,
    
    initialize: function (data, layoutConf) {

        this.data = {};

        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;

    },


    getLayoutEdit: function () {

        var href = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name
        };

        if (this.data) {
            if (this.data.path) {
                href.value = this.data.path;
            }
        }

        if (this.layoutConf.width) {
            href.width = this.layoutConf.width;
        }
        href.enableKeyEvents = true;
        href.cls = "pimcore_droptarget_input";
        this.layout = new Ext.form.TextField(href);

        this.layout.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.layout.getEl();
                },

                onNodeOver : function(target, dd, e, data) {

                    if (this.dndAllowed(data)) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                    else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }

                }.bind(this),

                onNodeDrop : this.onNodeDrop.bind(this)
            });


            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

        }.bind(this));

        // disable typing into the textfield
        this.layout.on("keyup", function (element, event) {
            element.setValue(this.data.path);
        }.bind(this));
        
        this.composite = new Ext.form.CompositeField({
            items: [this.layout, {
                xtype: "button",
                iconCls: "pimcore_icon_edit",
                handler: this.openElement.bind(this)
            },{
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                handler: this.empty.bind(this)
            },{
                xtype: "button",
                iconCls: "pimcore_icon_search",
                handler: this.openSearchEditor.bind(this)
            }],
            itemCls: "object_field"
        });
        
        return this.composite;
    },


    getLayoutShow: function () {

        var href = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
            cls: "object_field"
        };

        if (this.data) {
            if (this.data.path) {
                href.value = this.data.path;
            }
        }

        if (this.layoutConf.width) {
            href.width = this.layoutConf.width;
        }
        href.disabled = true;

        this.layout = new Ext.form.TextField(href);

        this.composite = new Ext.form.CompositeField({
            items: [this.layout, {
                xtype: "button",
                iconCls: "pimcore_icon_edit",
                handler: this.openElement.bind(this)
            }],
            itemCls: "object_field"
        });

        return this.composite;

    },

    onNodeDrop: function (target, dd, e, data) {
        if (this.dndAllowed(data)) {
            this.data.id = data.node.attributes.id;
            this.data.type = data.node.attributes.elementType;
            this.data.subtype = data.node.attributes.type;
            this.dataChanged = true;
            this.layout.setValue(data.node.attributes.path);

            return true;
        } else {
            return false;
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
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
                this.openElement();
            }.bind(this)
        }));
        
        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this)
        }));

        menu.showAt(e.getXY());

        e.stopEvent();
    },
    
    openSearchEditor: function () {
        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};
        
        if (this.layoutConf.objectsAllowed) {
            allowedTypes.push("object");
            if (this.layoutConf.classes != null && this.layoutConf.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object = ["object"];
                for (i = 0; i < this.layoutConf.classes.length; i++) {
                    allowedSpecific.classes.push(this.layoutConf.classes[i].classes);
                }
            } else {
                allowedSubtypes.object = ["object","folder","variant"];
            }
        }
        if (this.layoutConf.assetsAllowed) {
            allowedTypes.push("asset");
            if (this.layoutConf.assetTypes != null && this.layoutConf.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < this.layoutConf.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(this.layoutConf.assetTypes[i].assetTypes);
                }
            }            
        } 
        if (this.layoutConf.documentsAllowed) {
            allowedTypes.push("document");
            if (this.layoutConf.documentTypes != null && this.layoutConf.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < this.layoutConf.documentTypes.length; i++) {
                    allowedSubtypes.document.push(this.layoutConf.documentTypes[i].documentTypes);
                }
            }
        }
        
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: allowedTypes,
            subtype: allowedSubtypes,
            specific: allowedSpecific
        });
    },
    
    addDataFromSelector: function (data) {
        this.data.id = data.id;
        this.data.type = data.type;
        this.data.subtype = data.subtype;
        this.dataChanged = true;

        this.layout.setValue(data.fullpath);
    },
    
    openElement: function () {
        if(this.data.id && this.data.type && this.data.subtype) {
            pimcore.helpers.openElement(this.data.id, this.data.type, this.data.subtype);
        }
    },
    
    empty: function () {
        this.data = {};
        this.dataChanged=true;
        this.layout.setValue("");
    },

    getValue: function () {

        if(this.layoutConf.lazyLoading && !this.dataChanged){
            return false;
        } else {
            return this.data;
        }
    },

    getName: function () {
        return this.layoutConf.name;
    },

    dndAllowed: function(data) {
        var type = data.node.attributes.elementType;
        var isAllowed = false;
        if (type == "object" && this.layoutConf.objectsAllowed) {

            var classname = data.node.attributes.className;
            var isAllowed = false;
            if (this.layoutConf.classes != null && this.layoutConf.classes.length > 0) {
                for (i = 0; i < this.layoutConf.classes.length; i++) {
                    if (this.layoutConf.classes[i].classes == classname) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no classes configured - allow all
                isAllowed = true;
            }


        } else if (type == "asset" && this.layoutConf.assetsAllowed) {
            var subType = data.node.attributes.type;
            var isAllowed = false;
            if (this.layoutConf.assetTypes != null && this.layoutConf.assetTypes.length > 0) {
                for (i = 0; i < this.layoutConf.assetTypes.length; i++) {
                    if (this.layoutConf.assetTypes[i].assetTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no asset types configured - allow all
                isAllowed = true;
            }

        } else if (type == "document" && this.layoutConf.documentsAllowed) {
            var subType = data.node.attributes.type;
            var isAllowed = false;
            if (this.layoutConf.documentTypes != null && this.layoutConf.documentTypes.length > 0) {
                for (i = 0; i < this.layoutConf.documentTypes.length; i++) {
                    if (this.layoutConf.documentTypes[i].documentTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no document types configured - allow all
                isAllowed = true;
            }
        }
        return isAllowed;
    },

    isInvalidMandatory: function () {
        if (this.data.id) {
            return false;
        }
        return true;
    }
});