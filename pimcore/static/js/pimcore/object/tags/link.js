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

pimcore.registerNS("pimcore.object.tags.link");
pimcore.object.tags.link = Class.create(pimcore.object.tags.abstract, {

    type: "link",
    dirty: false,

    initialize: function (data, fieldConfig) {

        this.data = "";
        this.defaultData = {
            type: "internal",
            path: "",
            parameters: "",
            anchor: "",
            accesskey: "",
            rel: "",
            tabindex: "",
            target: ""
        };

        if (data) {
            this.data = data;
        }
        else {
            this.data = this.defaultData;
        }
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {
        var renderer = function(key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }
            if(value) {
                return value.text;
            }
            return t("empty");

        }.bind(this, field.key);

        return {header: ts(field.label), sortable: true, dataIndex: field.key, renderer: renderer};
    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            itemCls: "object_field"
        };

        this.button = new Ext.Button({
            iconCls: "pimcore_icon_edit_link",
            handler: this.openEditor.bind(this)
        });

        var textValue = "[not set]";
        if (this.data.text) {
            textValue = this.data.text;
        }
        this.displayField = new Ext.form.DisplayField({
            value: textValue
        });

        this.component = new Ext.form.CompositeField({
            xtype: 'compositefield',
            fieldLabel: this.fieldConfig.title,
            combineErrors: false,
            items: [this.displayField, this.button],
            itemCls: "object_field"
        });

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        //this.layout.disable();
        this.button.hide();
        
        return this.component;
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    openEditor: function () {


        this.fieldPath = new Ext.form.TextField({
            fieldLabel: t("path"),
            value: this.data.path,
            name: "path",
            width: 320,
            cls: "pimcore_droptarget_input"
        });


        this.fieldPath.on("render", function (el) {
            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.fieldPath.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    return Ext.dd.DropZone.prototype.dropAllowed;

                }.bind(this),

                onNodeDrop : function (target, dd, e, data) {
                    if (data.node.attributes.elementType == "asset" || data.node.attributes.elementType == "document") {
                        this.fieldPath.setValue(data.node.attributes.path);
                        return true;
                    }
                    return false;
                }.bind(this)
            });
        }.bind(this));


        this.form = new Ext.FormPanel({
            items: [
                {
                    xtype:'tabpanel',
                    activeTab: 0,
                    deferredRender: false,
                    defaults:{autoHeight:true, bodyStyle:'padding:10px'},
                    border: false,
                    items: [
                        {
                            title:t('basic'),
                            layout:'form',
                            border: false,
                            defaultType: 'textfield',
                            items: [
                                {
                                    fieldLabel: t('text'),
                                    name: 'text',
                                    value: this.data.text
                                },
                                {
                                    xtype: "compositefield",
                                    items: [this.fieldPath, {
                                        xtype: "button",
                                        iconCls: "pimcore_icon_search",
                                        handler: this.openSearchEditor.bind(this)
                                    }]
                                },
                                {
                                    xtype:'fieldset',
                                    title: t('properties'),
                                    collapsible: false,
                                    autoHeight:true,
                                    defaultType: 'textfield',
                                    items :[
                                        {
                                            xtype: "combo",
                                            fieldLabel: t('target'),
                                            name: 'target',
                                            triggerAction: 'all',
                                            editable: true,
                                            store: ["","_blank","_self","_top","_parent"],
                                            value: this.data.target
                                        },
                                        {
                                            fieldLabel: t('parameters'),
                                            name: 'parameters',
                                            value: this.data.parameters
                                        },
                                        {
                                            fieldLabel: t('anchor'),
                                            name: 'anchor',
                                            value: this.data.anchor
                                        },
                                        {
                                            fieldLabel: t('title'),
                                            name: 'title',
                                            value: this.data.title
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            title:t('advanced'),
                            layout:'form',
                            defaultType: 'textfield',
                            border: false,
                            items: [
                                {
                                    fieldLabel: t('accesskey'),
                                    name: 'accesskey',
                                    value: this.data.accesskey
                                },
                                {
                                    fieldLabel: t('relation'),
                                    name: 'rel',
                                    value: this.data.rel
                                },
                                {
                                    fieldLabel: t('tabindex'),
                                    name: 'tabindex',
                                    value: this.data.tabindex
                                }
                            ]
                        }
                    ]
                }
            ],
            buttons: [
                {
                    text: t("empty"),
                    listeners:  {
                        "click": this.empty.bind(this)
                    }
                },
                {
                    text: t("cancel"),
                    listeners:  {
                        "click": this.cancel.bind(this)
                    }
                },
                {
                    text: t("save"),
                    listeners: {
                        "click": this.save.bind(this)
                    },
                    icon: "/pimcore/static/img/icon/tick.png"
                }
            ]
        });


        this.window = new Ext.Window({
            width: 500,
            height: 330,
            title: "Edit link",
            items: [this.form],
            layout: "fit",
            listeners: {
                "close": function () {
                    this.getObject().edit.enableFieldMasks();
                }.bind(this)
            }
        });
        this.window.show();

        // this is because of underlying activated wysiwyg, which will also catch the drop event, when the panel is not disabled
        this.getObject().edit.disableFieldMasks();
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset","document"]
        });
    },

    addDataFromSelector: function (item) {
        if (item) {
            this.fieldPath.setValue(item.fullpath);
            return true;
        }
    },

    save: function () {
        var values = this.form.getForm().getFieldValues();
        if(Ext.encode(values) != Ext.encode(this.data)) {
            this.dirty = true; 
        }
        this.data = values;

        var textValue = "[not set]"; 
        if (this.data.text) {
            textValue = this.data.text;
        }
        this.displayField.setValue(textValue);

        // close window
        this.window.close();
    },

    empty: function () {

        // close window
        this.window.close();

        this.data = this.defaultData;
        this.dirty = true; 

        // set text
        this.displayField.setValue("[not set]");
    },

    cancel: function () {
        this.window.close();
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }
});