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

pimcore.registerNS("pimcore.object.tags.reverseManyToManyObjectRelation");
pimcore.object.tags.reverseManyToManyObjectRelation = Class.create(pimcore.object.tags.manyToManyObjectRelation, {

    pathProperty: "path",

    initialize: function (data, fieldConfig) {
        this.data = [];
        this.fieldConfig = fieldConfig;
        if (data) {
            this.data = data;
        }

        var fields = [
            "id",
            "path",
            "maintype",
            "classname",
            "published"
        ];

        this.store = new Ext.data.JsonStore({
            data: this.data,
            listeners: {
                add: function () {
                    this.dataChanged = true;
                }.bind(this),
                remove: function () {
                    this.dataChanged = true;
                }.bind(this),
                clear: function () {
                    this.dataChanged = true;
                }.bind(this)
            },
            fields: fields
        });
    },

    removeObject: function (index) {

        if (pimcore.globalmanager.exists("object_" + this.getStore().getAt(index).data.id) == false) {

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobject_get'),
                async: false,
                params: {id: this.getStore().getAt(index).data.id},
                success: function(index, response) {
                    this.data = Ext.decode(response.responseText);
                    if (this.data.editlock) {
                        var lockDate = new Date(this.data.editlock.date * 1000);
                        var lockDetails = "<br /><br />";
                        lockDetails += "<b>" + t("user") + ":</b> " + this.data.editlock.user.name + "<br />";
                        lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate);
                        lockDetails += "<br /><br />" + t("element_implicit_edit_question");

                        Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails,
                                function (lock, buttonValue) {
                                    if (buttonValue == "yes") {
                                        this.getStore().removeAt(index);
                                    }
                                }.bind(this, arguments));

                    } else {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_element_lockelement'),
                            method: 'PUT',
                            params: {
                                id: this.getStore().getAt(index).data.id,
                                type: 'object'
                            }
                        });

                        this.getStore().removeAt(index);
                    }

                }.bind(this, index)
            });
        } else {

            var lockDetails = "<br /><br />" + t("element_implicit_edit_question");

            Ext.MessageBox.confirm(' ', t("element_open_message") + lockDetails,
                function (lock, buttonValue) {
                    if (buttonValue == "yes") {
                        this.getStore().removeAt(index);
                    }
                }.bind(this, arguments)
            );
        }

    },

    actionColumnRemove: function (grid, rowIndex) {
        var f = this.removeObject.bind(grid, rowIndex);
        f();
    },

    getLayoutEdit: function () {

        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field object_field_type_' + this.type;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.findExact('text', this.fieldConfig.ownerClassName));

        // no class for nonowner is specified
        if(!record) {
            this.component = new Ext.Panel({
                title: t(this.fieldConfig.title),
                cls: cls,
                html: "There's no class specified in the field-configuration"
            });

            return this.component;
        }


        var className = record.data.text;


        this.component = new Ext.grid.GridPanel({
            store: this.store,
            border: true,
            style: "margin-bottom: 10px",
            selModel: Ext.create('Ext.selection.RowModel', {}),
            columns: {
                defaults: {
                    sortable: false
                },
                items: [
                    {text: 'ID', dataIndex: 'id', flex: 50},
                    {text: t("reference"), dataIndex: 'path', flex: 200, renderer:this.fullPathRenderCheck.bind(this)
                    },
                    {text: t("class"), dataIndex: 'classname', flex: 100},
                    {
                        xtype: 'actioncolumn',
                        menuText: t('open'),
                        width: 30,
                        items: [
                            {
                                tooltip: t('open'),
                                icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                                handler: function (el, rowIndex) {
                                    var data = this.store.getAt(rowIndex);
                                    pimcore.helpers.openObject(data.data.id, "object");
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('remove'),
                        width: 30,
                        items: [
                            {
                                tooltip: t('remove'),
                                icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                                handler: this.actionColumnRemove.bind(this)
                            }
                        ]
                    }
                ]
            },
            componentCls: cls,
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: {
                items: this.getEditToolbarItems(),
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            bbar: {
                items: [{
                    xtype: "tbtext",
                    text: ' <span class="warning">' + t('nonownerobject_warning') + " | " + t('owner_class')
                                    + ':<b>' + t(className) + "</b> " + t('owner_field') + ': <b>'
                                    + t(this.fieldConfig.ownerFieldName) + '</b></span>'
                }],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_objects",
            viewConfig: {
                markDirty: false,
                listeners: {
                    afterrender: function (gridview) {
                        this.requestNicePathData(this.store.data);
                    }.bind(this)
                }
            },
            listeners: {
                rowdblclick: this.gridRowDblClickHandler
            }
        });

        this.component.on("rowcontextmenu", this.onRowContextmenu);
        this.component.reference = this;

        this.component.on("afterrender", function () {

            var dropTargetEl = this.component.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'element',
                getTargetFromEvent: function(e) {
                    return this.component.getEl().dom;
                    //return e.getTarget(this.grid.getView().rowSelector);
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {
                    try {
                        var record = data.records[0].data;
                        var fromTree = this.isFromTree(ddSource);
                        if (data.records.length === 1 && record.elementType === "object" && this.dndAllowed(record, fromTree)) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }
                    } catch (e) {
                        console.log(e);
                    }

                }.bind(this),

                onNodeDrop : function(target, dd, e, data) {

                    if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                        return false;
                    }

                    try {
                        var record = data.records[0];
                        var data = record.data;
                        this.nodeElement = data;
                        var fromTree = this.isFromTree(dd);

                        if (data.elementType != "object") {
                            return false;
                        }

                        if (this.dndAllowed(data, fromTree)) {
                            var initData = {
                                id: data.id,
                                fullpath: data.path,
                                className: data.className,
                                published: data.published
                            };

                            if (!this.objectAlreadyExists(initData.id) && initData.id != this.object.id) {
                                this.addObject(initData);
                                return true;
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }.bind(this)
            });
        }.bind(this));


        return this.component;
    },


    dndAllowed: function(data, fromTree) {

        // check if data is a treenode, if not allow drop because of the reordering
        if (!fromTree) {
            return true;
        }

        // only allow objects not folders
        if (data.type == "folder") {
            return false;
        }

        //don't allow relation to myself
        if (data.id == this.object.id) {
            return false;
        }

        var classname = data.className;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.findExact('text', classname));
        var name = record.data.text;

        if (this.fieldConfig.ownerClassName == name) {
            return true;
        } else {
            return false;
        }
    },

    openSearchEditor: function () {
        var allowedClasses = [];
        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.findExact('text', this.fieldConfig.ownerClassName));
        allowedClasses.push(record.data.text);


        pimcore.helpers.itemselector(true, this.addDataFromSelector.bind(this), {
            type: ["object"],
            subtype: [
                {
                    object: ["object", "variant"]
                }
            ],
            specific: {
                classes: allowedClasses
            }
        },
            {
                context: Ext.apply({scope: "objectEditor"}, this.getContext())
            });
    },

    addObject: function(item) {

        if (pimcore.globalmanager.exists("object_" + item.id) == false) {

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobject_get'),
                params: {id: item.id},
                success: function(item, response) {
                    this.data = Ext.decode(response.responseText);
                    if (this.data.editlock) {
                        var lockDate = new Date(this.data.editlock.date * 1000);
                        var lockDetails = "<br /><br />";
                        lockDetails += "<b>" + t("user") + ":</b> " + this.data.editlock.user.name + "<br />";
                        lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate);
                        lockDetails += "<br /><br />" + t("element_implicit_edit_question");

                        Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails,
                                function (lock, buttonValue) {
                                    if (buttonValue == "yes") {
                                        this.store.add({
                                            id: item.id,
                                            path: item.fullpath,
                                            type: item.classname
                                        });
                                    }
                                }.bind(this, arguments));

                    } else {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_element_lockelement'),
                            method: 'PUT',
                            params: {id: item.id, type: 'object'}
                        });
                        var toBeRequested = new Ext.util.Collection();
                        toBeRequested.add(this.store.add({
                            id: item.id,
                            path: item.fullpath,
                            type: item.classname,
                            published: item.published
                        }));
                        this.requestNicePathData(toBeRequested);
                    }

                }.bind(this, item)
            });
        } else {

            var lockDetails = "<br /><br />" + t("element_implicit_edit_question");

            Ext.MessageBox.confirm(' ', t("element_open_message") + lockDetails,
                    function (item, buttonValue) {
                        if (buttonValue == "yes") {
                            this.store.add({
                                id: item.id,
                                path: item.fullpath,
                                type: item.classname,
                                published: item.published
                            });

                        }
                    }.bind(this, item));
        }
    },


    addDataFromSelector: function (items) {
        if (items.length > 0) {
            for (var i = 0; i < items.length; i++) {
                var item = items[i];

                if (this.object.id == item.id) {
                    //cannot select myself!
                    Ext.MessageBox.show({
                        title:t('error'),
                        msg: t('nonownerobjects_self_selection'),
                        buttons: Ext.Msg.OK ,
                        icon: Ext.MessageBox.ERROR
                    });

                } else if (!this.objectAlreadyExists(item.id)) {
                    this.addObject(item);
                }
            }
        }
    }

});

// @TODO BC layer, to be removed in v7.0
pimcore.object.tags.nonownerobjects = pimcore.object.tags.reverseManyToManyObjectRelation;
