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

pimcore.registerNS("pimcore.object.classificationstore.collectionsPanel");
pimcore.object.classificationstore.collectionsPanel = Class.create({

    initialize: function (storeConfig, groupsPanel) {
        this.groupsPanel = groupsPanel;
        this.storeConfig = storeConfig;
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
                ],
                viewConfig: {
                    forceFit: true
                }

            });
        }

        return this.layout;
    },


    createRelationsGrid: function() {
        this.relationsFields = ['id', 'colId', 'groupId', 'groupName', 'groupDescription', 'sorter'];

        var readerFields = [];
        for (var i = 0; i < this.relationsFields.length; i++) {
            var columnConfig = {name: this.relationsFields[i]};
            if (this.relationsFields[i] == "sorter") {
                columnConfig["type"] = "int";
            }
            readerFields.push(columnConfig);
        }

        var route = 'pimcore_admin_dataobject_classificationstore_collectionrelations';
        var proxy = {
            batchActions: false,
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            api: {
                create  : Routing.generate(route, {'xaction': 'create'}),
                read    : Routing.generate(route, {'xaction': 'read'}),
                update  : Routing.generate(route, {'xaction': 'update'}),
                destroy : Routing.generate(route, {'xaction': 'destroy'})
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            extraParams: {
                storeId: this.storeConfig.id
            }
        };

        var listeners = {};

        listeners.exception = function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), response);
                this.collectionsStore.rejectChanges();
            }
        }.bind(this);

        this.relationsStore = new Ext.data.Store({
            autoSync: true,
            proxy: proxy,
            fields: readerFields,
            listeners: listeners
        });

        var gridColumns = [];

        gridColumns.push({
            xtype: 'actioncolumn',
            text: t("open"),
            menuText: t("open"),
            width: 40,
            items: [
                {
                    tooltip: t("open"),
                    iconCls: "pimcore_icon_open",
                    handler: function (grid, rowIndex) {
                        var store = grid.getStore();
                        var data = store.getAt(rowIndex).getData();
                        var groupId = data.groupId;
                        this.groupsPanel.openConfig(groupId);
                    }.bind(this)
                }
            ]
        });

        gridColumns.push({text: t("group_id"), flex: 60, sortable: true, dataIndex: 'groupId', filter: 'string'});
        gridColumns.push({text: t("name"), flex: 200, sortable: true, dataIndex: 'groupName', filter: 'string'});
        gridColumns.push({text: t("description"), flex: 200, sortable: true, dataIndex: 'groupDescription', filter: 'string'});

        gridColumns.push({text: t('sorter'), width: 150, sortable: true, dataIndex: 'sorter',
            tooltip: t("classificationstore_tooltip_sorter"),
            editor: new Ext.form.NumberField()
        });


        gridColumns.push({
            xtype: 'actioncolumn',
            menuText: t('remove'),
            hideable: false,
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var colId = data.data.colId;
                        var groupId = data.data.groupId;

                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_dataobject_classificationstore_deletecollectionrelation'),
                            method: 'DELETE',
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


        var pageSize = pimcore.helpers.grid.getDefaultPageSize(-1);
        this.relationsPagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.relationsStore, {pageSize: pageSize});


        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 2
        });

        var plugins = ['gridfilters', cellEditing];

        var gridConfig = {
            frame: false,
            store: this.relationsStore,
            //border: true,
            columns: gridColumns,
            bodyCls: "pimcore_editable_grid",
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
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.relationsPagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAddGroup.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ]
        } ;

        this.relationsGrid = Ext.create('Ext.grid.Panel', gridConfig);

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
        this.groupsFields = ['storeId','id', 'name', 'description', 'creationDate', 'modificationDate'];

        var readerFields = [];
        for (var i = 0; i < this.groupsFields.length; i++) {
            readerFields.push({name: this.groupsFields[i]});
        }

        var proxy = {
            url: Routing.generate('pimcore_admin_dataobject_classificationstore_collectionsactionget'),
            batchActions: false,
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            extraParams: {
                storeId: this.storeConfig.id
            }
        };

        var listeners = {};

        listeners.exception = function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                this.collectionsStore.rejectChanges();
            }
        }.bind(this);


        this.collectionsStore = new Ext.data.Store({
            autoSync: true,
            proxy: proxy,
            fields: readerFields,
            listeners: listeners,
            remoteFilter: true,
            remoteSort: true
        });


        var gridColumns = [];

        //gridColumns.push({text: t("store"), flex: 60, sortable: true, dataIndex: 'storeId', filter: 'string'});
        gridColumns.push({text: "ID", flex: 60, sortable: true, dataIndex: 'id', filter: 'string'});
        gridColumns.push({text: t("name"), flex: 200, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({text: t("description"), flex: 300, sortable: true, dataIndex: 'description', editor: new Ext.form.TextField({}), filter: 'string'});

        var dateRenderer =  function(d) {
            if (d !== undefined) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            } else {
                return "";
            }
        };


        gridColumns.push(
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false, width: 130,
                hidden: true,
                renderer: dateRenderer            }
        );

        gridColumns.push(
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false, width: 130,
                hidden: true,
                renderer: dateRenderer
            }
        );

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            menuText: t('remove'),
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var id = data.data.id;

                        this.relationsStore.removeAll(true);
                        this.relationsGrid.hide();
                        this.relationsPanel.disable();

                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_dataobject_classificationstore_deletecollection'),
                            method: 'DELETE',
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

        var pageSize = pimcore.helpers.grid.getDefaultPageSize(-1);
        this.collectionsPagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.collectionsStore, {pageSize: pageSize});


        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {});

        var plugins = ['gridfilters', cellEditing];

        var gridConfig = {
            frame: false,
            store: this.collectionsStore,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            bodyCls: "pimcore_editable_grid",
            stripeRows: true,
            trackMouseOver: true,
            plugins: plugins,
            region: "west",
            split: true,
            width: 600,
            viewConfig: {
                forceFit: true
            },
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.collectionsPagingtoolbar,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ],
            listeners: {
                selectionchange: function(rowModel, selected, eOpts ) {
                    if (selected.length > 0) {
                        var record = selected[0];
                        var collectionId = record.data.id;
                        var collectionName = record.data.name;

                        this.collectionId = collectionId;

                        this.relationsPanel.setTitle(t("relations") + " - " + t("collection") + " " + record.data.id + " - " + collectionName);
                        this.relationsPanel.enable();
                        var proxy = this.relationsStore.getProxy();
                        proxy.setExtraParam("colId", collectionId);
                        this.relationsStore.reload();
                        this.relationsGrid.show();
                    }

                }.bind(this)
            }
        } ;

        this.grid = Ext.create('Ext.grid.Panel', gridConfig);

        return this.grid
    },


    onAddGroup: function() {
        var keySelectionWindow = new pimcore.object.classificationstore.keySelectionWindow(
            {
                parent: this,
                enableGroups: true,
                storeId: this.storeConfig.id
            });

        keySelectionWindow.show();
    },

    onAdd: function () {
        Ext.MessageBox.prompt(t('classificationstore_mbx_entercollection_title'), t('classificationstore_mbx_entergroup_prompt'),
            this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        value = value.trim();
        if (button == "ok" && value.length > 1) {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_classificationstore_createcollection'),
                method: 'POST',
                params: {
                    name: value,
                    storeId: this.storeConfig.id
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    if(!data || !data.success) {
                        Ext.Msg.alert(t("error"), t("classificationstore_error_addcollection_msg"));
                    } else {
                        this.collectionsStore.reload({
                                callback: function() {
                                    var rowIndex = this.collectionsStore.find('name', value);
                                    if (rowIndex != -1) {
                                        var sm = this.grid.getSelectionModel();
                                        sm.select(rowIndex);
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
                    colData.storeId = this.storeConfig.id;
                    colData.colId = this.collectionId;
                    var tmpId = this.collectionId + "-" + colData.groupId;

                    var match = this.relationsStore.findExact("id", tmpId);
                    if (match == -1) {
                        this.relationsStore.add(colData);
                    }
                }
            }
        }
    },

    requestPending: function() {
        // nothing to do
    }

});

