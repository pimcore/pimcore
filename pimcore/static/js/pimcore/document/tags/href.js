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

pimcore.registerNS("pimcore.document.tags.href");
pimcore.document.tags.href = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;


        this.data = {
            id: null,
            path: "",
            type: ""
        };

        if (!options) {
            options = {};
        }

        if (!options.width) {
            options.width = Ext.get(id).getWidth() - 2;
        }


        if (data) {
            this.data = data;
            options.value = this.data.path;
        }

        this.options = options;

        this.setupWrapper();

        options.enableKeyEvents = true;

        if(typeof options.emptyText == "undefined") {
            options.emptyText = t("drop_element_here");
        }

        options.name = id + "_editable";
        this.element = new Ext.form.TextField(options);


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

            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

        }.bind(this));

        // disable typing into the textfield
        this.element.on("keyup", function (element, event) {
            element.setValue(this.data.path);
        }.bind(this));

        this.element.render(id);
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.options["uploadPath"], "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if(data["id"]) {

                    if (this.options["subtypes"]) {
                        var found = false;
                        var typeKeys = Object.keys(this.options.subtypes);
                        for (var st = 0; st < typeKeys.length; st++) {
                            for (i = 0; i < this.options.subtypes[typeKeys[st]].length; i++) {
                                if (this.options.subtypes[typeKeys[st]][i] == data["type"]) {
                                    found = true;
                                    break;
                                }
                            }
                        }
                        if (!found) {
                            return false;
                        }
                    }

                    this.data.id = data["id"];
                    this.data.subtype = data["type"];
                    this.data.elementType = "asset";
                    this.data.path = data["fullpath"];
                    this.element.setValue(data["fullpath"]);
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    onNodeOver: function(target, dd, e, data) {
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!this.dndAllowed(data)){
            return false;
        }


        this.data.id = data.node.attributes.id;
        this.data.subtype = data.node.attributes.type;
        this.data.elementType = data.node.attributes.elementType;
        this.data.path = data.node.attributes.path;

        this.element.setValue(this.data.path);

        if (this.options.reload) {
            this.reloadDocument();
        }

        return true;
    },

    dndAllowed: function(data) {

        var i;

        //only is legacy
        if (this.options.only && !this.options.types) {
            this.options.types = [this.options.only];
        }

        //type check   (asset,document,object)
        if (this.options.types) {
            var found = false;
            for (i = 0; i < this.options.types.length; i++) {
                if (this.options.types[i] == data.node.attributes.elementType) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }

        //subtype check  (folder,page,snippet ... )
        if (this.options.subtypes) {
            var found = false;
            var typeKeys = Object.keys(this.options.subtypes);
            for (var st = 0; st < typeKeys.length; st++) {
                for (i = 0; i < this.options.subtypes[typeKeys[st]].length; i++) {
                    if (this.options.subtypes[typeKeys[st]][i] == data.node.attributes.type) {
                        found = true;
                        break;
                    }
                }
            }
            if (!found) {
                return false;
            }
        }

        //object class check
        if (data.node.attributes.elementType == "object" && this.options.classes) {
            var found = false;
            for (i = 0; i < this.options.classes.length; i++) {
                if (this.options.classes[i] == data.node.attributes.className) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }

        return true;
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                item.parentMenu.destroy();
                this.data = {};
                this.element.setValue(this.data.path);
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
                if (this.data.elementType == "document") {
                    pimcore.helpers.openDocument(this.data.id, this.data.subtype);
                }
                else if (this.data.elementType == "asset") {
                    pimcore.helpers.openAsset(this.data.id, this.data.subtype);
                }
                else if (this.data.elementType == "object") {
                    pimcore.helpers.openObject(this.data.id, this.data.subtype);
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

        if((this.options["types"] && in_array("asset",this.options.types)) || !this.options["types"]) {
            menu.add(new Ext.menu.Item({
                text: t('upload'),
                iconCls: "pimcore_icon_upload_single",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.uploadDialog();
                }.bind(this)
            }));
        }

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    openSearchEditor: function () {

        //only is legacy
        if (this.options.only && !this.options.types) {
            this.options.types = [this.options.only];
        }

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: this.options.types,
            subtype: this.options.subtypes
        });
    },

    addDataFromSelector: function (item) {
        if (item) {
            this.data.id = item.id;
            this.data.subtype = item.subtype;
            this.data.elementType = item.type;
            this.data.path = item.fullpath;

            this.element.setValue(this.data.path);
        }
    },

    getValue: function () {
        return {
            id: this.data.id,
            type: this.data.elementType,
            subtype: this.data.subtype
        };
    },

    getType: function () {
        return "href";
    }
});