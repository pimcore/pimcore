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

pimcore.registerNS("pimcore.object.tags.fieldcollections");
pimcore.object.tags.fieldcollections = Class.create(pimcore.object.tags.abstract, {

    type: "fieldcollections",
    dirty: false,

    initialize: function (data, fieldConfig) {

        this.dirty = false;
        this.data = [];
        this.currentElements = [];
        this.layoutDefinitions = {};
        this.dataFields = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;

        this.eventDispatcherKey = pimcore.eventDispatcher.registerTarget(this.eventDispatcherKey, this);
    },

    setObject:function (object) {
        this.object = object;
    },


    getGridColumnConfig: function(field) {
        return {text: ts(field.label), width: 150, sortable: false, dataIndex: field.key,
                renderer: function (key, value, metaData, record) {
                    this.applyPermissionStyle(key, value, metaData, record);

                    return t("not_supported");
                }.bind(this, field.key)};
    },

    loadFieldDefinitions: function () {

        var allowedTypes = this.fieldConfig.allowedTypes;
        if(!allowedTypes) {
            allowedTypes = [];
        }

        var extraParams = {
            allowedTypes: allowedTypes.join(","),
            object_id: this.object.id,
            field_name: this.fieldConfig.name,
            forObjectEditor: 1
        };

        if (typeof this.fieldConfig.layoutId !== "undefined") {
            extraParams.layoutId = this.fieldConfig.layoutId;
        }

        Ext.Ajax.request({
            url: "/admin/class/fieldcollection-tree",
            params: extraParams,
            success: this.initData.bind(this)
        });

    },

    getLayoutEdit: function () {

        this.loadFieldDefinitions();

        var panelConf = {
            autoHeight: true,
            border: true,
            style: "margin-bottom: 10px",
            componentCls: "object_field",
            collapsible: this.fieldConfig.collapsible,
            collapsed: this.fieldConfig.collapsed
        };
        if(this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }

        this.component = new Ext.Panel(panelConf);

        this.component.addListener("render", function() {
            if(this.object.data.metaData[this.getName()] && this.object.data.metaData[this.getName()].hasParentValue) {
                this.addInheritanceSourceButton(this.object.data.metaData[this.getName()]);
            }
        }.bind(this));

        this.component.on("destroy", function() {
            pimcore.eventDispatcher.unregisterTarget(this.eventDispatcherKey);
        }.bind(this));

        return this.component;
    },


    postSaveObject: function(object, task) {

        if (object.id == this.object.id && task == "publish") {
            for (var itemIndex = 0; itemIndex < this.component.items.items.length; itemIndex++) {
                var item = this.component.items.items[itemIndex];
                item["pimcore_oIndex"] = itemIndex;
            }
        }
    },

    initData: function (response) {

        var collectionData = Ext.decode(response.responseText);
        this.fieldcollections = collectionData.fieldcollections;
        this.layoutDefinitions = collectionData.layoutDefinitions;

        if(this.data.length < 1) {
            this.component.add(this.getControls());
        } else {
            for (var i=0; i<this.data.length; i++) {
                this.addBlockElement(
                    i,
                    {
                        type: this.data[i].type,
                        title: this.data[i].title,
                        oIndex: this.data[i].oIndex
                    },
                    this.data[i].data,
                    true);
            }
        }

        this.component.updateLayout();
    },

    buildMenu: function(data, blockElement) {
        var collectionMenu = [];

        if (data) {
            for(var i=0; i<data.length; i++) {
                var elementData = data[i];

                var menuItem = {
                    text: elementData.title ? ts(elementData.title) : ts(elementData.text),
                    iconCls: elementData.iconCls
                };
                if (elementData.group) {
                    var subMenu = this.buildMenu(elementData.children, blockElement);
                    menuItem.menu = subMenu;
                } else {
                    menuItem.handler = this.addBlock.bind(this, blockElement, elementData.key, elementData.title);
                }

                collectionMenu.push(menuItem);


            }
        }
        return collectionMenu;

    },

    getControls: function (blockElement, title) {

        var menuData = this.fieldcollections;
        var collectionMenu = this.buildMenu(menuData, blockElement, true);

        var items = [];

        if(collectionMenu.length == 0) {
            items.push({
                xtype: "tbtext",
                text: t("no_collections_allowed")
            });
        } else if(collectionMenu.length == 1 && !collectionMenu[0].menu) {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                handler: collectionMenu[0].handler
            });
        } else  {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                menu: collectionMenu
            });
        }

        if(blockElement) {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "pimcore_block_button_minus",
                iconCls: "pimcore_icon_minus",
                listeners: {
                    "click": this.removeBlock.bind(this, blockElement)
                }
            });

            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: "pimcore_block_button_up",
                iconCls: "pimcore_icon_up",
                listeners: {
                    "click": this.moveBlockUp.bind(this, blockElement)
                }
            });

            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: "pimcore_block_button_down",
                iconCls: "pimcore_icon_down",
                listeners: {
                    "click": this.moveBlockDown.bind(this, blockElement)
                }
            });

            if (title) {
                items.push('->');

                items.push({
                    xtype: "tbtext",
                    text: ts(title)
                });
            }
        }

        var toolbar = new Ext.Toolbar({
            items: items
        });

        return toolbar;
    },

    detectBlockIndex: function (blockElement) {
        // detect index
        var index;

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.component.items.items[s].key == blockElement.key) {
                index = s;
                break;
            }
        }
        return index;
    },

    closeOpenEditors: function () {

        // currently just wysiwyg
        for (var i=0; i<this.currentElements.length; i++) {
            if(typeof this.currentElements[i] == "object") {
                for(var e=0; e<this.currentElements[i]["fields"].length; e++) {
                    if(typeof this.currentElements[i]["fields"][e]["close"] == "function") {
                        this.currentElements[i]["fields"][e].close();
                    }
                }
            }
        }
    },

    addBlock: function (blockElement, type, title) {

        this.closeOpenEditors();

        if(this.fieldConfig.maxItems) {
            var itemAmount = 0;
            for(var s=0; s<this.component.items.items.length; s++) {
                if(typeof this.component.items.items[s].key != "undefined") {
                    itemAmount++;
                }
            }

            if(itemAmount >= this.fieldConfig.maxItems) {
                Ext.MessageBox.alert(t("error"), t("limit_reached"));
                return;
            }
        }

        var index = 0;
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }

        this.addBlockElement(index + 1, {
            type: type,
            title: title
        });
    },

    removeBlock: function (blockElement) {

        this.closeOpenEditors();

        var key = blockElement.key;
        this.currentElements[key] = "deleted";

        this.component.remove(blockElement);
        this.dirty = true;

        // check for remaining elements
        if(this.component.items.items.length < 1) {
            this.component.removeAll();
            this.component.add(this.getControls());
            this.component.updateLayout();
            this.currentElements = [];
        }

        this.updateBlockIndices();
    },

    moveBlockUp: function (blockElement) {

        this.closeOpenEditors();

        this.component.moveBefore(blockElement, blockElement.previousSibling());
        this.dirty = true;

        this.updateBlockIndices();
    },

    moveBlockDown: function (blockElement) {

        this.closeOpenEditors();

        this.component.moveAfter(blockElement, blockElement.nextSibling());
        this.dirty = true;

        this.updateBlockIndices();
    },

    addBlockElement: function (index, config, blockData, ignoreChange) {

        var type = config.type;
        var oIndex = config.oIndex;
        var title = config.title ? config.title : type;

        this.closeOpenEditors();

        if(!type){
            return;
        }
        if(!this.layoutDefinitions[type]) {
            return;
        }

        // remove the initial toolbar if there is no element
        if(this.currentElements.length < 1) {
            this.component.removeAll();
        }

        this.dataFields = {};
        this.currentData = {};

        if(blockData) {
            this.currentData = blockData;
        }

        var items =  this.getRecursiveLayout(this.layoutDefinitions[type], null,
            {
                containerType: "fieldcollection",
                containerName: this.fieldConfig.name,
                containerKey: type,
                index: index,
            }, false, false, this, true).items;


        var blockElement = new Ext.Panel({
            pimcore_oIndex: oIndex,
            bodyStyle: "padding:10px;",
            style: "margin: 0 0 10px 0;",
            manageHeight: false,
            border: false,
            items: items,
            disabled: this.fieldConfig.noteditable
        });

        blockElement.insert(0, this.getControls(blockElement, title));

        blockElement.key = this.currentElements.length;
        blockElement.fieldtype = type;
        this.component.insert(index, blockElement);
        this.component.updateLayout();

        this.currentElements.push({
            container: blockElement,
            fields: this.dataFields,
            type: type
        });

        if(!ignoreChange) {
            this.dirty = true;
        }

        this.dataFields = {};
        this.currentData = {};

        this.updateBlockIndices();
    },

    updateBlockIndices: function() {
        for (var itemIndex = 0; itemIndex < this.component.items.items.length; itemIndex++) {
            var item = this.component.items.items[itemIndex];

            for (j = 0; j < this.currentElements.length; j++) {
                if (item !== this.currentElements[j].container) continue;

                var fields = this.currentElements[j].fields;
                for (fieldName in fields) {
                    if (this.currentElements[j].fields.hasOwnProperty(fieldName)) {
                        fields[fieldName].context.index = itemIndex;
                    }
                }
            }
        }
    },

    getDataForField: function (fieldConfig) {
        var name = fieldConfig.name;
        return this.currentData[name];
    },

    getMetaDataForField: function(fieldConfig) {
        return null;
    },

    addToDataFields: function (field, name) {
        if(this.dataFields[name]) {
            // this is especially for localized fields which get aggregated here into one field definition
            // in the case that there are more than one localized fields in the class definition
            // see also Object_Class::extractDataDefinitions();
            if(typeof this.dataFields[name]["addReferencedField"]){
                this.dataFields[name].addReferencedField(field);
            }
        } else {
            this.dataFields[name] = field;
        }
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();

        return this.component;
    },

    getValue: function () {

        var data = [];
        var element;
        var elementData = {};

        for(var s=0; s<this.component.items.items.length; s++) {
            elementData = {};
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    try {
                        // no check for dirty, ... always send all field to the server
                        elementData[element.fields[elementFieldName].getName()] = element.fields[elementFieldName].getValue();
                    } catch (e) {
                        console.log(e);
                        elementData[element.fields[elementFieldName].getName()] = "";
                    }

                }

                data.push({
                    type: element.type,
                    data: elementData,
                    oIndex: element.container.pimcore_oIndex
                });
            }
        }

        return data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {

        // check elements
        var element;

        if(!this.isRendered()) {
            return false;
        }

        if(typeof this.component.items == "undefined") {
            return false;
        }

       var theItems = this.component.items.items;

        for(var s=0; s<theItems.length; s++) {
            if(this.currentElements[theItems[s].key]) {
                element = this.currentElements[theItems[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    if(element.fields[elementFieldName].isDirty()) {
                        return true;
                    }
                }
            }
        }

        return this.dirty;
    },

    isMandatory: function () {
        var element;

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    if(element.fields[elementFieldName].isMandatory()) {
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
        var invalidMandatoryFields = [];

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    if(element.fields[elementFieldName].isMandatory()) {
                        if(element.fields[elementFieldName].isInvalidMandatory()) {
                            invalidMandatoryFields.push(element.fields[elementFieldName].getTitle() + " ("
                                                                    + element.fields[elementFieldName].getName() + ")");
                            isInvalid = true;
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

pimcore.object.tags.fieldcollections.addMethods(pimcore.object.helpers.edit);
