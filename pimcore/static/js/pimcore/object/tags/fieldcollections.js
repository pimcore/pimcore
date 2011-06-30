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

pimcore.registerNS("pimcore.object.tags.fieldcollections");
pimcore.object.tags.fieldcollections = Class.create(pimcore.object.tags.abstract, {

    type: "fieldcollections",
    dirty: false,

    initialize: function (data, layoutConf) {

        this.data = [];
        this.currentElements = [];
        this.layoutDefinitions = {};
        this.dataFields = [];
        this.layoutIds = [];
        
        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;
    },

    loadFieldDefinitions: function () {
        this.fieldstore = new Ext.data.JsonStore({
            autoDestroy: false,
            url: "/admin/class/fieldcollection-list",
            root: 'fieldcollections',
            idProperty: 'key',
            fields: ['key', {name: "layoutConfigurations", convert: function (v, rec) {
                this.layoutDefinitions[rec.key] = rec.layoutDefinitions;
            }.bind(this)}],
            listeners: {
                load: this.initData.bind(this)
            },
            baseParams: {
                allowedTypes: this.layoutConf.allowedTypes.join(",")
            }
        });
        
        this.fieldstore.load();

    },

    getLayoutEdit: function () {
        
        this.loadFieldDefinitions();
        
        var panelConf = {
            autoHeight: true,
            cls: "object_field"
        };
        if(this.layoutConf.title) {
            panelConf.title = this.layoutConf.title;
        }
        
        this.layout = new Ext.Panel(panelConf);
        return this.layout;
    },
    
    initData: function () {
        
        if(this.data.length < 1) {
            this.layout.add(this.getControls());
        } else {
            for (var i=0; i<this.data.length; i++) {
                this.addBlockElement(i,this.data[i].type, this.data[i].data, true);
            }
        }
        
        this.layout.doLayout();
    },
    
    getControls: function (blockElement) {
        
        var collectionMenu = [];
        
        this.fieldstore.each(function (blockElement, rec) {
            collectionMenu.push({
                text: ts(rec.data.key),
                handler: this.addBlock.bind(this,blockElement, rec.data.key),
                iconCls: "pimcore_icon_fieldcollections"
            });
        }.bind(this, blockElement));
        
        var items = [];
        
        if(collectionMenu.length == 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                handler: collectionMenu[0].handler
            });
        } else if (collectionMenu.length > 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                menu: collectionMenu
            });
        } else {
            items.push({
                xtype: "tbtext",
                text: t("no_collections_allowed")
            });
        }
        
        
        
        if(blockElement) {
            items.push({
                cls: "pimcore_block_button_minus",
                iconCls: "pimcore_icon_minus",
                listeners: {
                    "click": this.removeBlock.bind(this, blockElement)
                }
            });
            
            items.push({
                cls: "pimcore_block_button_up",
                iconCls: "pimcore_icon_up",
                listeners: {
                    "click": this.moveBlockUp.bind(this, blockElement)
                }
            });
            
            items.push({
                cls: "pimcore_block_button_down",
                iconCls: "pimcore_icon_down",
                listeners: {
                    "click": this.moveBlockDown.bind(this, blockElement)
                }
            });
        }
        
        var toolbar = new Ext.Toolbar({
            items: items
        });
        
        return toolbar;
    },
    
    detectBlockIndex: function (blockElement) {
        // detect index
        var index;
        
        for(var s=0; s<this.layout.items.items.length; s++) {
            if(this.layout.items.items[s].key == blockElement.key) {
                index = s;
                break;
            }
        }
        return index;
    },
    
    addBlock: function (blockElement, type) {
        
        var index = 0;
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        this.addBlockElement(index, type)
    },
    
    removeBlock: function (blockElement) {
        
        var key = blockElement.key;
        this.currentElements[key] = "deleted";
        
        this.layout.remove(blockElement);
        this.dirty = true;
        
        // check for remaining elements
        if(this.layout.items.items.length < 1) {
            this.layout.removeAll();
            this.layout.add(this.getControls());
            this.layout.doLayout();
            this.currentElements = [];
        }
    },
    
    moveBlockUp: function (blockElement) {
        
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        var newIndex = index-1;
        if(newIndex < 0) {
            newIndex = 0;
        }
        
        // move this node temorary to an other so ext recognizes a change
        this.layout.remove(blockElement, false);
        this.object.edit.layout.add(blockElement);
        this.object.edit.layout.doLayout();
        this.layout.doLayout();
        
        // move the element to the right position
        this.object.edit.layout.remove(blockElement,false);
        this.layout.insert(newIndex, blockElement);
        this.layout.doLayout();
        this.dirty = true;
    },
    
    moveBlockDown: function (blockElement) {
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        // move this node temorary to an other so ext recognizes a change
        this.layout.remove(blockElement, false);
        this.object.edit.layout.add(blockElement);
        this.object.edit.layout.doLayout();
        this.layout.doLayout();
        
        // move the element to the right position
        this.object.edit.layout.remove(blockElement,false);
        this.layout.insert(index+1, blockElement);
        this.layout.doLayout();
        this.dirty = true;
    },
    
    addBlockElement: function (index, type, blockData, ignoreChange) {
        
        if(!type){
            return;
        }
        if(!this.layoutDefinitions[type]) {
            return;
        }
        
        // remove the initial toolbar if there is no element
        if(this.currentElements.length < 1) {
            this.layout.removeAll();
        }
        
        this.dataFields = [];
        this.currentData = {};
        
        if(blockData) {
            this.currentData = blockData;
        }

        var blockElement = new Ext.Panel({
            bodyStyle: "padding:10px;",
            style: "margin: 0 0 10px 0;",
            layout: "pimcoreform",
            autoHeight: true,
            border: false,
            items: this.getRecursiveLayout(this.layoutDefinitions[type]).items
        });
        
        blockElement.insert(0, this.getControls(blockElement));
        
        blockElement.key = this.currentElements.length;
        blockElement.fieldtype = type;
        this.layout.insert(index, blockElement);
        this.layout.doLayout();
        
        
        this.currentElements.push({
            container: blockElement,
            fields: this.dataFields,
            type: type
        });

        if(!ignoreChange) {
            this.dirty = true;
        }

        this.dataFields = [];
        this.currentData = {};
    },

    getDataForField: function (name) {
        return this.currentData[name];
    },

    getMetaDataForField: function(name) {
        return null;
    },

    addToDataFields: function (field, name) {
        this.dataFields.push(field);
    },

    addFieldsToMask: function (field) {
        this.object.edit.fieldsToMask.push(field);
    },
    
    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {
        
        var data = [];
        var element;
        var elementData = {};
        
        for(var s=0; s<this.layout.items.items.length; s++) {
            elementData = {};
            if(this.currentElements[this.layout.items.items[s].key]) {
                element = this.currentElements[this.layout.items.items[s].key];
                
                for (var u=0; u<element.fields.length; u++) {
                    elementData[element.fields[u].getName()] = element.fields[u].getValue();
                }
                
                data.push({
                    type: element.type,
                    data: elementData
                });
            }
        }
        
        return data;
    },

    getName: function () {
        return this.layoutConf.name;
    },

    isDirty: function() {
        if(!this.layout.rendered) {
            return false;
        }
        
        // HACK: always true - always transfer the values of the fieldcollection to the server
        return true;
    },

    isMandatory: function () {
        var element;

        for(var s=0; s<this.layout.items.items.length; s++) {
            if(this.currentElements[this.layout.items.items[s].key]) {
                element = this.currentElements[this.layout.items.items[s].key];

                for (var u=0; u<element.fields.length; u++) {
                    if(element.fields[u].isMandatory()) {
                        return true;
                    }
                }
            }
        }

        return false;
    },

    isInvalidMandatory: function () {
        var element;
        var isInvalid = false;

        for(var s=0; s<this.layout.items.items.length; s++) {
            if(this.currentElements[this.layout.items.items[s].key]) {
                element = this.currentElements[this.layout.items.items[s].key];

                for (var u=0; u<element.fields.length; u++) {
                    if(element.fields[u].isMandatory()) {
                        if(element.fields[u].isInvalidMandatory()) {
                            isInvalid = true;
                            element.fields[u].markMandatory();
                        } else {
                            element.fields[u].unmarkMandatory();
                        }
                    }
                }
            }
        }

        return isInvalid;
    }
});

pimcore.object.tags.fieldcollections.addMethods(pimcore.object.helpers.edit);