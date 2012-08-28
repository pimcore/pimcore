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

pimcore.registerNS("pimcore.document.tags.renderlet");
pimcore.document.tags.renderlet = Class.create(pimcore.document.tag, {

    defaultHeight: 100,

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
            if (this.data.id) {
                this.updateContent();
            }
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        // get path from nodes data
        this.data.id = data.node.attributes.id;
        this.data.type = data.node.attributes.elementType;
        this.data.subtype = data.node.attributes.type;

        if (this.options.reload) {
            this.reloadDocument();
        } else {
            this.updateContent();
        }

        return true;
    },

    onNodeOver: function(target, dd, e, data) {

        return Ext.dd.DropZone.prototype.dropAllowed;

    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html (only in configure)
        var bodyId = this.element.getEl().query(".x-panel-body")[0].getAttribute("id");
        return Ext.get(bodyId);
    },

    updateContent: function (path) {

        this.getBody().removeClass("pimcore_tag_snippet_empty");
        this.getBody().dom.innerHTML = '<br />&nbsp;&nbsp;Loading ...';

        var params = this.data;
        Ext.apply(params, this.options);

        Ext.Ajax.request({
            url: "/pimcore_document_tag_renderlet",
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
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function () {
                var height = this.options.height;
                if (!height) {
                    height = this.defaultHeight;
                }
                this.data = {};
                this.getBody().update('');
                this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
                this.getBody().addClass("pimcore_tag_snippet_empty");
                this.getBody().setHeight(height + "px");

                if (this.options.reload) {
                    this.reloadDocument();
                }

            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function () {
                if(this.data.id) {
                    pimcore.helpers.openElement(this.data.id, this.data.type, this.data.subtype);
                }
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
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {});
    },
    
    addDataFromSelector: function (item) {        
        if(item) {
            // get path from nodes data
            this.data.id = item.id;
            this.data.type = item.type;
            this.data.subtype = item.subtype;

            if (this.options.reload) {
                this.reloadDocument();
            } else {
                this.updateContent();
            }
        }
    },
    
    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "renderlet";
    }
});