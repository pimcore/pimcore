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

        var url = "/admin/classificationstore/collection-relations?";
        var proxy = {
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            api: {
                create  : url + "xaction=create",
                read    : url + "xaction=read",
                update  : url + "xaction=update",
                destroy : url + "xaction=destroy"
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
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

        gridColumns.push({header: t("group_id"), flex: 60, sortable: true, dataIndex: 'groupId', filter: 'string'});
        gridColumns.push({header: t("name"), flex: 200, sortable: true, dataIndex: 'groupName', filter: 'string'});
        gridColumns.push({header: t("description"), flex: 200, sortable: true, dataIndex: 'groupDescription', filter: 'string'});

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static6/img/icon/cross.png",
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
        

        var gridConfig = {
            frame: false,
            store: this.relationsStore,
            border: true,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
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
        this.groupsFields = ['id', 'name', 'description', 'creationDate', 'modificationDate'];

        var readerFields = [];
        for (var i = 0; i < this.groupsFields.length; i++) {
            readerFields.push({name: this.groupsFields[i], allowBlank: true});
        }

        var url = "/admin/classificationstore/collections?";
        var proxy = {
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            api: {
                create  : url + "xaction=create",
                read    : url + "xaction=read",
                update  : url + "xaction=update",
                destroy : url + "xaction=destroy"
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
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
            remoteFilter: true
        });


        var gridColumns = [];

        gridColumns.push({header: "ID", flex: 60, sortable: true, dataIndex: 'id', filter: 'string'});
        gridColumns.push({header: t("name"), flex: 200, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({header: t("description"), flex: 300, sortable: true, dataIndex: 'description', editor: new Ext.form.TextField({}), filter: 'string'});

        var dateRenderer =  function(d) {
            if (d !== undefined) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            } else {
                return "";
            }
        };


        gridColumns.push(
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false, width: 130,
                hidden: true,
                renderer: dateRenderer            }
        );

        gridColumns.push(
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false, width: 130,
                hidden: true,
                renderer: dateRenderer
            }
        );

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static6/img/icon/cross.png",
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

        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            //clicksToEdit: 2
        });

        var plugins = ['gridfilters', cellEditing];

        var gridConfig = {
            frame: false,
            store: this.collectionsStore,
            border: true,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
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

                rowclick: function(grid, record, tr, rowIndex, e, eOpts ) {
                    var record = this.collectionsStore.getAt(rowIndex);
                    var collectionId = record.data.id;
                    var collectionName = record.data.name;

                    this.collectionId = collectionId;

                    this.relationsPanel.setTitle(t("relations") + " - "  + t("collection") +  " " + record.data.id + " - " + collectionName);
                    this.relationsPanel.enable();
                    var proxy = this.relationsStore.getProxy();
                    proxy.setExtraParam("colId", collectionId);
                    this.relationsStore.reload();
                    this.relationsGrid.show();

                }.bind(this)
            }
        } ;

        this.grid = Ext.create('Ext.grid.Panel', gridConfig);

        this.collectionsStore.load();

        return this.grid
    },


    onAddGroup: function() {
        var window = new pimcore.object.classificationstore.keySelectionWindow(this, true, false, false);
        window.show();
    },

    onAdd: function () {
        Ext.MessageBox.prompt(t('classificationstore_mbx_entercollection_title'), t('classificationstore_mbx_entergroup_prompt'),
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

