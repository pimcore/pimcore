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

pimcore.registerNS("pimcore.document.tags.renderlet");
pimcore.document.tags.renderlet = Class.create(pimcore.document.tag, {

    defaultHeight: 100,

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.options = this.parseOptions(options);


        //TODO maybe there is a nicer way, the Panel doesn't like this
        this.controller = options.controller;
        delete(options.controller);

        this.data = {};

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

            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            this.getBody().setStyle({
                overflow: "auto"
            });

            this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
            this.getBody().addCls("pimcore_tag_snippet_empty");

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

        var record = data.records[0];
        data = record.data;

        // get path from nodes data
        this.data.id = data.id;
        this.data.type = data.elementType;
        this.data.subtype = data.type;

        if (this.options.type) {
            if (this.options.type != data.elementType) {
                return false;
            }
        }

        if (this.options.className) {
            if (this.options.className != data.className) {
                return false;
            }
        }

        if (this.options.reload) {
            this.reloadDocument();
        } else {
            this.updateContent();
        }

        return true;
    },

    onNodeOver: function(target, dd, e, data) {
        data = data.records[0].data;
        if (this.options.type) {
            if (this.options.type != data.elementType) {
                return false;
            }
        }

        if (this.options.className) {
            if (this.options.className != data.className) {
                return false;
            }
        }

        return Ext.dd.DropZone.prototype.dropAllowed;
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)
        var bodyId = this.element.getEl().query("." + Ext.baseCSSPrefix + "panel-body")[0].getAttribute("id");
        return Ext.get(bodyId);
    },

    updateContent: function (path) {

        this.getBody().removeCls("pimcore_tag_snippet_empty");
        this.getBody().dom.innerHTML = '<br />&nbsp;&nbsp;Loading ...';

        var params = this.data;
        params.controller = this.controller;
        Ext.apply(params, this.options);

        try {
            // add the id of the current document, so that the renderlet knows in which document it is embedded
            // this information is then grabbed in Pimcore_Controller_Action_Frontend::init() to set the correct locale
            params["pimcore_parentDocument"] = window.editWindow.document.id;
        } catch (e) {

        }

        Ext.Ajax.request({
            method: "get",
            url: "/pimcore_document_tag_renderlet",
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

        if(this.data["id"]) {
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
                    this.getBody().addCls("pimcore_tag_snippet_empty");
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

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function (item) {
                        item.parentMenu.destroy();
                        pimcore.treenodelocator.showInTree(this.data.id, this.data.type);
                    }.bind(this)
                }));
            }
        }

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
        var restrictions = {};

        if (this.options.type) {
            restrictions.type = [this.options.type];
        }
        if (this.options.className) {
            restrictions.specific = {
                classes: [this.options.className]
            };
        }

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), restrictions, {
            context: this.getContext()
        });
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
