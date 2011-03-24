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

    initialize: function (data, layoutConf) {

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
        this.layoutConf = layoutConf;

    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.layoutConf.title,
            name: this.layoutConf.name,
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

        this.layout = new Ext.form.CompositeField({
            xtype: 'compositefield',
            fieldLabel: this.layoutConf.title,
            combineErrors: false,
            items: [this.displayField, this.button],
            itemCls: "object_field"
        });

        return this.layout;
    },


    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        //this.layout.disable();
        this.layout.items.splice(1,1); // remove the edit buttom
        
        return this.layout;
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.layoutConf.name;
    },

    openEditor: function () {


        this.fieldPath = new Ext.form.TextField({
            fieldLabel: "Path",
            value: this.data.path,
            name: "path",
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
                            title:'Basic',
                            layout:'form',
                            border: false,
                            defaultType: 'textfield',
                            items: [
                                {
                                    fieldLabel: 'Text',
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
                                    title: 'Properties',
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
                                            fieldLabel: 'Parameters',
                                            name: 'parameters',
                                            value: this.data.parameters
                                        },
                                        {
                                            fieldLabel: 'Anchor',
                                            name: 'anchor',
                                            value: this.data.anchor
                                        },
                                        {
                                            fieldLabel: 'Title',
                                            name: 'title',
                                            value: this.data.title
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            title:'Advanced',
                            layout:'form',
                            defaultType: 'textfield',
                            border: false,
                            items: [
                                {
                                    fieldLabel: 'Accesskey',
                                    name: 'accesskey',
                                    value: this.data.accesskey
                                },
                                {
                                    fieldLabel: 'Relation',
                                    name: 'rel',
                                    value: this.data.rel
                                },
                                {
                                    fieldLabel: 'Tabindex',
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
            layout: "fit"
        });
        this.window.show();
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

        // set text
        this.displayField.setValue("[not set]");
    },

    cancel: function () {
        this.window.close();
    }
});