/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.tags.href");
pimcore.document.tags.href = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.options = this.parseOptions(options);

        this.data = {
            id: null,
            path: "",
            type: ""
        };

        if (!this.options.width) {
            this.options.width = Ext.get(id).getWidth() - 2;
        }

        if (data) {
            this.data = data;
            this.options.value = this.data.path;
        }

        this.setupWrapper();

        this.options.enableKeyEvents = true;

        if(typeof this.options.emptyText == "undefined") {
            this.options.emptyText = t("drop_element_here");
        }

        this.options.name = id + "_editable";
        this.element = new Ext.form.TextField(this.options);


        this.element.on("render", function (el) {
            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

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
                            for (var i = 0; i < this.options.subtypes[typeKeys[st]].length; i++) {
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
        var record = data.records[0];

        record = this.getCustomPimcoreDropData(record);
        if (this.dndAllowed(record)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];

        record = this.getCustomPimcoreDropData(record);

        if(!this.dndAllowed(record)){
            return false;
        }


        this.data.id = record.data.id;
        this.data.subtype = record.data.type;
        this.data.elementType = record.data.elementType;
        this.data.path = record.data.path;

        this.element.setValue(record.data.path);

        if (this.options.reload) {
            this.reloadDocument();
        }

        return true;
    },

    dndAllowed: function(data) {

        var i;
        var found;

        var checkSubType = false;
        var checkClass = false;
        var type;

        //only is legacy
        if (this.options.only && !this.options.types) {
            this.options.types = [this.options.only];
        }

        //type check   (asset,document,object)
        if (this.options.types) {
            found = false;
            for (i = 0; i < this.options.types.length; i++) {
                type = this.options.types[i];
                if (type == data.data.elementType) {
                    found = true;

                    if(this.options.subtypes[type] && this.options.subtypes[type].length) {
                        checkSubType = true;
                    }
                    if(data.data.elementType == "object" && this.options.classes) {
                        checkClass = true;
                    }
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }

        //subtype check  (folder,page,snippet ... )
        if (checkSubType) {

            found = false;
            var subTypes = this.options.subtypes[type];
            for (i = 0; i < subTypes.length; i++) {
                if (subTypes[i] == data.data.type) {
                    found = true;
                    break;
                }

            }
            if (!found) {
                return false;
            }
        }

        //object class check
        if (checkClass) {
            found = false;
            for (i = 0; i < this.options.classes.length; i++) {
                if (this.options.classes[i] == data.data.className) {
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

        if(this.data["id"]) {
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

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function (item) {
                        item.parentMenu.destroy();
                        pimcore.treenodelocator.showInTree(this.data.id, "document");
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

        if((this.options["types"] && in_array("asset",this.options.types)) || !this.options["types"]) {
            menu.add(new Ext.menu.Item({
                text: t('upload'),
                cls: "pimcore_inline_upload",
                iconCls: "pimcore_icon_upload",
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
            subtype: this.options.subtypes,
            specific: {
                classes: this.options["classes"]
            }
        }, {
            context: this.getContext()
        });
    },

    addDataFromSelector: function (item) {
        if (item) {
            this.data.id = item.id;
            this.data.subtype = item.subtype;
            this.data.elementType = item.type;
            this.data.path = item.fullpath;

            this.element.setValue(this.data.path);
            if (this.options.reload) {
                this.reloadDocument();
            }
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
