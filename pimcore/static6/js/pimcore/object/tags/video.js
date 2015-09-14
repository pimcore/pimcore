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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.video");
pimcore.object.tags.video = Class.create(pimcore.object.tags.abstract, {

    type: "video",
    dirty: false,

    initialize: function (data, fieldConfig) {
        if (data) {
            this.data = data;
        } else {
            this.data = {};
        }

        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function(field) {

        return {header: ts(field.label), width: 100, sortable: false, dataIndex: field.key,
                    renderer: function (key, value, metaData, record) {
                                    this.applyPermissionStyle(key, value, metaData, record);

                                    if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited
                                                                        == true) {
                                        metaData.tdCls += " grid_value_inherited";
                                    }

                                    if (value) {
                                        return '<img src="/admin/asset/get-video-thumbnail/id/' + value
                                            + '/width/88/height/88/frame/true" />';
                                    }
                                }.bind(this, field.key)};
    },    

    getLayoutEdit: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 300;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 300;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            border: true,
            style: "padding-bottom: 10px",
            tbar: [{
                xtype: "tbtext",
                text: "<b>" + this.fieldConfig.title + "</b>"
            },"->",{
                xtype: "button",
                iconCls: "pimcore_icon_videoedit",
                handler: this.openEdit.bind(this)
            }, {
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                handler: this.empty.bind(this)
            }],
            componentCls: "object_field",
            bodyCls: "pimcore_video_container"
        };

        this.component = new Ext.Panel(conf);


        this.component.on("afterrender", function (el) {
            if (this.data) {
                this.updateVideo();
            }
        }.bind(this));

        return this.component;
    },

    getLayoutShow: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 300;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 300;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            title: this.fieldConfig.title,
            border: true,
            style: "padding-bottom: 10px",
            cls: "object_field",
            bodyCls: "pimcore_video_container"
        };

        this.component = new Ext.Panel(conf);

        this.component.on("afterrender", function (el) {
            if (this.data) {
                this.updateVideo();
            }
        }.bind(this));

        return this.component;
    },

    addDataFromSelector: function (item) {

        this.empty();

        if (item) {
            this.fieldData.setValue(item.fullpath);
            return true;
        }
    },


    openEdit: function () {

        this.fieldData = new Ext.form.TextField({
            value: this.data.data,
            name: "data",
            width: 320,
            fieldCls: "pimcore_droptarget_input",
            enableKeyEvents: true,
            listeners: {
                keyup: function (el) {

                    var tmpId;

                    if(el.getValue().indexOf("youtu") >= 0 && el.getValue().indexOf("//") >= 0) {
                        this.form.getComponent("type").setValue("youtube");

                        // get id
                        /*
                            Possible Links:
                            # //www.youtube.com/embed/Vhf5cuXiLTA
                            # http://www.youtube.com/watch?v=Vhf5cuXiLTA
                            # http://youtu.be/Vhf5cuXiLTA

                         */
                        var path = el.getValue();
                        var parts = parse_url(path);

                        var vars = Ext.urlDecode(parts["query"]);
                        if(vars["v"]) {
                            tmpId = vars["v"];
                        }

                        //get youtube id if form urls like  http://www.youtube.com/embed/youtubeId
                        if(path.indexOf("embed") >= 0){
                            var explodedPath = trim(parts["path"]," /").split("/");
                            var tmpIndex = intval(array_search('embed',explodedPath))+1;
                            tmpId = explodedPath[tmpIndex];
                        }

                        if(parts["host"] == "youtu.be") {
                            tmpId = trim(parts["path"]," /");
                        }

                        if(tmpId) {
                            el.setValue(tmpId);
                        }

                    } else if (el.getValue().indexOf("vime") >= 0 && el.getValue().indexOf("//") >= 0) {
                        this.form.getComponent("type").setValue("vimeo");

                        /*
                            Possible Links
                            # http://vimeo.com/11696823
                            # http://player.vimeo.com/video/22775048?title=0&byline=0&portrait=0
                         */

                        var path = el.getValue();
                        var parts = parse_url(path);

                        var pathParts = trim(parts["path"]," /").split("/");

                        for(var i=0; i<pathParts.length; i++) {
                            if(intval(pathParts[i]) > 0 && pathParts[i].length > 3) {
                                tmpId = pathParts[i];
                                break;
                            }
                        }

                        if(tmpId) {
                            el.setValue(tmpId);
                        }
                    }
                }.bind(this)
            }
        });

        this.poster = new Ext.form.TextField({
            fieldLabel: t('poster_image'),
            value: this.data.poster,
            name: "poster",
            width: 420,
            fieldCls: "pimcore_droptarget_input",
            enableKeyEvents: true,
            listeners: {
                keyup: function (el) {
                    //el.setValue(this.data.poster)
                }.bind(this)
            }
        });


        this.fieldData.on("render", this.initDD.bind(this, "video"));
        this.poster.on("render", this.initDD.bind(this, "image"));

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
                fieldLabel: t('path'),
                xtype: "fieldcontainer",
                layout: 'hbox',
                border: false,
                itemId: "dataContainer",
                items: [this.fieldData, this.searchButton]
            }, this.poster,{
                xtype: "textfield",
                name: "title",
                fieldLabel: t('title'),
                width: 420,
                value: this.data.title
            },{
                xtype: "textarea",
                name: "description",
                fieldLabel: t('description'),
                width: 420,
                height: 50,
                value: this.data.description
            }],
            buttons: [
                {
                    text: t("cancel"),
                    listeners:  {
                        "click": function () {
                            this.window.hide();
                        }.bind(this)
                    }
                },
                {
                    text: t("save"),
                    listeners: {
                        "click": function () {
                            // close window
                            this.window.hide();

                            var values = this.form.getForm().getFieldValues();
                            this.data = values;

                            this.dirty = true;
                            this.updateVideo();
                        }.bind(this)
                    },
                    icon: "/pimcore/static6/img/icon/tick.png"
                }
            ]
        });


        this.window = new Ext.Window({
            width: 500,
            height: 400,
            title: t("video"),
            items: [this.form],
            layout: "fit",
            listeners: {
                afterrender: function () {
                    setTimeout(function() {
                        this.updateType(this.data.type);
                    }.bind(this), 1000);
                }.bind(this)
            }
        });
        this.window.show();
    },

    initDD: function (type, el) {

        // add drop zone
        new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: "element",
            getTargetFromEvent: function(e) {
                return this.reference.component.getEl();
            },

            onNodeOver : function(target, dd, e, data) {
                try {
                    var record = data.records[0];
                    var data = record.data;

                    if (data.type == type) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                } catch (e) {
                    console.log(e);
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;
            },

            onNodeDrop : function (target, dd, e, data) {

                try {
                    var record = data.records[0];
                    var data = record.data;

                    if (data.type == type) {
                        if (this.data.data != data.path) {
                            this.dirty = true;
                        }

                        if (type == "video") {
                            this.empty();
                            this.data.data = data.path;
                            this.fieldData.setValue(data.path);
                            this.form.getComponent("type").setValue("asset");
                        } else if (type == "image") {
                            this.data.poster = data.path;
                            this.poster.setValue(data.path);
                        }

                        this.updateVideo();
                        return true;
                    }
                } catch (e) {
                    console.log(e);
                }
            }.bind(this)
        });
    },

    updateType: function (type) {
        this.searchButton.enable();
        var cmp = this.form.getComponent("dataContainer");
        var textField = cmp.items.getAt(0);
        var labelEl = textField.labelEl;
        labelEl.update(t("path"));

        if(type != "asset") {
            this.searchButton.disable();
        }
        if(type == "youtube") {
            labelEl.update("URL / ID");
        }
        if(type == "vimeo") {
            labelEl.update("URL / ID");
        }
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["video"]
            }
        });
    },

    updateVideo: function () {

        var width = this.getBody().getWidth();
        //need to geht height this way, because element has no hight at afterrender (whyever)
        var height = this.fieldConfig.height - 55; //this.getBody().getHeight();

        var content = '';

        if(this.data.type == "asset" && pimcore.settings.videoconverter) {
            content = '<img src="/admin/asset/get-video-thumbnail/width/'
                + width + "/height/" + height + '/frame/true?' +  Ext.urlEncode({path: this.data.data}) + '" />';
        } else if(this.data.type == "youtube") {
            content = '<iframe width="' + width + '" height="' + height + '" src="//www.youtube.com/embed/' + this.data.data + '" frameborder="0" allowfullscreen></iframe>';
        } else if (this.data.type == "vimeo") {
            content = '<iframe src="//player.vimeo.com/video/' + this.data.data + '?title=0&amp;byline=0&amp;portrait=0" width="' + width + '" height="' + height + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        }

        this.getBody().update(content);
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)
        var result = Ext.get(this.component.getEl().dom).query(".pimcore_video_container");
        var bodyId = result[0].getAttribute("id");
        return Ext.get(bodyId);
    },
    
    empty: function () {
        this.data = {
            type: "asset",
            data: ""
        };

        this.getBody().update("");

        this.dirty = true;
    },
    
    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        if (this.getValue()) {
            return false;
        }
        return true;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }
});