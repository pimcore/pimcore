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
        this.dataFields = {};
        this.layoutIds = [];

        if (data) {
            this.data = data;
        }
        fieldConfig.noteditable = typeof fieldConfig.noteditable != 'undefined' ? fieldConfig.noteditable : false;
        this.fieldConfig = fieldConfig;
    },

    loadFieldDefinitions: function () {

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_class_objectbricktree'),
            params: {
                class_id: this.object.data.general.o_classId,
                object_id: this.object.id,
                field_name: this.getName(),
                layoutId: this.object.data.currentLayoutId,
                forObjectEditor: 1

            },
            success: this.initData.bind(this)
        });

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
            border: this.fieldConfig.border,
            style: "margin-bottom: 10px",
            componentCls: "object_field object_field_type_" + this.type,
            items: [this.tabpanel]
        };

        this.component = new Ext.Panel(panelConf);

        return this.component;
    },

    initData: function (response) {

        var bricksData = Ext.decode(response.responseText);
        this.objectbricks = bricksData.objectbricks;
        this.layoutDefinitions = bricksData.layoutDefinitions;

        this.component.insert(0, this.getControls());
        if (this.data.length > 0) {
            for (var i = 0; i < this.data.length; i++) {
                if (this.data[i] != null) {
                    this.preventDelete[this.data[i].type] = this.data[i].inherited;
                    this.addBlockElement(i, this.data[i].type, this.data[i], true, this.data[i].title, false);
                }
            }
        }

        this.tabpanel.setActiveTab(0);

        pimcore.layout.refresh();
    },

    buildMenu: function (data, blockElement) {
        var menu = [];

        if (data) {
            for (var i = 0; i < data.length; i++) {

                var elementData = data[i];
                if (this.addedTypes[elementData.key]) {
                    continue;
                }

                var menuItem = {
                    text: elementData.title ? t(elementData.title) : t(elementData.text),
                    iconCls: elementData.iconCls
                };
                if (elementData.group) {
                    var subMenu = this.buildMenu(elementData.children, blockElement);
                    if (subMenu.length == 0) {
                        continue;
                    }
                    menuItem.menu = subMenu;
                } else {
                    menuItem.handler = this.addBlock.bind(this, blockElement, elementData.key, elementData.title);
                }

                menu.push(menuItem);


            }
        }
        return menu;
    },


    getControls: function (blockElement) {

        var menuData = this.objectbricks;
        var menu = this.buildMenu(menuData, blockElement);

        var items = [];

        if (!this.fieldConfig.noteditable) {

            if (menu.length == 1) {
                if (!menu[0].menu) {
                    var handler = menu[0].menu ? menu[0].menu[0].handler : menu[0].handler;
                    items.push({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus",
                        handler: handler
                    });
                } else {
                    items.push({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus",
                        menu: menu
                    });

                }
            } else if (menu.length > 1) {
                    items.push({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus",
                        menu: menu
                    });
                }
            }

            items.push({
                xtype: "tbtext",
                text: t(this.fieldConfig.title)
            });

            var toolbar = new Ext.Toolbar({
                items: items
            });

            return toolbar;
        }
    ,

        addBlock: function (blockElement, type, title) {

            var index = 0;

            this.addBlockElement(index, type, null, false, title, true);
        }
    ,

        removeBlock: function (blockElement) {

            Ext.MessageBox.confirm(' ', t('delete_message'), function (blockElement, answer) {
                if (answer == "yes") {

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
            return false;
        }
    ,


        getCurrentElementsCount: function () {
            var i = 0;
            var types = Object.keys(this.currentElements);
            for (var t = 0; t < types.length; t++) {
                if (this.currentElements[types[t]]) {
                    var element = this.currentElements[types[t]];
                    if (element.action != "deleted") {
                        i++;
                    }
                }
            }
            return i;
        }
    ,

        addBlockElement: function (index, type, blockData, ignoreChange, title, manuallyAdded) {
            if (!type) {
                return;
            }
            if (!this.layoutDefinitions[type]) {
                return;
            }
            if (this.fieldConfig.maxItems && this.getCurrentElementsCount() >= this.fieldConfig.maxItems) {
                Ext.Msg.alert(t("error"), t("limit_reached"));
                return;
            }

            var dataFields = {};
            var currentData = {};
            var currentMetaData = {};

            if (blockData) {
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
                    if(dataFields[name]) {
                        // this is especially for localized fields which get aggregated here into one field definition
                        // in the case that there are more than one localized fields in the class definition
                        // see also ClassDefinition::extractDataDefinitions();
                        if(typeof dataFields[name]["addReferencedField"]){
                            dataFields[name].addReferencedField(field);
                        }
                    } else {
                        dataFields[name] = field;
                    }
                }
            };

            var childConfig = this.layoutDefinitions[type];

            var blockElement = new Ext.Panel({
                //bodyStyle: "padding:10px;",
                style: "margin: 0 0 10px 0;",
                cls: 'pimcore_objectbrick_item',
                closable: !this.fieldConfig.noteditable,
                autoHeight: true,
                border: false,
                title: title ? t(title) : t(type),
                // items: items
                items: [],
                listeners: {
                    afterrender: function (childConfig, dataProvider, manuallyAdded, panel) {
                        if (!panel.__tabpanel_initialized) {
                            var copy = Ext.decode(Ext.encode(childConfig));
                            var children = this.getRecursiveLayout(copy, null, {
                                containerType: "objectbrick",
                                containerKey: type,
                                ownerName: this.fieldConfig.name,
                                applyDefaults: manuallyAdded
                            }, false, true, dataProvider);
                            if (this.fieldConfig.noteditable && children) {
                                children.forEach(function (record) {
                                    record.disabled = true;
                                });
                            }

                            if (children) {
                                panel.add(children);
                            }

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
                    }.bind(this, childConfig, dataProvider, manuallyAdded)

                }
            });

            if (!this.fieldConfig.noteditable) {
                blockElement.on("beforeclose", this.removeBlock.bind(this, blockElement));
            }

            this.component.remove(this.component.getComponent(0));

            this.addedTypes[type] = true;

            blockElement.key = type;
            blockElement.fieldtype = type;
            this.tabpanel.add(blockElement);
            this.component.insert(0, this.getControls(null));

            this.tabpanel.updateLayout();
            this.component.updateLayout();

            this.currentElements[type] = null;
            this.currentElements[type] = {
                container: blockElement,
                fields: dataFields,
                type: type
            };

            if (!ignoreChange) {
                this.dirty = true;
                this.tabpanel.setActiveTab(blockElement);
            }
        }
    ,

        getLayoutShow: function () {

            this.component = this.getLayoutEdit();

            return this.component;
        }
    ,

        getValue: function () {

            var data = [];
            var element;
            var elementData = {};

            var types = Object.keys(this.currentElements);
            for (var t = 0; t < types.length; t++) {
                elementData = {};
                if (this.currentElements[types[t]]) {
                    element = this.currentElements[types[t]];

                    if (element.action == "deleted") {
                        elementData = "deleted";
                    } else {
                        var elementFieldNames = Object.keys(element.fields);
                        for (var u = 0; u < elementFieldNames.length; u++) {
                            var elementFieldName = elementFieldNames[u];
                            var field = element.fields[elementFieldName];
                            try {
                                if (field.isDirty()) {
                                    field.unmarkInherited();
                                    elementData[field.getName()] = field.getValue();
                                }
                            } catch (e) {
                                console.log(e);
                                elementData[field.getName()] = "";
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
        }
    ,

        getName: function () {
            return this.fieldConfig.name;
        }
    ,

        isDirty: function () {
            if (!this.isRendered()) {
                return false;
            }

            var types = Object.keys(this.currentElements);
            for (var t = 0; t < types.length; t++) {
                if (this.currentElements[types[t]]) {
                    var element = this.currentElements[types[t]];
                    if (element.action != "deleted") {
                        var elementFieldNames = Object.keys(element.fields);
                        for (var u = 0; u < elementFieldNames.length; u++) {
                            var elementFieldName = elementFieldNames[u];
                            var field = element.fields[elementFieldName];
                            if (field.isDirty()) {

                                this.dirty = true;

                                if (field.fieldConfig.fieldtype == "localizedfields") {
                                    field.dataIsNotInherited(true);
                                } else {
                                    field.unmarkInherited();
                                }


                                return this.dirty;

                            }
                        }
                    }
                }
            }

            return this.dirty;
        }
    ,

        markInherited:function (metaData) {
            // nothing to do, only sub-elements can be marked
        }
    ,

        dataIsNotInherited: function () {
            var types = Object.keys(this.currentElements);
            for (var t = 0; t < types.length; t++) {
                if (this.currentElements[types[t]]) {
                    var element = this.currentElements[types[t]];
                    if (element.action != "deleted") {
                        var elementFieldNames = Object.keys(element.fields);
                        for (var u = 0; u < elementFieldNames.length; u++) {
                            var elementFieldName = elementFieldNames[u];
                            var field = element.fields[elementFieldName];
                            if (field.isDirty()) {
                                if (field.fieldConfig.fieldtype == "localizedfields") {
                                    field.dataIsNotInherited(true);
                                } else {
                                    field.unmarkInherited();
                                }
                            }
                        }
                    }
                }
            }
        }
    ,

        isMandatory: function () {
            var element;

            var types = Object.keys(this.currentElements);
            for (var t = 0; t < types.length; t++) {
                if (this.currentElements[types[t]]) {
                    element = this.currentElements[types[t]];
                    if (element.action != "deleted") {
                        var elementFieldNames = Object.keys(element.fields);
                        for (var u = 0; u < elementFieldNames.fields.length; u++) {
                            var elementFieldName = elementFieldNames[u];
                            if (element.fields[elementFieldName].isMandatory()) {
                                return true;
                            }
                        }
                    }
                }
            }

            return false;
        }

    });

pimcore.object.tags.objectbricks.addMethods(pimcore.object.helpers.edit);
