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

pimcore.registerNS("pimcore.document.tags.link");
pimcore.document.tags.link = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        if (!data) {
            data = {};
        }

        this.defaultData = {
            type: "internal",
            path: "",
            parameters: "",
            anchor: "",
            accesskey: "",
            rel: "",
            tabindex: "",
            target: "",
            "class": "",
            attributes: ""
        };

        this.data = mergeObject(this.defaultData, data);

        this.id = id;
        this.name = name;
        this.setupWrapper();
        if (!options) {
            options = {};
        }

        this.options = options;

        Ext.get(id).setStyle({
            display:"inline"
        });
        Ext.get(id).insertHtml("beforeEnd",'<span class="pimcore_tag_link_text">' + this.getLinkContent() + '</span>');

        var button = new Ext.Button({
            iconCls: "pimcore_icon_edit_link",
            cls: "pimcore_edit_link_button",
            listeners: {
                "click": this.openEditor.bind(this)
            }
        });
        button.render(id);
    },

    openEditor: function () {

        for (var i = 0; i < editables.length; i++) {
            if (editables[i].getType() == "wysiwyg") {
                editables[i].endCKeditor();
            }
        }

        this.fieldPath = new Ext.form.TextField({
            fieldLabel: t('path'),
            value: this.data.path,
            name: "path",
            width: 320,
            cls: "pimcore_droptarget_input"
        });

        var initDD = function (el) {
            var domElement = el.getEl().dom;
            domElement.dndOver = false;

            domElement.reference = this;

            dndZones.push(domElement);
            el.getEl().on("mouseover", function (e) {
                this.dndOver = true;
            }.bind(domElement));
            el.getEl().on("mouseout", function (e) {
                this.dndOver = false;
            }.bind(domElement));

        }

        this.fieldPath.on("render", initDD.bind(this));

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
                                            mode: "local",
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
                            title: t('advanced'),
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
                                    width: 300,
                                    value: this.data.rel
                                },
                                {
                                    fieldLabel: ('tabindex'),
                                    name: 'tabindex',
                                    value: this.data.tabindex
                                },
                                {
                                    fieldLabel: t('class'),
                                    name: 'class',
                                    width: 300,
                                    value: this.data["class"]
                                },
                                {
                                    fieldLabel: t('attributes') + ' (key="value")',
                                    name: 'attributes',
                                    width: 300,
                                    value: this.data["attributes"]
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
            modal: true,
            width: 500,
            height: 330,
            title: t("edit_link"),
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

    getLinkContent: function () {

        var text = "[" + t("not_set") + "]";
        if (this.data.text) {
            text = this.data.text;
        }
        if (this.data.path) {
            return '<a href="' + this.data.path + '">' + text + '</a>'
        }
        return text;
    },

    onNodeDrop: function (target, dd, e, data) {

        if(this.dndAllowed(data)){
            this.fieldPath.setValue(data.node.attributes.path);
            return true;
        } else return false;
    },

    onNodeOver: function(target, dd, e, data) {
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    dndAllowed: function(data) {

        if (data.node.attributes.elementType == "asset" && data.node.attributes.type != "folder") {
            return true;
        } else if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page" || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
            return true;
        }
        return false;

    },

    save: function () {

        // close window
        this.window.hide();

        var values = this.form.getForm().getFieldValues();
        this.data = values;

        // set text
        Ext.get(this.id).query(".pimcore_tag_link_text")[0].innerHTML = this.getLinkContent();

        this.reload();
    },

    reload : function () {
        if (this.options.reload) {
            this.reloadDocument();
        }
    },

    empty: function () {

        // close window
        this.window.hide();

        this.data = this.defaultData;

        // set text
        Ext.get(this.id).query(".pimcore_tag_link_text")[0].innerHTML = this.getLinkContent();
    },

    cancel: function () {
        this.window.hide();
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "link";
    }
});