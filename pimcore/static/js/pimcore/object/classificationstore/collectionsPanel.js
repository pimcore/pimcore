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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.classificationstore.collectionsPanel");
pimcore.object.classificationstore.collectionsPanel = Class.create({

    initialize: function () {

    },

    getPanel: function () {
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t("classificationstore_collection_definition"),
                iconCls: "pimcore_icon_classificationstore_icon_cs_collections",
                border: false,
                layout: "border",
                items: [
                    this.createCollectionsGrid(),
                    this.createRelationsGrid()
                ]

            });
        }

        return this.layout;
    },


    createRelationsGrid: function() {
        this.relationsFields = ['id', 'colId', 'groupId', 'groupName', 'groupDescription'];

        var readerFields = [];
        for (var i = 0; i < this.relationsFields.length; i++) {
            readerFields.push({name: this.relationsFields[i], allowBlank: true});
        }

        var proxy = new Ext.data.HttpProxy({
            url: "/admin/classificationstore/collection-relations",
            method: 'post'
        });

        var writer = new Ext.data.JsonWriter();

        var listeners = {};

        listeners.write = function(store, action, result, response, rs) {};
        listeners.exception = function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), response);
                this.collectionsStore.rejectChanges();
            }
        }.bind(this);

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, readerFields);

        this.relationsStore = new Ext.data.Store({
            restful: false,
            idProperty: 'id',
            remoteSort: true,
            proxy: proxy,
            reader: reader,
            writer: writer,
            listeners: listeners
        });

        var gridColumns = [];

        gridColumns.push({header: t("id"), width: 60, sortable: true, dataIndex: 'id', hidden: true});
        gridColumns.push({header: t("group_id"), width: 60, sortable: true, dataIndex: 'groupId'});
        gridColumns.push({header: t("name"), width: 200, sortable: true, dataIndex: 'groupName'});
        gridColumns.push({header: t("description"), width: 200, sortable: true, dataIndex: 'groupDescription'});

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var colId = data.data.colId;
                        var groupId = data.data.groupId;

                        Ext.Ajax.request({
                            url: "/admin/classificationstore/delete-collection-relation",
                            params: {
                                colId: colId,
                                groupId: groupId
                            },
                            success: function (response) {
                                this.relationsStore.reload();
                            }.bind(this)});
                    }.bind(this)
                }
            ]
        });


        this.relationsPagingtoolbar = new Ext.PagingToolbar({
            pageSize: 15,
            store: this.relationsStore,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("classificationstore_collection_empty")
        });

        var configuredFilters = [
            {
                type: "string",
                dataIndex: "groupId"
            },
            {
            type: "string",
            dataIndex: "groupName"
        },{
            type: "string",
            dataIndex: "groupDescription"
        }];
        var gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: configuredFilters
        });

        var plugins = [gridfilters];

        var gridConfig = {
            frame: false,
            store: this.relationsStore,
            border: true,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            plugins: plugins,
            stripeRows: true,
            trackMouseOver: true,
            region: "west",
            split: true,
            hidden: true,
            viewConfig: {
                forceFit: true
            },
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.relationsPagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAddGroup.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ]
        } ;

        this.relationsGrid = new Ext.grid.EditorGridPanel(gridConfig);

        this.relationsPanel = new Ext.Panel({
            title: t("relations"),
            border: false,
            layout: "fit",
            region: "center",
            split: true,
            disabled: true,
            items: [
                this.relationsGrid
                ]

        });

        return this.relationsPanel;
    },


    createCollectionsGrid: function(response) {
        this.groupsFields = ['id', 'name', 'description', 'creationDate', 'modificationDate'];

        var readerFields = [];
        for (var i = 0; i < this.groupsFields.length; i++) {
            readerFields.push({name: this.groupsFields[i], allowBlank: true});
        }

        var proxy = new Ext.data.HttpProxy({
            url: "/admin/classificationstore/collections",
            method: 'post'
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, readerFields);

        var writer = new Ext.data.JsonWriter();

        var listeners = {};

        listeners.write = function(store, action, result, response, rs) {};
        listeners.exception = function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                this.collectionsStore.rejectChanges();
            }
        }.bind(this);


        this.collectionsStore = new Ext.data.Store({
            restful: false,
            idProperty: 'id',
            remoteSort: true,
            proxy: proxy,
            reader: reader,
            writer: writer,
            listeners: listeners
        });



        var gridColumns = [];

        gridColumns.push({header: "ID", width: 60, sortable: true, dataIndex: 'id'});
        gridColumns.push({header: t("name"), width: 200, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})});
        gridColumns.push({header: t("description"), width: 300, sortable: true, dataIndex: 'description', editor: new Ext.form.TextField({})});

        gridColumns.push(
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false, width: 130,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            }
        );

        gridColumns.push(
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false, width: 130,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            }
        );

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var id = data.data.id;

                        this.relationsStore.removeAll(true);
                        this.relationsGrid.hide();
                        this.relationsPanel.disable();

                        Ext.Ajax.request({
                            url: "/admin/classificationstore/delete-collection",
                            params: {
                                id: id
                            },
                            success: function (response) {
                                this.collectionsStore.reload();
                            }.bind(this)});
                    }.bind(this)
                }
            ]
        });

        this.collectionsPagingtoolbar = new Ext.PagingToolbar({
            pageSize: 15,
            store: this.collectionsStore,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("classificationstore_no_collections")
        });

        var configuredFilters = [
            {
                type: "string",
                dataIndex: "id"
            },
            {
            type: "string",
            dataIndex: "name"
        },{
            type: "string",
            dataIndex: "description"
        }];
        var gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: configuredFilters
        });

        var plugins = [gridfilters];

        var gridConfig = {
            frame: false,
            store: this.collectionsStore,
            border: true,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            plugins: plugins,
            stripeRows: true,
            trackMouseOver: true,
            region: "west",
            split: true,
            width: 600,
            viewConfig: {
                forceFit: true
            },
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.collectionsPagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ],
            listeners: {

                rowclick: function (grid, rowIndex, ev) {
                    var record = this.collectionsStore.getAt(rowIndex);
                    var collectionId = record.data.id;
                    var collectionName = record.data.name;

                    this.collectionId = collectionId;

                    this.relationsPanel.setTitle(t("relations") + " - "  + t("collection") +  " " + record.data.id + " - " + collectionName);
                    this.relationsPanel.enable();
                    this.relationsStore.removeAll(true);
                    this.relationsStore.setBaseParam("colId", collectionId);
                    this.relationsStore.reload();
                    this.relationsGrid.show();

                }.bind(this)
            }
        } ;

        this.grid = new Ext.grid.EditorGridPanel(gridConfig);

        this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));


        this.grid.on("sortchange", function(grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));

        this.collectionsStore.load();

        return this.grid
    },


    onAddGroup: function() {
        var window = new pimcore.object.classificationstore.keySelectionWindow(this, true, false, false);
        window.show();
    },

    onAdd: function () {
        Ext.MessageBox.prompt(t('classificationstore_mbx_entergroup_title'), t('classificationstore_mbx_entergroup_prompt'),
            this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        value = value.trim();
        if (button == "ok" && value.length > 1) {
            Ext.Ajax.request({
                url: "/admin/classificationstore/create-collection",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    if(!data || !data.success) {
                        Ext.Msg.alert(t("error"), t("classificationstore_error_addcollection_msg"));
                    } else {
                        this.collectionsStore.reload({
                                callback: function() {
                                    var rowIndex = this.collectionsStore.find('name', value);
                                    // alert(rowIndex);
                                    if (rowIndex != -1) {
                                        var sm = this.grid.getSelectionModel();
                                        sm.selectRow(rowIndex);
                                    }

                                    var lastOptions = this.collectionsStore.lastOptions;
                                    Ext.apply(lastOptions.params, {
                                        overrideSort: "false"
                                    });
                                }.bind(this),
                                params: {
                                    "overrideSort": "true"
                                }
                            }
                        );
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t("classificationstore_configuration"), t("classificationstore_invalidname"));
        }
    },


    onRowContextmenu: function (grid, rowIndex, event) {
        // no context menu
    },

    handleSelectionWindowClosed: function() {

    },

    handleAddGroups: function (response) {
        var data = Ext.decode(response.responseText);

        if(data) {
            for (groupId in data) {
                if (data.hasOwnProperty(groupId)) {
                    var groupDef = data[groupId];

                    var colData = {};
                    colData.groupId = groupDef.id;
                    colData.groupName = groupDef.name;
                    colData.gropDescription = groupDef.description;
                    colData.colId = this.collectionId;
                    colData.id = this.collectionId + "-" + colData.groupId;

                    var match = this.relationsStore.findExact("id", colData.id);
                    if (match == -1) {
                        this.relationsStore.add(new this.relationsStore.recordType(colData));
                    }
                }
            }
        }
    },

    requestPending: function() {
        // nothing to do
    }

});

