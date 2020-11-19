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

pimcore.registerNS("pimcore.object.objectbricks.field");
pimcore.object.objectbricks.field = Class.create(pimcore.object.classes.klass, {

    allowedInType: 'objectbrick',
    disallowedDataTypes: [
        "reverseManyToManyObjectRelation",
        "user",
        "fieldcollections",
        "localizedfields",
        "objectbricks",
        "objectsMetadata"
    ],
    uploadRoute: 'pimcore_admin_dataobject_class_importobjectbrick',
    exportRoute: "pimcore_admin_dataobject_class_exportobjectbrick",
    context: "objectbrick",
    baseStore: {},
    classStores: {},
    availableClasses: {},
    currentElements: [],

    getId: function () {
        return this.data.key;
    },

    getRootPanel: function () {
        this.currentElements = [];
        this.initClassData();

        this.groupField = new Ext.form.field.Text(
            {
                width: 600,
                name: "group",
                fieldLabel: t("group"),
                value: this.data.group
            });

        this.rootPanel = new Ext.form.FormPanel({
            title: '<b>' + t("general_settings") + '</b>',
            bodyStyle: 'padding: 10px; border-top: 1px solid #606060 !important;',
            defaults: {
                labelWidth: 200
            },
            items: [
                {
                    xtype: "textfield",
                    width: 600,
                    name: "parentClass",
                    fieldLabel: t("parent_php_class"),
                    value: this.data.parentClass
                },
                {
                    xtype: "textfield",
                    width: 600,
                    name: "implementsInterfaces",
                    fieldLabel: t("implements_interfaces"),
                    value: this.data.implementsInterfaces
                },
                {
                    xtype: "textfield",
                    width: 600,
                    name: "title",
                    fieldLabel: t("title"),
                    value: this.data.title
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("generate_type_declarations"),
                    name: "generateTypeDeclarations",
                    checked: this.data.generateTypeDeclarations
                },
                this.groupField,
                this.getClassDefinitionPanel()
            ]
        });

        return this.rootPanel;
    },

    getClassDefinitionPanel: function () {
        this.classDefinitionsItems = new Ext.Panel({
            title: t("class"),
            style: "margin-top: 20px",
            items: [
                this.getAddControl()
            ]
        });

        for (var i = 0; i < this.data.classDefinitions.length; i++) {
            this.addClassDefinition(this.data.classDefinitions[i]);
        }
        return this.classDefinitionsItems;
    },

    getDeleteControl: function (classDefinitionData) {

        var items = [{
            xtype: 'tbtext',
            text: ""
        }];

        if (this.availableClasses[classDefinitionData.classname]) {
            items = [{
                xtype: 'tbtext',
                text: this.availableClasses[classDefinitionData.classname].data.translatedText
            }];
        }

        items.push({
            cls: "pimcore_block_button_minus",
            iconCls: "pimcore_icon_minus",
            listeners: {
                "click": this.removeClassDefinition.bind(this, classDefinitionData)
            }
        });

        return new Ext.Toolbar({
            items: items
        });
    },

    getAddControl: function () {
        var classMenu = [];
        var classNames = Object.keys(this.baseStore);

        for (var i = 0; i < classNames.length; i++) {
            var rec = this.baseStore[classNames[i]];

            if (rec) {
                classMenu.push({
                    text: t(rec.data.translatedText),
                    handler: this.addClassDefinition.bind(this, null, rec.data.text),
                    iconCls: "pimcore_icon_class"
                });
            }
        }

        var items = [];

        if (classMenu.length === 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                text: t(classMenu[0].text),
                iconCls: "pimcore_icon_plus",
                handler: classMenu[0].handler
            });
        } else if (classMenu.length > 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                menu: classMenu
            });
        } else {
            items.push({
                xtype: "tbtext",
                text: t("no_further_classes_allowed")
            });
        }

        return new Ext.Toolbar({
            items: items
        });
    },

    initClassData: function () {
        var objectTypeStore = pimcore.globalmanager.get("object_types_store");
        objectTypeStore.load();

        objectTypeStore.each(function (rec) {
            var data = new Ext.data.Record({id: rec.id, text: rec.data.text, translatedText: rec.data.translatedText});
            this.availableClasses[rec.get("text")] = data;
            this.baseStore[rec.get("text")] = data;
        }.bind(this));
    },

    removeFromOthers: function (name, store) {
        delete (this.baseStore[name]);
    },

    getClassDefinitionElements: function (currentData) {
        if (currentData) {
            this.removeFromOthers(currentData.classname);
        }

        var fieldComboStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig'),
                extraParams: {
                    types: 'objectbricks',
                    gridtype: "all",
                    name: currentData.classname
                },
                reader: {
                    type: 'json',
                    rootProperty: "availableFields"
                }
            },
            fields: ['key', 'label'],
            autoLoad: true,

            forceSelection: true
        });

        var fieldCombo = new Ext.form.ComboBox({
            allowBlank: false,
            value: currentData.fieldname,
            store: fieldComboStore,
            displayField: 'key',
            valueField: 'key',
            name: 'fieldname',
            disableKeyFilter: "true",
            valueNotFoundText: "",
            editable: false,
            listeners: {
                focus: function () {
                    fieldComboStore.load();
                }.bind(this),
                change: function (field, fieldname) {
                    currentData.fieldname = fieldname;
                }
            }
        });

        fieldComboStore.addListener("load", function () {
            fieldCombo.setValue(currentData.fieldname);
        });

        var translatedText = " ";
        if (this.availableClasses[currentData.classname]) {
            translatedText = this.availableClasses[currentData.classname].data.translatedText;
        }

        var classTextfield = new Ext.form.TextField({
            fieldLabel: t('allowed_class_field'),
            labelWidth: 200,
            value: translatedText,
            readOnly: true
        });

        return new Ext.form.FieldSet({
            layout: 'hbox',
            border: false,
            combineErrors: false,
            style: "border-top: 0 !important",
            items: [classTextfield, fieldCombo],
            componentCls: "object_field"
        });
    },

    addClassDefinition: function (classDefinitionData, className) {
        this.classDefinitionsItems.remove(this.classDefinitionsItems.items.get(0));

        var currentData = {};

        if (classDefinitionData) {
            currentData = classDefinitionData;
        } else {
            currentData.classname = className;
            currentData.fieldname = "";
        }

        var element = new Ext.Panel({
            style: "margin-top: 10px",
            bodyStyle: "padding:10px;",
            autoHeight: true,
            border: true,
            tbar: this.getDeleteControl(currentData),
            items: [this.getClassDefinitionElements(currentData)]
        });

        element.key = this.currentElements.length;
        this.classDefinitionsItems.add(element);
        this.classDefinitionsItems.insert(0, this.getAddControl());
        this.classDefinitionsItems.updateLayout();

        this.currentElements.push({
            data: currentData,
            container: element
        });

    },

    removeClassDefinition: function (classDefinitionData) {
        for (var i = 0; i < this.currentElements.length; i++) {
            if (this.currentElements[i].data === classDefinitionData) {
                this.currentElements[i].data.deleted = true;
                this.classDefinitionsItems.remove(this.currentElements[i].container);
            }
        }

        this.baseStore[classDefinitionData.classname] = this.availableClasses[classDefinitionData.classname];

        this.classDefinitionsItems.remove(this.classDefinitionsItems.items.get(0));
        this.classDefinitionsItems.insert(0, this.getAddControl());
        this.classDefinitionsItems.updateLayout();
    },

    save: function () {
        var reload = false;
        var newGroup = this.groupField.getValue();
        if (newGroup != this.data.group) {
            this.data.group = newGroup;
            reload = true;
        }

        this.saveCurrentNode();

        var m = Ext.encode(this.getData());

        this.data.classDefinitions = [];
        for (var i = 0; i < this.currentElements.length; i++) {
            this.data.classDefinitions.push(this.currentElements[i].data);
        }

        var n = Ext.encode(this.data);

        if (this.getDataSuccess) {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_class_objectbrickupdate'),
                method: "PUT",
                params: {
                    configuration: m,
                    values: n,
                    key: this.data.key,
                    title: this.data.title,
                    group: this.data.group
                },
                success: this.saveOnComplete.bind(this, reload)
            });
        }
    },

    saveOnComplete: function (reload, response) {
        var rdata = Ext.decode(response.responseText);
        if (rdata && rdata.success) {
            if (reload) {
                this.parentPanel.tree.getStore().load();
            }
            pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
        } else {
            if (rdata && rdata.message) {
                pimcore.helpers.showNotification(t("error"), rdata.message, "error");
            } else {
                throw "save was not successful, see log files in /var/logs";
            }
        }

    },

    upload: function () {
        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function () {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_class_objectbrickget'),
                params: {
                    id: this.getId()
                },
                success: function (response) {
                    this.data = Ext.decode(response.responseText);
                    this.parentPanel.getEditPanel().removeAll();
                    this.addTree();
                    this.initLayoutFields();
                    this.addLayout();
                    pimcore.layout.refresh();
                }.bind(this)
            });
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    }
});
