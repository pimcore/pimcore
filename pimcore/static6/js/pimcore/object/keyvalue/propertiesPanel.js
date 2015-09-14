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

pimcore.registerNS("pimcore.object.keyvalue.propertiespanel");
pimcore.object.keyvalue.propertiespanel = Class.create({

    initialize: function () {
    },

    getPanel: function () {
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t("keyValue_properties"),
                border: false,
                layout: "fit",
                region: "center"
            });

            this.createGrid();
        }

        this.layout.on("activate", this.panelActivated.bind(this));

        return this.layout;
    },

    panelActivated: function() {
        if (this.store) {
            this.store.reload();
        }
    },

    createGrid: function(response) {
        this.fields = ['id', 'name', 'description', 'type', 'unit', 'group', 'groupdescription','possiblevalues',
            'translator', 'mandatory','creationDate', 'modificationDate'];

        var readerFields = [];
        for (var i = 0; i < this.fields.length; i++) {
            readerFields.push({name: this.fields[i]});
        }

        var itemsPerPage = 20;
        var url = "/admin/key-value/properties?";

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            readerFields,
            itemsPerPage
        );
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);


        var listeners = {};

        this.store.addListener("exception", function (conn, mode, action, request, response, store) {
            if(action == "update") {
                Ext.MessageBox.alert(t('error'), t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                this.store.rejectChanges();
            }
        }.bind(this));


        var gridColumns = [];

        gridColumns.push({header: "ID", width: 40, sortable: true, dataIndex: 'id'});
        gridColumns.push({header: t("name"), width: 200, sortable: true, dataIndex: 'name',editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({header: t("description"), flex: 30, sortable: true, dataIndex: 'description',editor: new Ext.form.TextField({}), filter: 'string'});
        gridColumns.push({header: t("type"), width: 100, sortable: true, dataIndex: 'type',
            editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["text","number","bool","select","translated","translatedSelect", "range"]

            }), filter: 'string'});


        var mandatory = Ext.create('Ext.grid.column.Check', {
            header: t("mandatory"),
            dataIndex: "mandatory",
            width: 50
        });

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 40,
            items: [
                {
                    tooltip: t("keyvalue_detailed_configuration"),
                    icon: "/pimcore/static6/img/icon/building_edit.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var id = data.data.id;

                        var type = data.data.type;
                        var possiblevalues = data.data.possiblevalues;

                        if (type == 'select' || type == 'translatedSelect') {
                            var specialConfigWindow = new pimcore.object.keyvalue.specialconfigwindow(
                                Ext.util.JSON.decode(possiblevalues), data.id, this);
                            specialConfigWindow.show();
                        } else if (type == 'translated') {
                            var translatorConfigWindow = new pimcore.object.keyvalue.translatorconfigwindow(
                                data.id, this, data.data.translator).show();
                        } else {
                            alert(t("keyvalue_define_select_values_error"));
                        }
                    }.bind(this)
                }
            ]
        });

        gridColumns.push({header: t("keyvalue_unit"), width: 100, sortable: true, dataIndex: 'unit',
            editor: new Ext.form.TextField({})});
        gridColumns.push({header: t("keyvalue_col_groupid"), width: 40, sortable: true, dataIndex: 'group'});
        gridColumns.push({header: t("keyvalue_col_groupdescription"), width: 200, sortable: false,
            dataIndex: 'groupdescription', filter: 'string'});

        gridColumns.push(mandatory);

        var dateRenderer = function(d) {
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
            width: 40,
            items: [
                {
                    tooltip: t('keyvalue_find_group'),
                    icon: "/pimcore/static6/img/icon/magnifier.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var id = data.data.id;
                        this.selectData = data;
                        this.showSearchWindow();
                    }.bind(this)
                }
            ]
        });


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
                            url: "/admin/key-value/deleteproperty",
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

        gridColumns.push({header: t("keyvalue_col_translator"), width: 40, sortable: true, dataIndex: 'translator'});

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var plugins = ['gridfilters', this.cellEditing];

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

        this.grid = Ext.create('Ext.grid.Panel' ,gridConfig);

        this.store.load();

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();
    },


    applyTranslatorConfig: function(keyid, value) {
        var data = this.store.getById(keyid);
        data.set("translator", value);
    },

    applyDetailedConfig: function(keyid, value) {
        var data = this.store.getById(keyid);
        data.set("possiblevalues",  Ext.util.JSON.encode(value));
        //  this.store.save();
    },

    onAdd: function () {
        Ext.MessageBox.prompt(t('keyvalue_mbx_enterkey_title'), t('keyvalue_mbx_enterkey_prompt'),
            this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        value = value.trim();
        if (button == "ok" && value.length > 1) {
            Ext.Ajax.request({
                url: "/admin/key-value/addproperty",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    if(!data || !data.success) {
                        Ext.Msg.alert(t("keyvalue_error_addkey_title"), t("keyvalue_error_addkey_msg"));
                    } else {

                        this.store.reload({
                                callback: function() {
                                    var rowIndex = this.store.find('name', value);
                                    // alert(rowIndex);
                                    if (rowIndex != -1) {
                                        var sm = this.grid.getSelectionModel();
                                        sm.select(rowIndex);
                                    }

                                    var lastOptions = this.store.lastOptions;
                                    Ext.apply(lastOptions.params, {
                                        overrideSort: "false"
                                    });

//                                    this.store.setBaseParam("overrideSort", "false");
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

    showSearchWindow: function() {

        this.searchfield = new Ext.form.TextField({
            width: 300,
            style: "float: left;",
            fieldLabel: t("search")
        });

        var resultPanel = this.getResultPanel();

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            layout: "fit",
            resizable: false,
            title: t("keyvalue_select_group"),
            items: [resultPanel],
            tbar: [this.searchfield,
                {
                    xtype: "button",
                    text: t("search"),
                    icon: "/pimcore/static6/img/icon/magnifier.png",
                    handler: function () {
                        var formValue = this.searchfield.getValue();

                        var filter = [{
                            "field": "description",
                            "value" :formValue},
                            {
                                "field": "name",
                                "value" :formValue}
                        ];
                        this.groupStore.baseparams = {};

                        this.groupStore.getProxy().setExtraParam("filter", Ext.util.JSON.encode(filter));

                        this.groupPagingtoolbar.moveFirst();
                    }.bind(this)
                }],
            bbar: ["->",{
                xtype: "button",
                text: t("cancel"),
                icon: "/pimcore/static6/img/icon/cancel.png",
                handler: function () {
                    this.searchWindow.close();
                }.bind(this)
            },{
                xtype: "button",
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    var data = this.getData();
                    if (data) {
                        this.selectData.set("group", data.id);
                        this.selectData.set("groupdescription", data.description);
                    } else {
                        this.selectData.set("group", null);
                        this.selectData.set("groupdescription", null);
                    }
                    this.store.save();
                    this.searchWindow.close();
                }.bind(this)
            }],
            plain: true
        });


        this.searchWindow.show();
    },

    getResultPanel: function () {
        this.resultPanel = new Ext.Panel({
            layout: "fit"
        });

        this.getGridPanel();
        return this.resultPanel;
    },

    getData: function () {
        var selected = this.groupGridPanel.getSelectionModel().getSelected();
        if(selected) {
            return selected.data;
        }
        return null;
    },


    getGridPanel: function() {

        this.groupFields = ['id', 'name', 'description'];

        var readerFields = [];
        for (var i = 0; i < this.groupFields.length; i++) {
            readerFields.push({name: this.groupFields[i], allowBlank: true});
        }

        var gridColumns = [];
        gridColumns.push({header: "ID", width: 40, sortable: true, dataIndex: 'id'});
        gridColumns.push({header: t("name"), width: 200, sortable: true, dataIndex: 'name'});
        gridColumns.push({header: t("description"), width: 340, sortable: true, dataIndex: 'description'});



        var proxy = {
            type: 'ajax',
            url: "/admin/key-value/groups",
            reader: {
                type: 'json',
                totalProperty: 'total',
                successProperty: 'success',
                rootProperty: 'data'
            }
        };

        this.groupStore = new Ext.data.Store({
            remoteSort: true,
            proxy: proxy,
            fields: readerFields
        });

        this.groupPagingtoolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.groupStore,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("keyvalue_no_groups")
        });

        this.groupGridPanel = new Ext.grid.GridPanel({
            store: this.groupStore,
            border: false,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            stripeRows: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.groupPagingtoolbar,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);

                    this.selectData.set("group", data.id);
                    this.selectData.set("groupdescription", data.data.description);
                    this.store.save();
                    this.searchWindow.close();

                }.bind(this)
            }
        });

        this.groupStore.load();

        this.resultPanel.removeAll();
        this.resultPanel.add(this.groupGridPanel);
        this.resultPanel.updateLayout();
    }
});