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

pimcore.registerNS("pimcore.object.classificationstore.keySelectionWindow");
pimcore.object.classificationstore.keySelectionWindow = Class.create({

    acceptEvents: true,

    // initialize: function (parent, enableGroups, enableKeys, enableCollections, storeId) {
    initialize: function (config) {
        config =  config || {};

        // apply defaults
        Ext.applyIf(config, {


        });
        this.config = config;

        if (this.config.enableGroups && !this.config.enableCollections) {
            this.config.isGroupSearch = true;
        }
        if (this.config.enableCollections) {
            this.config.isCollectionSearch = true;
        }
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
        var title = t('classificationstore_dialog_keygroup_search');
        if (this.config.frameName) {
            title += " - " + t("frame") + " " + this.config.frameName;
        }

        this.searchWindow = new Ext.Window({
            title: title,
            width: 850,
            height: 550,
            modal: true,
            layout: "fit",
            items: [resultPanel],
            listeners: {
                beforeclose: function() {
                    this.config.parent.handleSelectionWindowClosed.call(this.config.parent);
                }.bind(this)
            },
            bbar: [
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
                        if (this.config.isCollectionSearch) {
                            var collectionIds = [];
                            var selected = selectionModel.getSelection();
                            for (var i = 0; i < selected.length; i++) {
                                var collectionId = selected[i].id;
                                collectionIds.push(collectionId);
                            }
                            this.addCollections(collectionIds);

                        } else if (this.config.isGroupSearch) {
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
            this.config.parent.requestPending.call(this.config.parent);
            Ext.Ajax.request({
                url: "/admin/classificationstore/add-collections",
                params: {
                    collectionIds: Ext.util.JSON.encode(collectionIds),
                    oid: this.config.object.id,
                    fieldname: this.config.fieldname
                },
                success: function(response) {
                    this.config.parent.handleAddGroups.call(this.config.parent, response);
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
            this.config.parent.requestPending.call(this.config.parent);
            Ext.Ajax.request({
                url: "/admin/classificationstore/add-groups",
                params: {
                    groupIds: Ext.util.JSON.encode(groupIds)
                },
                success: function(response) {
                    this.config.parent.handleAddGroups.call(this.config.parent, response);
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
            this.config.parent.requestPending.call(this.config.parent);
            Ext.Ajax.request({
                url: "/admin/classificationstore/properties",
                params: {
                    keyIds: Ext.util.JSON.encode(keyIds)
                },
                success: function(response) {
                    this.config.parent.handleAddKeys.call(this.config.parent, response);
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
            pressed: this.config.isCollectionSearch,
            hidden: !this.config.enableCollections
        });
        items.push(this.toolbarbuttons.collection);

        this.toolbarbuttons.group = new Ext.Button({
            text: t("classificationstore_group"),
            handler: this.searchGroup.bind(this),
            iconCls: "pimcore_icon_keys",
            enableToggle: true,
            pressed: this.config.isGroupSearch,
            hidden: !this.config.enableGroups

        });
        items.push(this.toolbarbuttons.group);

        this.toolbarbuttons.key = new Ext.Button({
            text: t("key"),
            handler: this.searchKey.bind(this),
            iconCls: "pimcore_icon_key",
            enableToggle: true,
            pressed: !this.config.isGroupSearch && !this.config.isCollectionSearch,
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
        this.config.isCollectionSearch = true;
        this.config.isGroupSearch = false;
        this.getGridPanel();
    },

    searchGroup: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.group.toggle(true);
        this.config.isGroupSearch = true;
        this.config.isCollectionSearch = false;
        this.getGridPanel();
    },

    searchKey: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.key.toggle(true);
        this.config.isGroupSearch = false;
        this.config.isCollectionSearch = false;
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

        if (this.config.isCollectionSearch) {
            postFix = "collections";
            this.groupFields = ['id', 'name', 'description'];
        } else  if (this.config.isGroupSearch) {
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
                header: t("classificationstore_tag_col_group"),
                width: 150,
                sortable: true,
                dataIndex: 'groupName'
            });
        }

        gridColumns.push({header: t("name"), width: nameWidth, sortable: true, dataIndex: 'name'});
        gridColumns.push({header: t("description"), width: descWidth, sortable: true, dataIndex: 'description'});

        var extraParams = {
            storeId: this.config.storeId
        };

        if (this.config.frameName) {
            extraParams.frameName = this.config.frameName;
        }

        var proxy = {
            type: 'ajax',
            url: "/admin/classificationstore/" + postFix,
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            extraParams: extraParams
        };

        if (this.config.object) {
            proxy.extraParams.oid = this.config.object.id;
            proxy.extraParams.fieldname = this.config.fieldname;
        }

        this.store = new Ext.data.Store({
            remoteSort: true,
            proxy: proxy,
            fields: readerFields
        });


        var emptyMsg;
        if (this.config.isCollectionSearch) {
            emptyMsg = "classificationstore_no_collections";
        } else if (this.config.isGroupSearch) {
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

                    if (this.config.isCollectionSearch) {
                        this.addCollections(data);
                    } else if (this.config.isGroupSearch) {
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