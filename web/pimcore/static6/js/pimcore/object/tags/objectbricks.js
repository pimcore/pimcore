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
        fieldConfig.noteditable = typeof fieldConfig.noteditable != 'undefined' ? fieldConfig.noteditable : false;
        this.fieldConfig = fieldConfig;
    },

    loadFieldDefinitions: function () {
        this.fieldstore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: "/admin/class/objectbrick-list",
                reader: {
                    type: 'json',
                    rootProperty: 'objectbricks',
                    idProperty: 'key'
                },
                extraParams: {
                    class_id: this.object.data.general.o_classId,
                    object_id: this.object.id,
                    field_name: this.getName(),
                    layoutId: this.object.data.currentLayoutId
                }
            },
            autoDestroy: false,

            fields: ['key', {name: "fieldConfigigurations", convert: function (v, rec) {
                this.layoutDefinitions[rec.data.key] = rec.data.layoutDefinitions;
            }.bind(this)}],
            listeners: {
                load: this.initData.bind(this)
            }
        });

        this.fieldstore.load();

    },

    getLayoutEdit: function () {

        this.loadFieldDefinitions();

        var panelConf = {
            autoHeight: true,
            activeTab: 0
        };
        this.tabpanel = new Ext.TabPanel(panelConf);

        panelConf = {
            autoHeight: true,
            border: true,
            style: "margin-bottom: 10px",
            componentCls: "object_field",
            items: [this.tabpanel]
        };

        if(this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }
        this.component = new Ext.Panel(panelConf);

        return this.component;
    },

    initData: function (store, records, successful, eOpts ) {

        this.component.insert(0, this.getControls());
        if(this.data.length > 0) {
            for (var i=0; i<this.data.length; i++) {
                if(this.data[i] != null) {
                    this.preventDelete[this.data[i].type] = this.data[i].inherited;
                    this.addBlockElement(i,this.data[i].type, this.data[i], true);
                }
            }
        }

        this.tabpanel.setActiveTab(0);

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

        if(!this.fieldConfig.noteditable) {

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
            }
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

        this.addBlockElement(index, type);
    },

    removeBlock: function (blockElement) {

        Ext.MessageBox.confirm(t('delete_objectbrick'), t('delete_objectbrick_text'), function(blockElement, answer) {
            if(answer == "yes") {

                var key = blockElement.key;
                this.currentElements[key].action = "deleted";

                this.tabpanel.remove(blockElement);
                this.addedTypes[blockElement.fieldtype] = false;
                this.component.remove(this.component.getComponent(0));
                this.component.insert(0, this.getControls());
                this.component.updateLayout();

                this.dirty = true;
            }
        }.bind(this, blockElement), this);
    },


    getCurrentElementsCount: function() {
        var i = 0;
        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            if (this.currentElements[types[t]]) {
                var element = this.currentElements[types[t]];
                if (element.action != "deleted") {
                    i++;
                }
            }
        }
        return i;
    },

    addBlockElement: function (index, type, blockData, ignoreChange) {
        if(!type){
            return;
        }
        if(!this.layoutDefinitions[type]) {
            return;
        }
        if (this.fieldConfig.maxItems && this.getCurrentElementsCount() >= this.fieldConfig.maxItems) {
            Ext.Msg.alert(t("error"),t("limit_reached"));
            return;
        }

        var dataFields = [];
        var currentData = {};
        var currentMetaData = {};

        if(blockData) {
            currentData = blockData.data;
            currentMetaData = blockData.metaData;
        }

        var dataProvider = {
            getDataForField: function (fieldConfig) {
                var name = fieldConfig.name;
                return currentData[name];
            },

            getMetaDataForField: function (fieldConfig) {
                var name = fieldConfig.name;
                return currentMetaData[name];
            },

            addToDataFields: function (field, name) {
                dataFields.push(field);
            }
        };

        var childConfig = this.layoutDefinitions[type];

        var blockElement = new Ext.Panel({
            //bodyStyle: "padding:10px;",
            style: "margin: 0 0 10px 0;",
            autoHeight: true,
            border: false,
            title: ts(type),
            // items: items
            items: [],
            listeners: {
                afterrender: function (childConfig, dataProvider, panel) {
                    if (!panel.__tabpanel_initialized) {
                        var children = this.getRecursiveLayout(childConfig, null,             {
                            containerType: "objectbrick",
                            containerKey: type,
                            ownerName: this.fieldConfig.name
                        }, false, true, dataProvider);
                        if(this.fieldConfig.noteditable && children) {
                            children.forEach(function (record) {
                                record.disabled = true;
                            });
                        }

                        panel.add(children);
                        panel.updateLayout();

                        if (panel.setActiveTab) {
                            var activeTab = panel.items.items[0];
                            if (activeTab) {
                                activeTab.updateLayout();
                                panel.setActiveTab(activeTab);
                            }
                        }

                        panel.__tabpanel_initialized = true;


                    }
                }.bind(this, childConfig, dataProvider)

            }
        });

        this.component.remove(this.component.getComponent(0));

        this.addedTypes[type] = true;

        if(!this.fieldConfig.noteditable) {
            var control = this.getDeleteControl(type, blockElement);
            if(control) {
                blockElement.insert(0, control);
            }
        }

        blockElement.key = type;
        blockElement.fieldtype = type;
        this.tabpanel.add(blockElement);
        this.component.insert(0, this.getControls());

        this.tabpanel.updateLayout();
        this.component.updateLayout();

        this.currentElements[type] = null;
        this.currentElements[type] = {
            container: blockElement,
            fields: dataFields,
            type: type
        };

        if(!ignoreChange) {
            this.dirty = true;
            this.tabpanel.setActiveTab(blockElement);
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

        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            elementData = {};
            if(this.currentElements[types[t]]) {
                element = this.currentElements[types[t]];

                if(element.action == "deleted") {
                    elementData = "deleted";
                } else {
                    for (var u=0; u<element.fields.length; u++) {

                        try {
                            if(element.fields[u].isDirty()) {
                                element.fields[u].unmarkInherited();
                                elementData[element.fields[u].getName()] = element.fields[u].getValue();
                            }
                        } catch (e) {
                            console.log(e);
                            elementData[element.fields[u].getName()] = "";
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
            if(this.currentElements[types[t]]) {
                var element = this.currentElements[types[t]];
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

    markInherited:function (metaData) {
        // nothing to do, only sub-elements can be marked
    },

    dataIsNotInherited: function() {
        var types = Object.keys(this.currentElements);
        for(var t=0; t < types.length; t++) {
            if(this.currentElements[types[t]]) {
                var element = this.currentElements[types[t]];
                if(element.action != "deleted") {
                    for (var u=0; u<element.fields.length; u++) {
                        if(element.fields[u].isDirty()) {
                            element.fields[u].unmarkInherited();
                        }
                    }
                }
            }
        }
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
                                invalidMandatoryFields.push(element.fields[u].getTitle()
                                    + " (" + element.fields[u].getName() + "|" + types[t] + ")");
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
