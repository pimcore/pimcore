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

pimcore.registerNS("pimcore.object.classificationstore.groupsPanel");
pimcore.object.classificationstore.groupsPanel = Class.create({

    initialize: function () {

    },

    getPanel: function () {
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t("classificationstore_group_definition"),
                iconCls: "pimcore_icon_keys",
                border: false,
                layout: "border",
                items: [
                    this.createGroupsGrid(),
                    this.createRelationsGrid()
                ]

            });
        }

        return this.layout;
    },


    createRelationsGrid: function() {
        this.relationsFields = ['id', 'keyId', 'groupId', 'keyName', 'keyDescription'];

        var readerFields = [];
        for (var i = 0; i < this.relationsFields.length; i++) {
            readerFields.push({name: this.relationsFields[i], allowBlank: true, type: 'string'});
        }

        var url = "/admin/classificationstore/relations?";
        var proxy = {
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data',
                idProperty: 'id'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            api: {
                create  : url + "xaction=create",
                read    : url + "xaction=read",
                update  : url + "xaction=update",
                destroy : url + "xaction=destroy"
            }
        };

        var listeners = {};

        listeners.write = function(store, action, result, response, rs) {};
        listeners.exception = function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), response);
                this.relationsStore.rejectChanges();
            }
        }.bind(this);

        this.relationsStore = new Ext.data.Store({
            autoSync: true,
            proxy: proxy,
            fields: readerFields,
            listeners: listeners
        });

        var gridColumns = [];

        gridColumns.push({header: t("key_id"), flex: 60, sortable: true, dataIndex: 'keyId', filter: 'string'});
        gridColumns.push({header: t("name"), flex: 200, sortable: true, dataIndex: 'keyName', filter: 'string'});
        gridColumns.push({header: t("description"), flex: 200, sortable: true, dataIndex: 'keyDescription', filter: 'string'});

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
                        var keyId = data.data.keyId;
                        var groupId = data.data.groupId;

                        Ext.Ajax.request({
                            url: "/admin/classificationstore/delete-relation",
                            params: {
                                keyId: keyId,
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
            emptyMsg: t("classificationstore_group_empty")
        });


        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 2
        });

        var plugins = ['gridfilters', cellEditing];

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
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.relationsPagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAddKey.bind(this),
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


    createGroupsGrid: function(response) {
        this.groupsFields = ['id', 'parentId', 'name', 'description', 'creationDate', 'modificationDate', 'sorter'];

        var readerFields = [];
        for (var i = 0; i < this.groupsFields.length; i++) {
            readerFields.push({name: this.groupsFields[i], allowBlank: true});
        }

        var url = "/admin/classificationstore/groups?";
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
                this.groupsStore.rejectChanges();
            }
        }.bind(this);


        this.groupsStore = new Ext.data.Store({
            autoSync: true,
            proxy: proxy,
            fields: readerFields,
            listeners: listeners,
            remoteFilter: true
        });



        var gridColumns = [];

        gridColumns.push({header: "ID", width: 60, sortable: true, dataIndex: 'id', filter: 'string'});
        gridColumns.push({header: t("parent_id"), width: 160, sortable: true, dataIndex: 'parentId', hidden: true, editor: new Ext.form.TextField({})});
        gridColumns.push({header: t("name"), flex: 200, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({header: t("description"), flex: 300, sortable: true, dataIndex: 'description', editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({header: t('sorter'), width: 100, sortable: true, dataIndex: 'sorter',
            tooltip: t("classificationstore_tooltip_sorter"),
            editor: new Ext.form.NumberField()
        });

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
                renderer: dateRenderer
            }
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
                            url: "/admin/classificationstore/deletegroup",
                            params: {
                                id: id
                            },
                            success: function (response) {
                                this.groupsStore.reload();
                            }.bind(this)});
                    }.bind(this)
                }
            ]
        });

        this.groupsPagingtoolbar = new Ext.PagingToolbar({
            pageSize: 15,
            store: this.groupsStore,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("classificationstore_no_groups")
        });


        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            //clicksToEdit: 2
        });

        var plugins = ['gridfilters', cellEditing];

        var gridConfig = {
            frame: false,
            store: this.groupsStore,
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
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.groupsPagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ],
            listeners: {

                rowclick: function(grid, record, tr, rowIndex, e, eOpts ) {
                    var record = this.groupsStore.getAt(rowIndex);
                    var groupId = record.data.id;
                    var groupName = record.data.name;

                    this.groupId = groupId;

                    this.relationsPanel.setTitle(t("relations") + " - "  + t("group") +  " " + record.data.id + " - " + groupName);
                    this.relationsPanel.enable();
                    this.relationsStore.getProxy().setExtraParam("groupId", groupId);
                    this.relationsStore.reload();
                    this.relationsGrid.show();

                }.bind(this)
            }
        } ;

        this.grid =  Ext.create('Ext.grid.Panel', gridConfig);

        this.groupsStore.load();

        return this.grid
    },

    onAddKey: function() {
        var window = new pimcore.object.classificationstore.keySelectionWindow(this, false, true, false);
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
                url: "/admin/classificationstore/create-group",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    if(!data || !data.success) {
                        Ext.Msg.alert(t("classificationstore_error_addgroup_title"), t("classificationstore_error_addgroup_msg"));
                    } else {
                        this.groupsStore.reload({
                                callback: function() {
                                    var rowIndex = this.groupsStore.find('name', value);
                                    if (rowIndex != -1) {
                                        var sm = this.grid.getSelectionModel();
                                        sm.select(rowIndex);
                                    }

                                    var lastOptions = this.groupsStore.lastOptions;
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

    handleAddKeys: function (response) {
        var data = Ext.decode(response.responseText);

        if(data && data.success) {
            for (var i=0; i < data.data.length; i++) {
                var keyDef = data.data[i];

                var colData = {};
                colData.keyId = keyDef.id;
                colData.keyName = keyDef.name;
                colData.keyDescription = keyDef.description;
                colData.groupId = this.groupId;

                var tempId = this.groupId + "-" + colData.keyId;

                var match = this.relationsStore.findExact("id" , tempId);
                if (match == -1) {
                    this.relationsStore.add(colData);
                }
            }
        }
    },

    requestPending: function() {
        // nothing to do
    }

});

