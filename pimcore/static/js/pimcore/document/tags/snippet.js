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

pimcore.registerNS("pimcore.document.tags.snippet");
pimcore.document.tags.snippet = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.options = options;

        this.data = {};

        if (!this.options) {
            this.options = {};
        }
        if (!data) {
            data = {};
        }

        // height management                
        this.defaultHeight = 100;
        if (this.options.defaultHeight) {
            this.defaultHeight = this.options.defaultHeight;
        }
        if (!this.options.height && !data.path) {
            this.options.height = this.defaultHeight;
        }

        this.setupWrapper();

        this.options.name = id + "_editable";
        this.options.border = false;
        this.options.bodyStyle = "min-height: 40px;";

        this.element = new Ext.Panel(this.options);

        this.element.on("render", function (el) {
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

            this.getBody().setStyle({
                overflow: "auto"
            });

            this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
            this.getBody().addClass("pimcore_tag_snippet_empty");

            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

        }.bind(this));

        this.element.render(id);


        // insert snippet content
        if (data) {
            this.data = data;
            if (this.data.path) {
                this.updateContent(this.data.path);
            }
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if (this.dndAllowed(data)) {
            // get path from nodes data
            var uri = data.node.attributes.path;

            this.data.id = data.node.attributes.id;
            this.data.path = uri;

            if (this.options.reload) {
                this.reloadDocument();
            } else {
                this.updateContent(uri);
            }

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

        if (data.node.attributes.type != "snippet") {
            return false;
        } else return true;

    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html (only in configure)
        var bodyId = Ext.get(this.element.getEl().dom).query(".x-panel-body")[0].getAttribute("id");
        return Ext.get(bodyId);
    },

    updateContent: function (path) {

        var params = this.options;
        params.blockAutoHtml = true;
        params.pimcore_admin = true;

        Ext.Ajax.request({
            url: path,
            method: "get",
            success: function (response) {
                this.getBody().dom.innerHTML = response.responseText;
                this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
                this.updateDimensions();
            }.bind(this),
            params: params
        });
    },

    updateDimensions: function () {
        this.getBody().setStyle({
            height: "auto"
        });

        this.getBody().removeClass("pimcore_tag_snippet_empty");
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                item.parentMenu.destroy();

                var height = this.options.height;
                if (!height) {
                    height = this.defaultHeight;
                }

                this.element.setHeight(height);

                this.data = {};
                this.getBody().dom.innerHTML = '';
                this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
                this.getBody().addClass("pimcore_tag_snippet_empty");
                this.getBody().setStyle(height + "px");

                if (this.options.reload) {
                    this.reloadDocument();
                }

            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (item) {
                item.parentMenu.destroy();

                pimcore.helpers.openDocument(this.data.id, "snippet");

            }.bind(this)
        }));
        
        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                
                this.openSearchEditor();
            }.bind(this)
        }));


        menu.showAt(e.getXY());

        e.stopEvent();
    },
    
    openSearchEditor: function () {

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["document"],
            subtype: {
                document: ["snippet"]
            }
        });
    },
    
    addDataFromSelector: function (item) {
        
        if(item) {
            var uri = item.fullpath;
    
            this.data.id = item.id;
            this.data.path = uri;

            if (this.options.reload) {
                this.reloadDocument();
            } else {
                this.updateContent(uri);
            }
        }
    },

    getValue: function () {
        return this.data.id;
    },

    getType: function () {
        return "snippet";
    }
});