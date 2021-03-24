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

pimcore.registerNS("pimcore.object.tags.manyToOneRelation");
pimcore.object.tags.manyToOneRelation = Class.create(pimcore.object.tags.abstract, {

    type: "manyToOneRelation",
    dataChanged: false,
    dataObjectFolderAllowed: false,

    initialize: function (data, fieldConfig) {

        this.data = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;

        this.fieldConfig.classes =  this.fieldConfig.classes.filter(function (x) {
            if(x.classes == 'folder') {
                this.dataObjectFolderAllowed = true;
                return false;
            }
            return true;
        }.bind(this));
    },


    getGridColumnConfig: function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.tdCls += " grid_value_inherited";
            }

            if (value && value.path) {
                return value.path;

            }
            return value;

        }.bind(this, field.key);

        return {
            text: t(field.label), sortable: false, dataIndex: field.key, renderer: renderer,
            getEditor: this.getWindowCellEditor.bind(this, field)
        };
    },


    getLayoutEdit: function () {

        var href = {
            name: this.fieldConfig.name
        };

        var labelWidth = this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100;

        if (this.data) {
            if (this.data.path) {
                href.value = this.data.path;
            }
        }

        if (this.fieldConfig.width) {
            href.width = this.fieldConfig.width;
        } else {
            href.width = 300;
        }

        href.enableKeyEvents = true;
        href.fieldCls = "pimcore_droptarget_input";
        this.component = new Ext.form.TextField(href);
        if (this.data.published === false) {
            this.component.addCls("strikeThrough");
        }
        this.component.on("render", function (el) {
            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function (e) {
                    return this.reference.component.getEl();
                },

                onNodeOver: function (target, dd, e, data) {
                    if (data.records.length === 1 && this.dndAllowed(data.records[0].data)) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                }.bind(this),

                onNodeDrop: this.onNodeDrop.bind(this)
            });


            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            el.getEl().on('dblclick', function(){
                var subtype = this.data.subtype;
                if (this.data.type === "object" && this.data.subtype !== "folder" && this.data.subtype !== null) {
                    subtype = "object";
                }

                pimcore.helpers.openElement(this.data.id, this.data.type, subtype);
            }.bind(this));
        }.bind(this));

        // disable typing into the textfield
        this.component.on("keyup", function (element, event) {
            element.setValue(this.data.path);
        }.bind(this));

        var items = [this.component, {
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

        // add upload button when assets are allowed
        if (this.fieldConfig.assetsAllowed) {
            items.push({
                xtype: "button",
                iconCls: "pimcore_icon_upload",
                cls: "pimcore_inline_upload",
                style: "margin-left: 5px",
                handler: this.uploadDialog.bind(this)
            });
        }


        this.composite = Ext.create('Ext.form.FieldContainer', {
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            layout: 'hbox',
            items: items,
            componentCls: "object_field object_field_type_" + this.type,
            border: false,
            style: {
                padding: 0
            },
            listeners: {
                afterrender: function () {
                    this.requestNicePathData();
                }.bind(this)
            }
        });

        return this.composite;
    },


    getLayoutShow: function () {

        var href = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            labelWidth: this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100
        };

        if (this.data) {
            if (this.data.path) {
                href.value = this.data.path;
            }
        }

        if (this.fieldConfig.width) {
            href.width = this.fieldConfig.width;
        } else {
            href.width = 300;
        }
        href.width = href.labelWidth + href.width;
        href.disabled = true;

        this.component = new Ext.form.TextField(href);

        if (this.data.published === false) {
            this.component.addCls("strikeThrough");
        }

        this.composite = Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            items: [this.component, {
                xtype: "button",
                iconCls: "pimcore_icon_open",
                handler: this.openElement.bind(this)
            }],
            componentCls: "object_field object_field_type_" + this.type,
            border: false,
            style: {
                padding: 0
            },
            listeners: {
                afterrender: function () {
                    this.requestNicePathData();
                }.bind(this)
            }
        });

        return this.composite;

    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.fieldConfig.assetUploadPath, "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if (data["id"]) {
                    this.data.id = data["id"];
                    this.data.type = "asset";
                    this.data.subtype = data["type"];
                    this.data.path = data["fullpath"];
                    this.dataChanged = true;
                    this.component.setValue(data["fullpath"]);
                    this.requestNicePathData();
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this), null, this.context);
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        data = data.records[0].data;

        if (this.dndAllowed(data)) {
            this.data.id = data.id;
            this.data.type = data.elementType;
            this.data.subtype = data.type;
            this.data.path = data.path;
            this.dataChanged = true;

            this.component.removeCls("strikeThrough");
            if (data.published === false) {
                this.component.addCls("strikeThrough");
            }
            this.component.setValue(data.path);
            this.requestNicePathData();

            return true;
        } else {
            return false;
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                item.parentMenu.destroy();

                this.empty();
            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openElement();
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

        // add upload button when assets are allowed
        if (this.fieldConfig.assetsAllowed) {
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
        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};
        var i;

        if (this.fieldConfig.objectsAllowed) {
            allowedTypes.push("object");
            allowedSubtypes.object = [];
            if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object.push("object", "variant");
                for (i = 0; i < this.fieldConfig.classes.length; i++) {
                    allowedSpecific.classes.push(this.fieldConfig.classes[i].classes);

                }
            }
            if(this.dataObjectFolderAllowed) {
                allowedSubtypes.object.push("folder");
            }

            if(allowedSubtypes.length == 0) {
                allowedSubtypes.object = ["object", "folder", "variant"];
            }
        }
        if (this.fieldConfig.assetsAllowed) {
            allowedTypes.push("asset");
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(this.fieldConfig.assetTypes[i].assetTypes);
                }
            }
        }
        if (this.fieldConfig.documentsAllowed) {
            allowedTypes.push("document");
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    allowedSubtypes.document.push(this.fieldConfig.documentTypes[i].documentTypes);
                }
            }
        }

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: allowedTypes,
            subtype: allowedSubtypes,
            specific: allowedSpecific
        }, {
            context: Ext.apply({scope: "objectEditor"}, this.getContext())
        });
    },

    addDataFromSelector: function (data) {
        this.data.id = data.id;
        this.data.type = data.type;
        this.data.subtype = data.subtype;
        this.data.path = data.fullpath;
        this.dataChanged = true;
        this.component.removeCls("strikeThrough");
        if (data.published === false) {
            this.component.addCls("strikeThrough");
        }
        this.component.setValue(data.fullpath);
        this.requestNicePathData();
    },

    openElement: function () {
        if (this.data.id && this.data.type) {
            pimcore.helpers.openElement(this.data.id, this.data.type, this.data.subtype);
        }
    },

    empty: function () {
        this.data = {};
        this.dataChanged = true;
        this.component.setValue("");
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    dndAllowed: function (data) {

        var elementType = data.elementType;
        var i;
        var subType;
        var isAllowed = false;
        if (elementType == "object" && this.fieldConfig.objectsAllowed) {

            if(data.type == 'folder') {
                if(this.dataObjectFolderAllowed || this.fieldConfig.classes.length <= 0) {
                    isAllowed = true;
                }
            } else {
                var classname = data.className;

                isAllowed = false;
                if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                    for (i = 0; i < this.fieldConfig.classes.length; i++) {
                        if (this.fieldConfig.classes[i].classes == classname) {
                            isAllowed = true;
                            break;
                        }
                    }
                } else {
                    if(!this.dataObjectFolderAllowed) {
                        isAllowed = true;
                    }
                }
            }
        } else if (elementType == "asset" && this.fieldConfig.assetsAllowed) {
            subType = data.type;
            isAllowed = false;
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    if (this.fieldConfig.assetTypes[i].assetTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no asset types configured - allow all
                isAllowed = true;
            }

        } else if (elementType == "document" && this.fieldConfig.documentsAllowed) {
            subType = data.type;
            isAllowed = false;
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    if (this.fieldConfig.documentTypes[i].documentTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no document types configured - allow all
                isAllowed = true;
            }
        }
        return isAllowed;
    },

    requestNicePathData: function () {
        if (this.data.id) {
            var targets = new Ext.util.Collection();
            var target = Ext.clone(this.data)
            target.nicePathKey = target.type + "_" + target.id;
            var targetRecord = {
                id: 0,
                data: target
            };
            targets.add(targetRecord);

            pimcore.helpers.requestNicePathData(
                {
                    type: "object",
                    id: this.object.id
                },
                targets,
                {
                    idProperty: "nicePathKey"
                },
                this.fieldConfig,
                this.getContext(),
                function () {
                    this.component.addCls("grid_nicepath_requested");
                }.bind(this),
                function (target, responseData) {
                    this.component.removeCls("grid_nicepath_requested");

                    if (typeof responseData[target["nicePathKey"]] !== "undefined") {
                        this.component.setValue(responseData[target["nicePathKey"]]);
                    }

                }.bind(this, target)
            );
        }
    },

    getCellEditValue: function () {
        return this.getValue();
    },

    isDirty:function () {
        if (this.component) {
            if (!this.component.rendered) {
                return false;
            } else {
                return this.dataChanged;
            }
        }
    }
});

// @TODO BC layer, to be removed in v7.0
pimcore.object.tags.href = pimcore.object.tags.manyToOneRelation;
