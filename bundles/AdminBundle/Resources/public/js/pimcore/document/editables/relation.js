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

pimcore.registerNS("pimcore.document.editables.relation");
pimcore.document.editables.relation = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {

        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);

        this.data = {
            id: null,
            path: "",
            type: ""
        };

        if (data) {
            this.data = data;
            this.config.value = this.data.path;
        }

        this.config.enableKeyEvents = true;

        if(typeof this.config.emptyText == "undefined") {
            this.config.emptyText = t("drop_element_here");
        }

        this.config.name = id + "_editable";
    },

    render: function () {
        this.setupWrapper();

        if (!this.config.width) {
            this.config.width = Ext.get(this.id).getWidth() ?? Ext.get(this.id).getWidth() - 2;
        }

        this.element = new Ext.form.TextField(this.config);


        this.element.on("render", function (el) {
            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            el.getEl().on("contextmenu", this.onContextMenu.bind(this));
        }.bind(this));

        // disable typing into the textfield
        this.element.on("keyup", function (element, event) {
            element.setValue(this.data.path);
        }.bind(this));

        var items = [this.element, {
            xtype: "button",
            iconCls: "pimcore_icon_open",
            style: "margin-left: 5px",
            handler: this.openElement.bind(this)
        }, {
            xtype: "button",
            iconCls: "pimcore_icon_delete",
            style: "margin-left: 5px",
            handler: this.empty.bind(this)
        }, {
            xtype: "button",
            iconCls: "pimcore_icon_search",
            style: "margin-left: 5px",
            handler: this.openSearchEditor.bind(this)
        }];

        this.composite = Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            items: items
        });

        this.composite.render(this.id);
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.config["uploadPath"], "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if(data["id"]) {

                    if (this.config["subtypes"]) {
                        var found = false;
                        var typeKeys = Object.keys(this.config.subtypes);
                        for (var st = 0; st < typeKeys.length; st++) {
                            for (var i = 0; i < this.config.subtypes[typeKeys[st]].length; i++) {
                                if (this.config.subtypes[typeKeys[st]][i] == data["type"]) {
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
        if (data.records.length === 1 && this.dndAllowed(record)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

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

        if (this.config.reload) {
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
        if (this.config.only && !this.config.types) {
            this.config.types = [this.config.only];
        }

        //type check   (asset,document,object)
        if (this.config.types) {
            found = false;
            for (i = 0; i < this.config.types.length; i++) {
                type = this.config.types[i];
                if (type == data.data.elementType) {
                    found = true;

                    if((typeof this.config.subtypes !== "undefined") && this.config.subtypes[type] && this.config.subtypes[type].length) {
                        checkSubType = true;
                    }
                    if(data.data.elementType == "object" && this.config.classes) {
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
            var subTypes = this.config.subtypes[type];
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
            for (i = 0; i < this.config.classes.length; i++) {
                if (this.config.classes[i] == data.data.className) {
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
                handler: this.empty.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: this.openElement.bind(this)
            }));

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function (item) {
                        item.parentMenu.destroy();
                        pimcore.treenodelocator.showInTree(this.data.id, this.data.elementType);
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

        if((this.config["types"] && in_array("asset",this.config.types)) || !this.config["types"]) {
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
        if (this.config.only && !this.config.types) {
            this.config.types = [this.config.only];
        }

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: this.config.types,
            subtype: this.config.subtypes,
            specific: {
                classes: this.config["classes"]
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
            if (this.config.reload) {
                this.reloadDocument();
            }
        }
    },

    openElement: function () {
        if (this.data.id && this.data.elementType) {
            pimcore.helpers.openElement(this.data.id, this.data.elementType, this.data.subtype);
        }
    },

    empty: function () {
        this.data = {};
        this.element.setValue(this.data.path);
        if (this.config.reload) {
            this.reloadDocument();
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
        return "relation";
    }
});
