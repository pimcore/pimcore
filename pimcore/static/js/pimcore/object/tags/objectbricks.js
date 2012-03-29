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

pimcore.registerNS("pimcore.object.tags.objectbricks");
pimcore.object.tags.objectbricks = Class.create(pimcore.object.tags.abstract, {

    type: "objectbricks",
    dirty: false,
    addedTypes: {},
    preventDelete: {},

    initialize: function (data, fieldConfig) {

        this.addedTypes = {};
        this.preventDelete = {};

        this.data = [];
        this.currentElements = {};
        this.layoutDefinitions = {};
        this.dataFields = [];
        this.layoutIds = [];
        
        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    loadFieldDefinitions: function () {
        this.fieldstore = new Ext.data.JsonStore({
            autoDestroy: false,
            url: "/admin/class/objectbrick-list",
            root: 'objectbricks',
            idProperty: 'key',
            fields: ['key', {name: "fieldConfigigurations", convert: function (v, rec) {
                this.layoutDefinitions[rec.key] = rec.layoutDefinitions;
            }.bind(this)}],
            listeners: {
                load: this.initData.bind(this)
            },
            baseParams: {
                class_id: this.object.data.general.o_classId,
                field_name: this.getName()
            }
        });
        
        this.fieldstore.load();

    },

    getLayoutEdit: function () {
        
        this.loadFieldDefinitions();
        
        var panelConf = {
            autoHeight: true,
            border: false,
            activeTab: 0
        };
        this.tabpanel = new Ext.TabPanel(panelConf);

        var panelConf = {
            autoHeight: true,
            cls: "object_field",
            items: [this.tabpanel]
        };

        if(this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }
        this.component = new Ext.Panel(panelConf);

        return this.component;
    },
    
    initData: function () {
        
        this.component.insert(0, this.getControls());
        if(this.data.length > 0) {
            for (var i=0; i<this.data.length; i++) {
                if(this.data[i] != null) {
                    this.preventDelete[this.data[i].type] = this.data[i].inherited;
                    this.addBlockElement(i,this.data[i].type, this.data[i], true);
                }
            }
        }
        
        pimcore.layout.refresh();
    },
    
    getControls: function (blockElement) {
        
        var collectionMenu = [];

        this.fieldstore.each(function (blockElement, rec) {

            if(!this.addedTypes[rec.data.key]) {
                collectionMenu.push({
                    text: ts(rec.data.key),
                    handler: this.addBlock.bind(this,blockElement, rec.data.key),
                    iconCls: "pimcore_icon_objectbricks"
                });
            }

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
                text: t("no_further_objectbricks_allowed")
            });
        }
        
        var toolbar = new Ext.Toolbar({
            items: items
        });
        
        return toolbar;
    },

    getDeleteControl: function(type, blockElement) {
        var items = [];
        if(!this.preventDelete[type]) {
            items.push({
                cls: "pimcore_block_button_minus",
                iconCls: "pimcore_icon_minus",
                listeners: {
                    "click": this.removeBlock.bind(this, blockElement)
                }
            });
        }
        items.push({
            xtype: "tbtext",
            text: ts(type)
        });

        var toolbar = new Ext.Toolbar({
            items: items
        });

        return toolbar; 
    },
    
    addBlock: function (blockElement, type) {
        
        var index = 0;

        this.addBlockElement(index, type)
    },
    
    removeBlock: function (blockElement) {

        Ext.MessageBox.confirm(t('delete_objectbrick'), t('delete_objectbrick_text'), function(blockElement, answer) {
            if(answer == "yes") {

                var key = blockElement.key;
                this.currentElements[key].action = "deleted";

                this.tabpanel.remove(blockElement);
                this.addedTypes[blockElement.fieldtype] = false;
                this.component.remove(this.component.get(0));
                this.component.insert(0, this.getControls());
                this.component.doLayout();

                this.dirty = true;
            }
        }.bind(this, blockElement), this);
    },
    

    addBlockElement: function (index, type, blockData, ignoreChange) {
        if(!type){
            return;
        }
        if(!this.layoutDefinitions[type]) {
            return;
        }

        this.dataFields = [];
        this.currentData = {};
        this.currentMetaData = {};
        
        if(blockData) {
            this.currentData = blockData.data;
            this.currentMetaData = blockData.metaData;
        }

        var blockElement = new Ext.Panel({
            //bodyStyle: "padding:10px;",
            style: "margin: 0 0 10px 0;",
            layout: "pimcoreform",
            autoHeight: true,
            border: false,
            title: type,
            items: this.getRecursiveLayout(this.layoutDefinitions[type]).items
        });


        this.component.remove(this.component.get(0));

        this.addedTypes[type] = true;

        var control = this.getDeleteControl(type, blockElement);
        if(control) {
            blockElement.insert(0, control);
        }
        
        blockElement.key = type; 
        blockElement.fieldtype = type;
        this.tabpanel.add(blockElement);
//        console.log(this.getControls());
        this.component.insert(0, this.getControls());


        this.tabpanel.doLayout();
        this.component.doLayout();

        this.currentElements[type] = null;
        this.currentElements[type] = {
                            container: blockElement,
                            fields: this.dataFields,
                            type: type
                        };

        if(!ignoreChange) {
            this.dirty = true;
            this.tabpanel.activate(blockElement);
        }

        this.dataFields = [];
        this.currentData = {};
        this.currentMetaData = {};
    },

    getDataForField: function (name) {
        return this.currentData[name];
    },

    getMetaDataForField: function(name) {
        return this.currentMetaData[name];
    },

    addToDataFields: function (field, name) {
        this.dataFields.push(field);
    },

    addFieldsToMask: function (field) {
        this.object.edit.fieldsToMask.push(field);
    },
    
    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        
        var data = [];
        var element;
        var elementData = {};

        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            elementData = {};
            if(this.currentElements[types[t]]) {
                element = this.currentElements[types[t]];

                if(element.action == "deleted") {
                    elementData = "deleted";
                } else {
                    for (var u=0; u<element.fields.length; u++) {
                        if(element.fields[u].isDirty()) {
                            element.fields[u].unmarkInherited();
                            elementData[element.fields[u].getName()] = element.fields[u].getValue();
                        }
                    }
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
        return this.fieldConfig.name;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            elementData = {};
            if(this.currentElements[types[t]]) {
                element = this.currentElements[types[t]];
                if(element.action != "deleted") {
                    for (var u=0; u<element.fields.length; u++) {
                        if(element.fields[u].isDirty()) {
                            element.fields[u].unmarkInherited();
                            this.dirty = true;
                            return this.dirty;
                        }
                    }
                }
            }
        }

        return this.dirty;
    },

    isMandatory: function () {
        var element;

        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            if(this.currentElements[types[t]]) {
                element = this.currentElements[types[t]];
                if(element.action != "deleted") {
                    for (var u=0; u<element.fields.length; u++) {
                        if(element.fields[u].isMandatory()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    },

    isInvalidMandatory: function () {
        var element;
        var isInvalid = false;
        var invalidMandatoryFields = [];

        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            if(this.currentElements[types[t]]) {
                element = this.currentElements[types[t]];
                if(element.action != "deleted") {
                    for (var u=0; u<element.fields.length; u++) {
                        if(element.fields[u].isMandatory()) {
                            if(element.fields[u].isInvalidMandatory()) {
                                invalidMandatoryFields.push(element.fields[u].getTitle() + " (" + element.fields[u].getName() + "|" + types[t] + ")");
                                isInvalid = true;
                            }
                        }
                    }
                }
            }
        }

        // return the error messages not bool, this is handled in object/edit.js
        if(isInvalid) {
            return invalidMandatoryFields;
        }

        return isInvalid;
    }

});

pimcore.object.tags.objectbricks.addMethods(pimcore.object.helpers.edit);