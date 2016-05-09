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

pimcore.registerNS("pimcore.object.classificationstore.keySelectionWindow");
pimcore.object.classificationstore.keySelectionWindow = Class.create({

    acceptEvents: true,

    initialize: function (parent, enableGroups, enableKeys, enableCollections, storeId) {
        this.parent = parent;
        this.enableGroups = enableGroups;
        this.enableKeys = enableKeys;
        this.enableCollections = enableCollections;
        this.storeId = storeId;

        if (enableGroups && !enableCollections) {
            this.isGroupSearch = true;
        }
        if (enableCollections) {
            this.isCollectionSearch = true;
        }
    },

    setRestriction: function(object, fieldname) {
        this.object = object;
        this.fieldname = fieldname;
    },


    show: function() {
        this.searchfield = new Ext.form.TextField({
            width: 300,
            style: "float: left;",
            fieldLabel: t("search"),
            enableKeyEvents: true,
            listeners: {
                keypress: function(searchField, e, eOpts) {
                    if (e.getKey() == 13) {
                        this.applySearchFilter();
                    }
                }.bind(this)
            }
        });

        var resultPanel = this.getResultPanel();

        this.searchWindow = new Ext.Window({
            title: t('keyvalue_dialog_keygroup_search'),
            width: 850,
            height: 550,
            modal: true,
            layout: "fit",
            items: [resultPanel],
            listeners: {
                beforeclose: function() {
                    this.parent.handleSelectionWindowClosed.call(this.parent);
                }.bind(this)
            },
            bbar: [//this.searchfield,
                "->",{
                    xtype: "button",
                    text: t("cancel"),
                    iconCls: "pimcore_icon_cancel",
                    handler: function () {
                        this.searchWindow.close();
                    }.bind(this)
                },{
                    xtype: "button",
                    text: t("apply"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        var selectionModel = this.gridPanel.getSelectionModel();
                        if (this.isCollectionSearch) {
                            var collectionIds = [];
                            var selected = selectionModel.getSelection();
                            for (var i = 0; i < selected.length; i++) {
                                var collectionId = selected[i].id;
                                collectionIds.push(collectionId);
                            }
                            this.addCollections(collectionIds);

                        } else if (this.isGroupSearch) {
                            var groupIds = [];
                            var selected = selectionModel.getSelection();
                            for (var i = 0; i < selected.length; i++) {
                                var groupId = selected[i].id;
                                groupIds.push(groupId);
                            }
                            this.addGroups(groupIds);
                        } else {
                            var keyIds = [];
                            var selectedKeys = selectionModel.getSelection();
                            for (var ki = 0; ki < selectedKeys.length; ki++) {
                                var keyId = selectedKeys[ki].id;
                                keyIds.push(keyId);
                            }
                            this.addKeys(keyIds);
                        }

                    }.bind(this)
                }]
        });

        this.searchWindow.show();
    },

    addCollections: function(collectionIds) {
        if (!this.acceptEvents) {
            return;
        }
        this.acceptEvents = false;
        if (collectionIds.length > 0) {
            this.parent.requestPending.call(this.parent);
            Ext.Ajax.request({
                url: "/admin/classificationstore/add-collections",
                params: {
                    collectionIds: Ext.util.JSON.encode(collectionIds),
                    oid: this.object.id,
                    fieldname: this.fieldname
                },
                success: function(response) {
                    this.parent.handleAddGroups.call(this.parent, response);
                    this.searchWindow.close();
                }.bind(this),
                failure: function(response) {
                    this.searchWindow.close();
                }.bind(this)
            });
        } else {
            this.searchWindow.close();
        }
    },


    addGroups: function(groupIds) {
        if (!this.acceptEvents) {
            return;
        }
        this.acceptEvents = false;
        if (groupIds.length > 0) {
            this.parent.requestPending.call(this.parent);
            Ext.Ajax.request({
                url: "/admin/classificationstore/add-groups",
                params: {
                    groupIds: Ext.util.JSON.encode(groupIds)
                },
                success: function(response) {
                    this.parent.handleAddGroups.call(this.parent, response);
                    this.searchWindow.close();
                }.bind(this),
                failure: function(response) {
                    this.searchWindow.close();
                }.bind(this)
            });
        } else {
            this.searchWindow.close();
        }
    },


    addKeys: function(keyIds) {
        if (!this.acceptEvents) {
            return;
        }
        this.acceptEvents = false;
        if (keyIds.length > 0) {
            this.parent.requestPending.call(this.parent);
            Ext.Ajax.request({
                url: "/admin/classificationstore/properties",
                params: {
                    keyIds: Ext.util.JSON.encode(keyIds)
                },
                success: function(response) {
                    this.parent.handleAddKeys.call(this.parent, response);
                    this.searchWindow.close();
                }.bind(this),
                failure: function(response) {
                    this.searchWindow.close();
                }.bind(this)
            });
        } else {
            this.searchWindow.close();
        }
    },

    getToolbar: function () {

        var user = pimcore.globalmanager.get("user");
        var toolbar;
        var items = [];
        this.toolbarbuttons = {};


        this.toolbarbuttons.collection = new Ext.Button({
            text: t("collection"),
            handler: this.searchCollection.bind(this),
            iconCls: "pimcore_icon_classificationstore_icon_cs_collections",
            enableToggle: true,
            pressed: this.isCollectionSearch,
            hidden: !this.enableCollections
        });
        items.push(this.toolbarbuttons.collection);

        this.toolbarbuttons.group = new Ext.Button({
            text: t("keyValue_group"),
            handler: this.searchGroup.bind(this),
            iconCls: "pimcore_icon_keys",
            enableToggle: true,
            pressed: this.isGroupSearch,
            hidden: !this.enableGroups

        });
        items.push(this.toolbarbuttons.group);

        this.toolbarbuttons.key = new Ext.Button({
            text: t("key"),
            handler: this.searchKey.bind(this),
            iconCls: "pimcore_icon_key",
            enableToggle: true,
            pressed: !this.isGroupSearch && !this.isCollectionSearch,
            hidden: !this.enableKeys
        });
        items.push(this.toolbarbuttons.key);

        items.push("->");
        items.push(this.searchfield);

        items.push({
            xtype: "button",
            text: t("search"),
            iconCls: "pimcore_icon_search",
            handler: this.applySearchFilter.bind(this)
        });

        if(items.length > 1) {
            toolbar = {
                items: items
            };
        }

        return toolbar;
    },
    
    applySearchFilter: function() {
        var formValue = this.searchfield.getValue();

        this.store.getProxy().setExtraParam("searchfilter", formValue);


        var lastOptions = this.store.lastOptions;
        Ext.apply(lastOptions.params, {
            filter: this.encodedFilter
        });
        this.store.reload();

        this.gridPanel.getView().refresh();
    },

    resetToolbarButtons: function () {
        if(this.toolbarbuttons.collection) {
            this.toolbarbuttons.collection.toggle(false);
        }

        if(this.toolbarbuttons.group) {
            this.toolbarbuttons.group.toggle(false);
        }
        if(this.toolbarbuttons.key) {
            this.toolbarbuttons.key.toggle(false);
        }
    },

    searchCollection: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.collection.toggle(true);
        this.isCollectionSearch = true;
        this.isGroupSearch = false;
        this.getGridPanel();
    },

    searchGroup: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.group.toggle(true);
        this.isGroupSearch = true;
        this.isCollectionSearch = false;
        this.getGridPanel();
    },

    searchKey: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.key.toggle(true);
        this.isGroupSearch = false;
        this.isCollectionSearch = false;
        this.getGridPanel();
    },

    getResultPanel: function () {
        this.resultPanel = new Ext.Panel({
            tbar: this.getToolbar(),
            layout: "fit",
            style: {
                backgroundColor: "#0000FF"
            }
        });

        this.getGridPanel();
        return this.resultPanel;
    },

    getData: function () {
        var selected = this.groupGridPanel.getSelectionModel().getSelections();
        if(selected) {
            return selected.data.id;
        }
        return null;
    },

    getGridPanel: function() {
        var postFix;
        var nameWidth = 200;
        var descWidth = 590;

        if (this.isCollectionSearch) {
            postFix = "collections";
            this.groupFields = ['id', 'name', 'description'];
        } else  if (this.isGroupSearch) {
            postFix = "groups";
            this.groupFields = ['id', 'name', 'description'];
        } else {
            postFix = "properties";
            nameWidth = 150;
            descWidth = 490;
            this.groupFields = ['id', 'groupName', 'name', 'description'];
        }


        var readerFields = [];
        for (var i = 0; i < this.groupFields.length; i++) {
            readerFields.push({name: this.groupFields[i], allowBlank: true});
        }

        var gridColumns = [];
        gridColumns.push({header: "ID", width: 40, sortable: true, dataIndex: 'id'});

        if (postFix == "properties") {
            gridColumns.push({
                header: t("keyvalue_tag_col_group"),
                width: 150,
                sortable: true,
                dataIndex: 'groupName'
            });
        }

        gridColumns.push({header: t("name"), width: nameWidth, sortable: true, dataIndex: 'name'});
        gridColumns.push({header: t("description"), width: descWidth, sortable: true, dataIndex: 'description'});


        var proxy = {
            type: 'ajax',
            url: "/admin/classificationstore/" + postFix,
            reader: {
                type: 'json',
                rootProperty: 'data',
            },
            extraParams: {
                storeId: this.storeId
            }
        };

        if (this.object) {
            proxy.extraParams.oid = this.object.id;
            proxy.extraParams.fieldname = this.fieldname;
        }

        this.store = new Ext.data.Store({
            remoteSort: true,
            proxy: proxy,
            fields: readerFields
        });


        var emptyMsg;
        if (this.isCollectionSearch) {
            emptyMsg = "classificationstore_no_collections";
        } else if (this.isGroupSearch) {
            emptyMsg = "classificationstore_no_groups";
        } else {
            emptyMsg = "classificationstore_no_keys";
        }

        var pageSize = pimcore.helpers.grid.getDefaultPageSize(-1);
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: pageSize});

        this.gridPanel = new Ext.grid.GridPanel({
            store: this.store,
            border: false,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            bodyCls: "pimcore_editable_grid",
            stripeRows: true,
            selModel: Ext.create('Ext.selection.RowModel', {
                mode: 'MULTI'
            }),
            bbar: this.pagingtoolbar,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = [grid.getStore().getAt(rowIndex).id];

                    if (this.isCollectionSearch) {
                        this.addCollections(data);
                    } else if (this.isGroupSearch) {
                        this.addGroups(data);
                    } else {
                        this.addKeys(data);
                    }

                    this.searchWindow.close();
                }.bind(this)
            }
        });

        this.store.load();

        this.resultPanel.removeAll();
        this.resultPanel.add(this.gridPanel);
        this.resultPanel.updateLayout();
    }

});