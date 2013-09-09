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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.document.tags.video");
pimcore.document.tags.video = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.data = {};

        this.options = this.parseOptions(options);
        this.data = data;

        this.setupWrapper();

        var element = Ext.get("pimcore_video_" + name);
        element.insertHtml("afterBegin", '<div class="pimcore_video_edit_button"></div>');

        var button = new Ext.Button({
            iconCls: "pimcore_icon_edit_video",
            cls: "pimcore_edit_link_button",
            handler: this.openEditor.bind(this)
        });
        button.render(Ext.get(Ext.query(".pimcore_video_edit_button", element.dom)[0]));
    },

    openEditor: function () {

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
            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
        };

        this.fieldPath.on("render", initDD.bind(this));
        this.poster.on("render", initDD.bind(this));

        this.searchButton = new Ext.Button({
            iconCls: "pimcore_icon_search",
            handler: this.openSearchEditor.bind(this)
        });

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
                value: this.data.type,
                listeners: {
                    select: function (combo) {
                        var type = combo.getValue();
                        this.updateType(type);
                    }.bind(this)
                }
            }, {
                xtype: "compositefield",
                itemId: "pathContainer",
                items: [this.fieldPath, this.searchButton]
            }, this.poster,{
                xtype: "textfield",
                name: "title",
                fieldLabel: t('title'),
                width: 320,
                value: this.data.title
            },{
                xtype: "textarea",
                name: "description",
                fieldLabel: t('description'),
                width: 320,
                height: 50,
                value: this.data.description
            }],
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
            height: 250,
            title: t("video"),
            items: [this.form],
            layout: "fit",
            listeners: {
                afterrender: function () {
                    this.updateType(this.data.type);
                }.bind(this)
            }
        });
        this.window.show();
    },

    updateType: function (type) {
        this.searchButton.enable();
        var labelEl = this.form.getComponent("pathContainer").label;
        labelEl.update(t("path"));

        if(type != "asset") {
            this.searchButton.disable();
        }
        if(type == "youtube") {
            labelEl.update("URL / ID");
        }
        if(type == "vimeo") {
            labelEl.update("URL");
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if(target) {
            if(target.getAttribute("name") == "path") {
                if(this.dndAllowedPath(data)){
                    this.fieldPath.setValue(data.node.attributes.path);
                    this.form.getComponent("type").setValue("asset");
                    return true;
                }
            } else if (target.getAttribute("name") == "poster") {
                if(this.dndAllowedPoster(data)){
                    this.poster.setValue(data.node.attributes.path);
                    return true;
                }
            }
        }

        return false;
    },

    onNodeOver: function(target, dd, e, data) {

        var check = "dndAllowedPath";
        if (target && target.getAttribute("name") == "poster") {
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