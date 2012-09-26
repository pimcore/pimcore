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
    
    initialize: function (data, fieldConfig) {

        this.data = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;

    },


    getLayoutEdit: function () {

        var href = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name
        };

        if (this.data) {
            if (this.data.path) {
                href.value = this.data.path;
            }
        }

        if (this.fieldConfig.width) {
            href.width = this.fieldConfig.width;
        }
        href.enableKeyEvents = true;
        href.cls = "pimcore_droptarget_input";
        this.component = new Ext.form.TextField(href);

        this.component.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.component.getEl();
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
        this.component.on("keyup", function (element, event) {
            element.setValue(this.data.path);
        }.bind(this));

        var items = [this.component, {
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
        }];

        // add upload button when assets are allowed
        if (this.fieldConfig.assetsAllowed) {
            items.push({
                xtype: "button",
                iconCls: "pimcore_icon_upload_single",
                handler: this.uploadDialog.bind(this)
            });
        }


        this.composite = new Ext.form.CompositeField({
            items: items,
            itemCls: "object_field"
        });
        
        return this.composite;
    },


    getLayoutShow: function () {

        var href = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            cls: "object_field"
        };

        if (this.data) {
            if (this.data.path) {
                href.value = this.data.path;
            }
        }

        if (this.fieldConfig.width) {
            href.width = this.fieldConfig.width;
        }
        href.disabled = true;

        this.component = new Ext.form.TextField(href);

        this.composite = new Ext.form.CompositeField({
            items: [this.component, {
                xtype: "button",
                iconCls: "pimcore_icon_edit",
                handler: this.openElement.bind(this)
            }],
            itemCls: "object_field"
        });

        return this.composite;

    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.fieldConfig.assetUploadPath, "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if(data["id"]) {
                    this.data.id = data["id"];
                    this.data.type = "asset";
                    this.data.subtype = data["type"];
                    this.dataChanged = true;

                    this.component.setValue(data["fullpath"]);
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    onNodeDrop: function (target, dd, e, data) {
        if (this.dndAllowed(data)) {
            this.data.id = data.node.attributes.id;
            this.data.type = data.node.attributes.elementType;
            this.data.subtype = data.node.attributes.type;
            this.dataChanged = true;
            this.component.setValue(data.node.attributes.path);

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

        // add upload button when assets are allowed
        if (this.fieldConfig.assetsAllowed) {
            menu.add(new Ext.menu.Item({
                text: t('upload'),
                iconCls: "pimcore_icon_upload_single",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.uploadDialog();
                }.bind(this)
            }));
        }

        menu.showAt(e.getXY());

        e.stopEvent();
    },
    
    openSearchEditor: function () {
        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};
        
        if (this.fieldConfig.objectsAllowed) {
            allowedTypes.push("object");
            if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object = ["object"];
                for (i = 0; i < this.fieldConfig.classes.length; i++) {
                    allowedSpecific.classes.push(this.fieldConfig.classes[i].classes);
                }
            } else {
                allowedSubtypes.object = ["object","folder","variant"];
            }
        }
        if (this.fieldConfig.assetsAllowed) {
            allowedTypes.push("asset");
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(this.fieldConfig.assetTypes[i].assetTypes);
                }
            }            
        } 
        if (this.fieldConfig.documentsAllowed) {
            allowedTypes.push("document");
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    allowedSubtypes.document.push(this.fieldConfig.documentTypes[i].documentTypes);
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

        this.component.setValue(data.fullpath);
    },
    
    openElement: function () {
        if(this.data.id && this.data.type && this.data.subtype) {
            pimcore.helpers.openElement(this.data.id, this.data.type, this.data.subtype);
        }
    },
    
    empty: function () {
        this.data = {};
        this.dataChanged=true;
        this.component.setValue("");
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    dndAllowed: function(data) {
        var type = data.node.attributes.elementType;
        var isAllowed = false;
        if (type == "object" && this.fieldConfig.objectsAllowed) {

            var classname = data.node.attributes.className;
            var isAllowed = false;
            if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                for (i = 0; i < this.fieldConfig.classes.length; i++) {
                    if (this.fieldConfig.classes[i].classes == classname) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no classes configured - allow all
                isAllowed = true;
            }


        } else if (type == "asset" && this.fieldConfig.assetsAllowed) {
            var subType = data.node.attributes.type;
            var isAllowed = false;
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    if (this.fieldConfig.assetTypes[i].assetTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no asset types configured - allow all
                isAllowed = true;
            }

        } else if (type == "document" && this.fieldConfig.documentsAllowed) {
            var subType = data.node.attributes.type;
            var isAllowed = false;
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    if (this.fieldConfig.documentTypes[i].documentTypes == subType) {
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