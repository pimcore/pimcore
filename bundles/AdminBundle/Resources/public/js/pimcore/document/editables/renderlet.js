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

pimcore.registerNS("pimcore.document.editables.renderlet");
pimcore.document.editables.renderlet = Class.create(pimcore.document.editable, {

    defaultHeight: 100,

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);


        //TODO maybe there is a nicer way, the Panel doesn't like this
        this.controller = config.controller;
        delete(config.controller);

        this.data = {};
        if (data) {
            this.data = data;
        }

        // height management
        this.defaultHeight = 100;
        if (this.config.defaultHeight) {
            this.defaultHeight = this.config.defaultHeight;
        }
        if (!this.config.height) {
            this.config.height = this.defaultHeight;
        }

        this.config.name = id + "_editable";
        this.config.border = false;
        this.config.bodyStyle = "min-height: 40px;";
    },

    render: function() {
        this.setupWrapper();

        this.element = new Ext.Panel(this.config);
        this.element.on("render", function (el) {

            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            this.getBody().setStyle({
                overflow: "auto"
            });

            this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget pimcore_editable_droptarget"></div>');
            this.getBody().addCls("pimcore_tag_snippet_empty pimcore_editable_snippet_empty");

            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

        }.bind(this));

        this.element.render(this.id);

        if (this.data.id) {
            this.updateContent();
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        var record = data.records[0];
        data = record.data;

        this.data.id = data.id;
        this.data.type = data.elementType;
        this.data.subtype = data.type;

        if (this.config.type) {
            if (this.config.type != data.elementType) {
                return false;
            }
        }

        if (this.config.className) {
            if(Array.isArray(this.config.className)) {
                if (this.config.className.indexOf(data.className) < 0) {
                    return false;
                }
            } else {
                if (this.config.className != data.className) {
                    return false;
                }
            }
        }

        if (this.config.reload) {
            this.reloadDocument();
        } else {
            this.updateContent();
        }

        return true;
    },

    onNodeOver: function(target, dd, e, data) {
        if (data.records.length !== 1) {
            return false;
        }

        data = data.records[0].data;
        if (this.config.type) {
            if (this.config.type != data.elementType) {
                return false;
            }
        }

        if (this.config.className) {
            if(Array.isArray(this.config.className)) {
                if (this.config.className.indexOf(data.className) < 0) {
                    return false;
                }
            } else {
                if (this.config.className != data.className) {
                    return false;
                }
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

    updateContent: function () {
        var self = this;

        this.getBody().removeCls("pimcore_tag_snippet_empty pimcore_editable_snippet_empty");
        this.getBody().dom.innerHTML = '<br />&nbsp;&nbsp;Loading ...';

        var params = this.data;
        params.controller = this.controller;
        Ext.apply(params, this.config);

        try {
            // add the id of the current document, so that the renderlet knows in which document it is embedded
            // this information is then grabbed in Pimcore_Controller_Action_Frontend::init() to set the correct locale
            params["pimcore_parentDocument"] = window.editWindow.document.id;
        } catch (e) {
        }

        if ('undefined' !== typeof window.editWindow.targetGroup && window.editWindow.targetGroup.getValue()) {
            params['_ptg'] = window.editWindow.targetGroup.getValue();
        }

        var setContent = function(content) {
            self.getBody().dom.innerHTML = content;
            self.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget pimcore_editable_droptarget"></div>');
            self.updateDimensions();
        };

        Ext.Ajax.request({
            method: "get",
            url: Routing.generate('pimcore_admin_document_renderlet_renderlet'),
            success: function (response) {
                setContent(response.responseText);
            }.bind(this),

            failure: function(response) {
                var message = response.responseText;

                try {
                    var json = Ext.decode(response.responseText);
                    if (json && 'undefined' !== typeof json.message) {
                        message = '<strong style="color:red">' + json.message + '</strong>';
                    }
                } catch (e) {
                    // noop - fall back to responseText
                }

                message = '<br />&nbsp;&nbsp;' + message;

                setContent(message);
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
                    var height = this.config.height;
                    if (!height) {
                        height = this.defaultHeight;
                    }
                    this.data = {};
                    this.getBody().update('');
                    this.getBody().insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget pimcore_editable_droptarget"></div>');
                    this.getBody().addCls("pimcore_tag_snippet_empty pimcore_editable_snippet_empty");
                    this.getBody().setHeight(height + "px");

                    if (this.config.reload) {
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

        if (this.config.type) {
            restrictions.type = [this.config.type];
        }
        if (this.config.className) {
            restrictions.specific = {
                classes: [this.config.className]
            };
        }

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), restrictions, {
            context: this.getContext()
        });
    },

    addDataFromSelector: function (item) {
        if(item) {
            this.data.id = item.id;
            this.data.type = item.type;
            this.data.subtype = item.subtype;

            if (this.config.reload) {
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
