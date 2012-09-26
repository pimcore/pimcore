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

pimcore.registerNS("pimcore.object.tags.nonownerobjects");
pimcore.object.tags.nonownerobjects = Class.create(pimcore.object.tags.objects, {


    removeObject: function (index, item) {

        if (pimcore.globalmanager.exists("object_" + this.getStore().getAt(index).data.id) == false) {

            Ext.Ajax.request({
                url: "/admin/object/get/",
                params: {id: this.getStore().getAt(index).data.id},
                success: function(item, index, response) {
                    this.data = Ext.decode(response.responseText);
                    if (this.data.editlock) {
                        var lockDate = new Date(this.data.editlock.date * 1000);
                        var lockDetails = "<br /><br />";
                        lockDetails += "<b>" + t("user") + ":</b> " + this.data.editlock.user.name + "<br />";
                        lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate);
                        lockDetails += "<br /><br />" + t("element_implicit_edit_question");

                        Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails, function (lock, buttonValue) {
                            if (buttonValue == "yes") {
                                this.getStore().removeAt(index);
                                if (item != null) {
                                    item.parentMenu.destroy();
                                }

                            }
                        }.bind(this, arguments));

                    } else {
                        Ext.Ajax.request({
                            url: "/admin/element/lock-element",
                            params: {id: this.getStore().getAt(index).data.id, type: 'object'}
                        });
                        this.getStore().removeAt(index);
                        if (item != null) {
                            item.parentMenu.destroy();
                        }
                    }

                }.bind(this, item, index)
            });
        } else {

            var lockDetails = "<br /><br />" + t("element_implicit_edit_question");

            Ext.MessageBox.confirm(t("element_is_open"), t("element_open_message") + lockDetails, function (lock, buttonValue) {
                if (buttonValue == "yes") {
                    this.getStore().removeAt(index);
                    item.parentMenu.destroy();
                }
            }.bind(this, arguments));
        }

    },

    actionColumnRemove: function (grid, rowIndex) {
        var f = this.removeObject.bind(grid, rowIndex, null);
        f();
    },

    getLayoutEdit: function () {

        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';

        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.find('text', this.fieldConfig.ownerClassName));
        var className = record.data.text;


        this.component = new Ext.grid.GridPanel({
            plugins: [new Ext.ux.dd.GridDragDropRowOrder({})],
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: [
                    {header: 'ID', dataIndex: 'id', width: 50},
                    {id: "path", header: t("path"), dataIndex: 'path', width: 200},
                    {header: t("type"), dataIndex: 'type', width: 100},
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [
                            {
                                tooltip: t('open'),
                                icon: "/pimcore/static/img/icon/pencil_go.png",
                                handler: function (grid, rowIndex) {
                                    var data = grid.getStore().getAt(rowIndex);
                                    pimcore.helpers.openObject(data.data.id, "object");
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [
                            {
                                tooltip: t('remove'),
                                icon: "/pimcore/static/img/icon/cross.png",
                                handler: this.actionColumnRemove.bind(this)
                            }
                        ]
                    }
                ]
            }),
            cls: cls,
            autoExpandColumn: 'path',
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: {
                items: [
                    {
                        xtype: "tbspacer",
                        width: 20,
                        height: 16,
                        cls: "pimcore_icon_droptarget"
                    },
                    {
                        xtype: "tbtext",
                        text: "<b>" + this.fieldConfig.title + "</b>"
                    },
                    "->",
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_delete",
                        handler: this.empty.bind(this)
                    },
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_search",
                        handler: this.openSearchEditor.bind(this)
                    },
                    this.getCreateControl()
                ],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            bbar: {
                items: [{
                    xtype: "tbtext",
                    text: ' <span class="warning">' + t('nonownerobject_warning') + " | " + t('owner_class') + ':<b>' + ts(className) + "</b> " + t('owner_field') + ': <b>' + ts(this.fieldConfig.ownerFieldName) + '</b></span>'
                }],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_objects"
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

                    if (data.node.attributes.elementType == "object" && this.dndAllowed(data)) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }

                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {

                    if (data.node.attributes.elementType != "object") {
                        return false;
                    }

                    if (this.dndAllowed(data)) {
                        var initData = {
                            id: data.node.attributes.id,
                            fullpath: data.node.attributes.path,
                            className: data.node.attributes.className
                        };

                        if (!this.objectAlreadyExists(initData.id) && initData.id != this.object.id) {
                            this.addObject(initData);
                            return true;
                        } else return false;
                    } else return false;
                }.bind(this)
            });
        }.bind(this));


        return this.component;
    },


    dndAllowed: function(data) {

        // check if data is a treenode, if not allow drop because of the reordering
        if (!this.sourceIsTreeNode(data)) {
            return true;
        }

        // only allow objects not folders
        if (data.node.attributes.type == "folder") {
            return false;
        }

        //don't allow relation to myself
        if (data.node.id == this.object.id) {
            return false;
        }

        var classname = data.node.attributes.className;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.find('text', classname));
        var name = record.data.text;

        if (this.fieldConfig.ownerClassName == name) {
            return true;
        } else return false;


    },

    openSearchEditor: function () {
        var allowedClasses = [];
        var classStore = pimcore.globalmanager.get("object_types_store");
        var record = classStore.getAt(classStore.find('text', this.fieldConfig.ownerClassName));
        allowedClasses.push(record.data.text);


        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["object"],
            subtype: [
                {
                    object: ["object", "variant"]
                }
            ],
            specific: {
                classes: allowedClasses
            }
        });
    },

    addObject: function(item) {

        if (pimcore.globalmanager.exists("object_" + item.id) == false) {

            Ext.Ajax.request({
                url: "/admin/object/get/",
                params: {id: item.id},
                success: function(item, response) {
                    this.data = Ext.decode(response.responseText);
                    if (this.data.editlock) {
                        var lockDate = new Date(this.data.editlock.date * 1000);
                        var lockDetails = "<br /><br />";
                        lockDetails += "<b>" + t("user") + ":</b> " + this.data.editlock.user.name + "<br />";
                        lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate);
                        lockDetails += "<br /><br />" + t("element_implicit_edit_question");

                        Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails, function (lock, buttonValue) {
                            if (buttonValue == "yes") {
                                this.store.add(new this.store.recordType({
                                    id: item.id,
                                    path: item.fullpath,
                                    type: item.classname
                                }, this.store.getCount() + 1));
                            }
                        }.bind(this, arguments));

                    } else {
                        Ext.Ajax.request({
                            url: "/admin/element/lock-element",
                            params: {id: item.id, type: 'object'}
                        });
                        this.store.add(new this.store.recordType({
                            id: item.id,
                            path: item.fullpath,
                            type: item.classname
                        }, this.store.getCount() + 1));
                    }

                }.bind(this, item)
            });
        } else {

            var lockDetails = "<br /><br />" + t("element_implicit_edit_question");

            Ext.MessageBox.confirm(t("element_is_open"), t("element_open_message") + lockDetails, function (item, buttonValue) {
                if (buttonValue == "yes") {
                    this.store.add(new this.store.recordType({
                        id: item.id,
                        path: item.fullpath,
                        type: item.classname
                    }, this.store.getCount() + 1));

                }
            }.bind(this, item));
        }
    },


    addDataFromSelector: function (items) {

        if (this.object.id == items.id) {
            //cannot select myself!
            Ext.MessageBox.show({
                title:t('error'),
                msg: t('nonownerobjects_self_selection'),
                buttons: Ext.Msg.OK ,
                icon: Ext.MessageBox.ERROR
            });

        } else if (!this.objectAlreadyExists(items.id)) {
            this.addObject(items);
        }

    }

});