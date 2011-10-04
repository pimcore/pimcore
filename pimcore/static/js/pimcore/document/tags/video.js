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

    initialize: function(id, name, options, data) {
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

        var toolbar = [];
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
            html: Ext.get("pimcore_video_" + name).dom.innerHTML,
            tbar: toolbar
        });

        this.element.on("render", function (el) {
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

        }.bind(this));

        Ext.get("pimcore_video_" + name).dom.innerHTML = "";

        this.element.render(id);
    },

    onNodeDrop: function (target, dd, e, data) {

        if (this.dndAllowed(data)) {
            this.data.id = data.node.attributes.id;
            this.data.type = "asset";

            window.setTimeout(this.reloadDocument.bind(this), 200);
            return true;
        }
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

        if(data.node.attributes.elementType!="asset" || data.node.attributes.type!="video"){
            return false;
        } else return true;

    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "video";
    }
});