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

pimcore.registerNS("pimcore.object.tags.nonownerobjects");
pimcore.object.tags.nonownerobjects = Class.create(pimcore.object.tags.objects, {


    removeObject: function (index, item) {

        if (pimcore.globalmanager.exists("object_" + this.getStore().getAt(index).data.id) == false) {

            Ext.Ajax.request({
                url: "/admin/object/get",
                params: {id: this.getStore().getAt(index).data.id},
                success: function(item, index, response) {
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

            Ext.MessageBox.confirm(t("element_is_open"), t("element_open_message") + lockDetails,
                    function (lock, buttonValue) {
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

        // no class for nonowner is specified
        if(!record) {
            this.component = new Ext.Panel({
                title: ts(this.fieldConfig.title),
                cls: cls,
                html: "There's no class specified in the field-configuration"
            });

            return this.component;
        }


        var className = record.data.text;


        this.component = new Ext.grid.GridPanel({
            store: this.store,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            columns: {
                defaults: {
                    sortable: false
                },
                items: [
                    {header: 'ID', dataIndex: 'id', flex: 50},
                    {header: t("reference"), dataIndex: 'path', flex: 200},
                    {header: t("type"), dataIndex: 'type', flex: 100},
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [
                            {
                                tooltip: t('open'),
                                icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
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
                                icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
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
                    text: ' <span class="warning">' + t('nonownerobject_warning') + " | " + t('owner_class')
                                    + ':<b>' + ts(className) + "</b> " + t('owner_field') + ': <b>'
                                    + ts(this.fieldConfig.ownerFieldName) + '</b></span>'
                }],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_objects",
            viewConfig: {
                listeners: {
                    refresh: function (gridview) {
                        this.requestNicePathData(this.store.data);
                    }.bind(this)
                }
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
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(ddSource);

                        if (data.elementType == "object" && this.dndAllowed(data, fromTree)) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }
                    } catch (e) {
                        console.log(e);
                    }

                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {
                    try {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(dd);

                        if (data.elementType != "object") {
                            return false;
                        }

                        if (this.dndAllowed(data, fromTree)) {
                            var initData = {
                                id: data.id,
                                fullpath: data.path,
                                className: data.className
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
        var record = classStore.getAt(classStore.find('text', classname));
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
        var record = classStore.getAt(classStore.find('text', this.fieldConfig.ownerClassName));
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
                url: "/admin/object/get",
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
                            url: "/admin/element/lock-element",
                            params: {id: item.id, type: 'object'}
                        });
                        var toBeRequested = new Ext.util.Collection();
                        toBeRequested.add(this.store.add({
                            id: item.id,
                            path: item.fullpath,
                            type: item.classname
                        }));
                        this.requestNicePathData(toBeRequested);
                    }

                }.bind(this, item)
            });
        } else {

            var lockDetails = "<br /><br />" + t("element_implicit_edit_question");

            Ext.MessageBox.confirm(t("element_is_open"), t("element_open_message") + lockDetails,
                    function (item, buttonValue) {
                        if (buttonValue == "yes") {
                            this.store.add({
                                id: item.id,
                                path: item.fullpath,
                                type: item.classname
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