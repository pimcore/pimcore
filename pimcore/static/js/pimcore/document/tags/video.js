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

pimcore.registerNS("pimcore.document.tags.video");
pimcore.document.tags.video = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.data = {};

        if (!options) {
            options = {};
            options = {};
        }

        this.options = options;
        this.data = data;

        this.setupWrapper();

        var element = Ext.get("pimcore_video_" + name);
        element.insertHtml("afterBegin", '<div class="pimcore_video_edit_button"></div>');

        var button = new Ext.Button({
            iconCls: "pimcore_icon_edit_link",
            cls: "pimcore_edit_link_button",
            handler: this.openEditor.bind(this)
        });
        button.render(Ext.get(Ext.query(".pimcore_video_edit_button", element.dom)[0]));

        /*var toolbar = [];
        toolbar.push("Type");
        toolbar.push({
            xtype: "combo",
            mode: "local",
            triggerAction: "all",
            store: [
                ["asset","Asset"],
                ["youtube","YouTube"],
                ["vimeo","Vimeo"],
                ["url","URL"]
            ],
            value: this.data.type,
            listeners: {
                "select": function (box, rec, index) {
                    this.data.type = box.getValue();
                    this.reloadDocument();
                }.bind(this)
            }
        });

        if (this.data.type != "asset") {
            this.urlField = new Ext.form.TextField({
                name: "url",
                value: this.data.id,
                emptyText: t("insert_video_url_here")
            });
            toolbar.push(this.urlField);
            toolbar.push({
                xtype: "button",
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.data.id = this.urlField.getValue();
                    this.reloadDocument();
                }.bind(this)
            });
        }


        toolbar.push({
            xtype: "button",
            iconCls: "pimcore_icon_empty",
            handler: function () {
                this.data = null;
                this.reloadDocument();
            }.bind(this)
        });

        this.element = new Ext.Panel({
            width: this.options.width,
            autoHeight: true,
            bodyStyle: "background: none;",
            tbar: toolbar
        });

        this.element.on("afterrender", function (el) {
            var domElement = el.getEl().dom;
            domElement.dndOver = false;
            domElement.reference = this;

            dndZones.push(domElement);
            el.getEl().on("mouseover", function (options, e) {
                this.dndOver = true;
            }.bind(domElement, this.options));
            el.getEl().on("mouseout", function (e) {
                this.dndOver = false;
            }.bind(domElement));

            Ext.get("pimcore_video_" + name).appendTo(el.body);
        }.bind(this));

        this.element.render(id);
        */
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
            cls: "pimcore_droptarget_input",
            enableKeyEvents: true,
            listeners: {
                keyup: function (el) {
                    if(el.getValue().indexOf("you") >= 0 && el.getValue().indexOf("http") >= 0) {
                        this.form.getComponent("type").setValue("youtube");
                    } else if (el.getValue().indexOf("vim") >= 0 && el.getValue().indexOf("http") >= 0) {
                        this.form.getComponent("type").setValue("vimeo");
                    }
                }.bind(this)
            }
        });

        this.poster = new Ext.form.TextField({
            fieldLabel: t('poster_image'),
            value: this.data.poster,
            name: "poster",
            width: 320,
            cls: "pimcore_droptarget_input",
            enableKeyEvents: true,
            listeners: {
                keyup: function (el) {
                    //el.setValue(this.data.poster)
                }.bind(this)
            }
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
        this.poster.on("render", initDD.bind(this));

        this.form = new Ext.FormPanel({
            bodyStyle: "padding:10px;",
            items: [{
                xtype: "combo",
                itemId: "type",
                fieldLabel: t('type'),
                name: 'type',
                triggerAction: 'all',
                editable: true,
                mode: "local",
                store: ["asset","youtube","vimeo"],
                value: this.data.type
            }, {
                xtype: "compositefield",
                items: [this.fieldPath, {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this)
                }]
            }, this.poster],
            buttons: [
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
            height: 170,
            title: t("video"),
            items: [this.form],
            layout: "fit"
        });
        this.window.show();
    },

    onNodeDrop: function (target, dd, e, data) {

        var t = Ext.get(target);

        if(t.getAttribute("name") == "path") {
            if(this.dndAllowedPath(data)){
                this.fieldPath.setValue(data.node.attributes.path);
                this.form.getComponent("type").setValue("asset");
                return true;
            }
        } else if (t.getAttribute("name") == "poster") {
            if(this.dndAllowedPoster(data)){
                this.poster.setValue(data.node.attributes.path);
                return true;
            }
        }

        return false;
    },

    onNodeOver: function(target, dd, e, data) {

        var t = Ext.get(target);
        var check = "dndAllowedPath";
        if (t.getAttribute("name") == "poster") {
            check = "dndAllowedPoster";
        }

        if (this[check](data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    dndAllowedPath: function(data) {

        if (data.node.attributes.elementType == "asset" && data.node.attributes.type == "video") {
            return true;
        }
        return false;
    },

    dndAllowedPoster: function(data) {

        if (data.node.attributes.elementType == "asset" && data.node.attributes.type == "image") {
            return true;
        }
        return false;
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["video"]
            }
        });
    },

    addDataFromSelector: function (item) {
        if (item) {
            this.fieldPath.setValue(item.fullpath);
            return true;
        }
    },

    save: function () {

        // close window
        this.window.hide();

        var values = this.form.getForm().getFieldValues();
        this.data = values;



        this.reloadDocument();
    },

    cancel: function () {
        this.window.hide();
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "video";
    }
});