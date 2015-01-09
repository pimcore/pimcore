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

pimcore.registerNS("pimcore.object.keyvalue.groupspanel");
pimcore.object.keyvalue.groupspanel = Class.create({

    initialize: function () {

    },

    getPanel: function () {
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t("keyValue_groups"),
                border: false,
                layout: "fit",
                region: "center"
            });

            this.createGrid();
        }

        return this.layout;
    },

    createGrid: function(response) {
        this.fields = ['id', 'name', 'description', 'creationDate', 'modificationDate'];

        var readerFields = [];
        for (var i = 0; i < this.fields.length; i++) {
            readerFields.push({name: this.fields[i], allowBlank: true});
        }


        var proxy = new Ext.data.HttpProxy({
            url: "/admin/key-value/groups",
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
                this.store.rejectChanges();
            }
        }.bind(this);


        this.store = new Ext.data.Store({
            restful: false,
            idProperty: 'id',
            remoteSort: true,
            proxy: proxy,
            reader: reader,
            writer: writer,
            listeners: listeners,
            baseParams: this.baseParams
        });



        var gridColumns = [];

        gridColumns.push({header: "ID", width: 40, sortable: true, dataIndex: 'id'});
        gridColumns.push({header: t("name"), width: 200, sortable: true, dataIndex: 'name',editor: new Ext.form.TextField({})});
        gridColumns.push({header: t("description"), width: 200, sortable: true, dataIndex: 'description', editor: new Ext.form.TextField({})});


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

                          Ext.Ajax.request({
                                url: "/admin/key-value/deletegroup",
                                params: {
                                    id: id
                                },
                                success: function (response) {
                                    this.store.reload();
                                }.bind(this)});
                    }.bind(this)
                }
            ]
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 15,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            //TODO translate
            emptyMsg: t("keyvalue_no_groups")
        });

        var selectFilterFields;
        var configuredFilters = [{
            type: "string",
            dataIndex: "name"
        },{
            type: "string",
            dataIndex: "description"
        }];
        this.gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: configuredFilters
        });

        var plugins = [this.gridfilters];

        var gridConfig = {
            frame: false,
            store: this.store,
            border: true,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            plugins: plugins,
            stripeRows: true,
            trackMouseOver: true,
            viewConfig: {
                forceFit: false
            },
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.pagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ],
            listeners: {
                rowdblclick: function (grid, rowIndex, ev) {

                }.bind(this)
            }
        } ;

        this.grid = new Ext.grid.EditorGridPanel(gridConfig);

        this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.grid.on("sortchange", function(grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));

        this.store.load();

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.doLayout();
    },

    updateGridHeaderContextMenu: function(grid) {
        // not needed for now.
    },

    onAdd: function () {
        Ext.MessageBox.prompt(t('keyvalue_mbx_entergroup_title'), t('keyvalue_mbx_entergroup_prompt'),
                                                this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        value = value.trim();
        if (button == "ok" && value.length > 1) {
            Ext.Ajax.request({
                url: "/admin/key-value/addgroup",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    if(!data || !data.success) {
                        Ext.Msg.alert(t("keyvalue_error_addgroup_title"), t("keyvalue_error_addgroup_msg"));
                    } else {
                        this.store.reload({
                                callback: function() {
                                var rowIndex = this.store.find('name', value);
                                // alert(rowIndex);
                                if (rowIndex != -1) {
                                    var sm = this.grid.getSelectionModel();
                                    sm.selectRow(rowIndex);
                                    // alert(sm);

                                }

                                var lastOptions = this.store.lastOptions;
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
            Ext.Msg.alert(t("keyvalue_configuration"), t("keyvalue_invalidname"));
        }
    },


    onRowContextmenu: function (grid, rowIndex, event) {
        // no context menu
    }

});

