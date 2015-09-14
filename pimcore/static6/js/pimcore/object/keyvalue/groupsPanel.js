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

        var itemsPerPage = 20;
        var url =  "/admin/key-value/groups?";

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            readerFields,
            itemsPerPage
        );
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);


        this.store.addListener("exception", function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                this.store.rejectChanges();
            }
        }.bind(this));

        var gridColumns = [];

        gridColumns.push({header: "ID", width: 40, sortable: true, dataIndex: 'id'});
        gridColumns.push({header: t("name"), width: 200, sortable: true, dataIndex: 'name',editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({header: t("description"), width: 300, sortable: true, dataIndex: 'description', editor: new Ext.form.TextField({}), filter: 'string'});

        var dateRenderer =  function(d) {
            if (d !== undefined) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            } else {
                return "";
            }
        };

        gridColumns.push(
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false, width: 150,
                hidden: true,
                renderer: dateRenderer
            }
        );

        gridColumns.push(
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false, width: 150,
                hidden: true,
                renderer: dateRenderer            }
        );

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 40,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static6/img/icon/cross.png",
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


        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var plugins = ['pimcore.gridfilters', this.cellEditing];

        var gridConfig = {
            frame: false,
            store: this.store,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            plugins: plugins,
            stripeRows: true,
            trackMouseOver: true,
            viewConfig: {
                forceFit: false
            },
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ]
        } ;

        this.grid = Ext.create('Ext.grid.Panel', gridConfig);

        this.store.load();

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();
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
                                if (rowIndex != -1) {
                                    var sm = this.grid.getSelectionModel();
                                    sm.select(rowIndex);
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
    }

});

