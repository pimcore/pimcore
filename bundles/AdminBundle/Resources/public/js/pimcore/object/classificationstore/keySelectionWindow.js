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
/*
 * this is for the object editor and the add key window in the classification store definition
 */
pimcore.object.classificationstore.keySelectionWindow = Class.create({
    acceptEvents: true,

    initialize: function (config) {
        config = config || {};

        // apply defaults
        Ext.applyIf(config, {});
        this.config = config;

        if (this.config.enableGroups && !this.config.enableCollections) {
            this.config.isGroupSearch = true;
        }
        if (this.config.enableCollections) {
            this.config.isCollectionSearch = true;
        }
    },

    show: function () {
        if (this.config.maxItems > 0 && this.config.parent.getUsedActiveGroups().length >= this.config.maxItems) {
            pimcore.helpers.showNotification(t('validation_failed'), t('limit_reached'), 'error');

            return;
        }

        this.searchfield = new Ext.form.field.Text({
            width: 300,
            style: "float: left;",
            fieldLabel: t("search"),
            enableKeyEvents: true,
            listeners: {
                keypress: function (searchField, e, eOpts) {
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

        this.searchWindow = new Ext.window.Window({
            title: title,
            width: 850,
            height: 550,
            modal: true,
            layout: "fit",
            items: [resultPanel],
            listeners: {
                beforeclose: function () {
                    this.config.parent.handleSelectionWindowClosed.call(this.config.parent);
                }.bind(this)
            },
            bbar: [
                "->", {
                    xtype: "button",
                    text: t("cancel"),
                    iconCls: "pimcore_icon_cancel",
                    handler: function () {
                        this.searchWindow.close();
                    }.bind(this)
                }, {
                    xtype: "button",
                    text: t("apply"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        var selectionModel = this.gridPanel.getSelectionModel();
                        var selected = selectionModel.getSelection();
                        if (this.config.isCollectionSearch) {
                            var collectionIds = [];
                            for (var i = 0; i < selected.length; i++) {
                                var collectionId = selected[i].id;
                                collectionIds.push(collectionId);
                            }
                            this.addCollections(collectionIds);
                        } else if (this.config.isGroupSearch || this.config.isGroupByKeySearch) {
                            var groupIds = [];
                            for (var i = 0; i < selected.length; i++) {
                                var groupId = this.config.isGroupSearch ? selected[i].id : selected[i].get("groupId");
                                groupIds.push(groupId);
                            }
                            this.addGroups(groupIds);
                        } else {
                            var keyIds = [];

                            for (var ki = 0; ki < selected.length; ki++) {
                                var keyId = selected[ki].id;
                                keyIds.push(keyId);
                            }
                            this.addKeys(keyIds);
                        }

                    }.bind(this)
                }]
        });

        this.searchWindow.show();
    },

    addCollections: function (collectionIds) {
        if (!this.acceptEvents) {
            return;
        }
        this.acceptEvents = false;
        if (collectionIds.length > 0) {
            this.config.parent.requestPending.call(this.config.parent);
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_classificationstore_addcollections'),
                method: 'POST',
                params: {
                    collectionIds: Ext.util.JSON.encode(collectionIds),
                    oid: this.config.object ? this.config.object.id : null,
                    fieldname: this.config ? this.config.fieldname : null
                },
                success: function (response) {
                    this.config.parent.handleAddGroups.call(this.config.parent, response);
                    this.searchWindow.close();
                }.bind(this),
                failure: function (response) {
                    this.searchWindow.close();
                }.bind(this)
            });
        } else {
            this.searchWindow.close();
        }
    },

    addGroups: function (groupIds) {
        if (!this.acceptEvents) {
            return;
        }

        groupIds = Ext.Array.unique(groupIds.map(function (groupId) {
            return parseInt(groupId);
        }));

        if (
            this.config.maxItems > 0 &&
            Ext.Array.merge(groupIds, this.config.parent.getUsedActiveGroups()).length > this.config.maxItems
        ) {
            pimcore.helpers.showNotification(t('validation_failed'), t('limit_reached'), 'error');

            return;
        }

        this.acceptEvents = false;

        if (groupIds.length > 0) {
            this.config.parent.requestPending.call(this.config.parent);
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_classificationstore_addgroups'),
                method: 'POST',
                params: {
                    groupIds: Ext.util.JSON.encode(groupIds),
                    oid: this.config.object ? this.config.object.id : null,
                    fieldname: this.config ? this.config.fieldname : null
                },
                success: function (response) {
                    this.config.parent.handleAddGroups.call(this.config.parent, response);
                    this.searchWindow.close();
                }.bind(this),
                failure: function (response) {
                    this.searchWindow.close();
                }.bind(this)
            });
        } else {
            this.searchWindow.close();
        }
    },


    addKeys: function (keyIds) {
        if (!this.acceptEvents) {
            return;
        }
        this.acceptEvents = false;
        if (keyIds.length > 0) {
            this.config.parent.requestPending.call(this.config.parent);
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_classificationstore_propertiesget'),
                params: {
                    keyIds: Ext.util.JSON.encode(keyIds)
                },
                success: function (response) {
                    this.config.parent.handleAddKeys.call(this.config.parent, response);
                    this.searchWindow.close();
                }.bind(this),
                failure: function (response) {
                    this.searchWindow.close();
                }.bind(this)
            });
        } else {
            this.searchWindow.close();
        }
    },

    getToolbar: function () {
        var toolbar;
        var items = [];
        this.toolbarbuttons = {};

        if (this.config.enableCollections) {
            this.toolbarbuttons.collection = new Ext.Button({
                text: t("collection"),
                handler: this.searchCollection.bind(this),
                iconCls: "pimcore_icon_classificationstore_icon_cs_collections",
                enableToggle: true,
                pressed: this.config.isCollectionSearch,

            });
        }
        items.push(this.toolbarbuttons.collection);

        if (this.config.enableGroups) {
            this.toolbarbuttons.group = new Ext.Button({
                text: t("classificationstore_group"),
                handler: this.searchGroup.bind(this),
                iconCls: "pimcore_icon_keys",
                enableToggle: true,
                pressed: this.config.isGroupSearch,
                hidden: !this.config.enableGroups
            });
            items.push(this.toolbarbuttons.group);
        }

        if (this.config.enableGroupByKey) {
            this.toolbarbuttons.groupByKey = new Ext.Button({
                text: t("classificationstore_group_by_key"),
                handler: this.searchGroupByKey.bind(this),
                iconCls: "pimcore_icon_key",
                enableToggle: true,
                pressed: this.config.isGroupByKeySearch,

            });
            items.push(this.toolbarbuttons.groupByKey);
        }


        if (this.config.enableKeys) {
            this.toolbarbuttons.key = new Ext.Button({
                text: t("key"),
                handler: this.searchKey.bind(this),
                iconCls: "pimcore_icon_key",
                enableToggle: true,
                pressed: !this.config.isGroupSearch && !this.config.isCollectionSearch,

            });
            items.push(this.toolbarbuttons.key);
        }

        items.push("->");
        items.push(this.searchfield);

        items.push({
            xtype: "button",
            text: t("search"),
            iconCls: "pimcore_icon_search",
            handler: this.applySearchFilter.bind(this)
        });

        if (items.length > 1) {
            toolbar = {
                items: items
            };
        }

        return toolbar;
    },

    applySearchFilter: function () {
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
        if (this.toolbarbuttons.collection) {
            this.toolbarbuttons.collection.toggle(false);
        }

        if (this.toolbarbuttons.group) {
            this.toolbarbuttons.group.toggle(false);
        }
        if (this.toolbarbuttons.key) {
            this.toolbarbuttons.key.toggle(false);
        }
    },

    setupSearch: function (type, configKey) {
        this.resetToolbarButtons();
        this.toolbarbuttons[type].toggle(true);

        this.config.isCollectionSearch = false;
        this.config.isGroupSearch = false;
        this.config.isKeySearch = false;
        this.config.isGroupByKeySearch = false;

        this.config[configKey] = true;
        this.getGridPanel();

    },

    searchCollection: function () {
        this.setupSearch("collection", "isCollectionSearch");
    },

    searchGroup: function () {
        this.setupSearch("group", "isGroupSearch");
    },

    searchKey: function () {
        this.setupSearch("key", "key");
    },

    searchGroupByKey: function () {
        this.setupSearch("groupByKey", "isGroupByKeySearch");
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
        if (selected) {
            return selected.data.id;
        }
        return null;
    },

    getGridPanel: function () {
        var postFix;
        var route;
        var nameWidth = 200;
        var descWidth = 590;

        if (this.config.isCollectionSearch) {
            route = 'pimcore_admin_dataobject_classificationstore_collectionsactionget';
            this.groupFields = ['id', 'name', 'description'];
        } else if (this.config.isGroupSearch) {
            route = 'pimcore_admin_dataobject_classificationstore_groupsactionget';
            this.groupFields = ['id', 'name', 'description'];
        } else if (this.config.isGroupsBySearch) {
            this.groupFields = ['id', 'groupName', 'keyName', 'keyDescription', 'keyId', 'groupId'];
        } else {
            route = 'pimcore_admin_dataobject_classificationstore_propertiesget';
            nameWidth = 150;
            descWidth = 490;
            this.groupFields = ['id', 'groupName', 'name', 'description'];
        }

        if (this.config.isGroupByKeySearch) {
            var url = Routing.generate('pimcore_admin_dataobject_classificationstore_searchrelations');
        } else {
            var url = Routing.generate(route);
        }

        var readerFields = [];
        for (var i = 0; i < this.groupFields.length; i++) {
            readerFields.push({name: this.groupFields[i]});
        }

        var gridColumns = [];
        if (this.config.isGroupByKeySearch) {
            gridColumns.push({text: "ID", width: 60, sortable: true, dataIndex: 'id'});

            gridColumns.push({
                text: t("group"),
                flex: 1,
                sortable: true,
                dataIndex: 'groupName',
                filter: 'string',
                renderer: pimcore.helpers.grid.getTranslationColumnRenderer.bind(this)
            });

            gridColumns.push({
                text: t("name"),
                flex: 1,
                sortable: true,
                dataIndex: 'keyName',
                filter: 'string',
                renderer: pimcore.helpers.grid.getTranslationColumnRenderer.bind(this)
            });

            gridColumns.push({
                text: t("description"),
                flex: 1,
                sortable: true,
                dataIndex: 'keyDescription',
                filter: 'string',
                renderer: pimcore.helpers.grid.getTranslationColumnRenderer.bind(this)
            });
        } else {
            gridColumns.push({text: "ID", width: 40, sortable: true, dataIndex: 'id'});

            if (postFix == "properties") {
                gridColumns.push({
                    text: t("classificationstore_tag_col_group"),
                    width: 150,
                    sortable: true,
                    dataIndex: 'groupName'
                });
            }

            gridColumns.push({
                text: t("name"),
                width: nameWidth,
                sortable: true,
                dataIndex: 'name',
                renderer: pimcore.helpers.grid.getTranslationColumnRenderer.bind(this)
            });

            gridColumns.push({
                text: t("description"),
                width: descWidth,
                sortable: true,
                dataIndex: 'description',
                renderer: pimcore.helpers.grid.getTranslationColumnRenderer.bind(this)
            });
        }

        var extraParams = {
            storeId: this.config.storeId
        };

        if (this.config.frameName) {
            extraParams.frameName = this.config.frameName;
        }

        var proxy = {
            type: 'ajax',
            url: url,
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

        var pageSize = pimcore.helpers.grid.getDefaultPageSize(-1);
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: pageSize});

        this.gridPanel = new Ext.grid.Panel({
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
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts) {
                    if (this.config.isGroupByKeySearch) {
                        let data = [grid.getStore().getAt(rowIndex).get("groupId")];
                        this.addGroups(data);
                    } else {
                        let data = [grid.getStore().getAt(rowIndex).id];

                        if (this.config.isCollectionSearch) {
                            this.addCollections(data);
                        } else if (this.config.isGroupSearch) {
                            this.addGroups(data);
                        } else {
                            this.addKeys(data);
                        }
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
