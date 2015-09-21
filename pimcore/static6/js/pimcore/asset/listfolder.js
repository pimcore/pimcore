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

pimcore.registerNS("pimcore.asset.listfolder");
pimcore.asset.listfolder = Class.create({

    onlyDirectChildren: false,

    initialize: function (element) {
        this.element = element;

    },


    getLayout: function () {
        this.filterField = new Ext.form.TextField({
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            value: this.preconfiguredFilter,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.baseParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });


        var proxy = new Ext.data.HttpProxy({
            type: 'ajax',
            url: "/admin/asset/grid-proxy/",
            reader: {
                type: 'json',
                rootProperty: 'data',
                totalProperty: 'total',
                successProperty: 'success',
                idProperty: 'key'
            },
            extraParams: {
                limit: itemsPerPage,
                folderId: this.element.data.id
            }
        });

        var readerFields = [
            {name: 'id', allowBlank: true},
            {name: 'fullpath', allowBlank: true},
            {name: 'type', allowBlank: true},
            {name: 'creationDate', allowBlank: true},
            {name: 'modificationDate', allowBlank: true},
            {name: 'size', allowBlank: true},
            {name: 'idPath', allowBlank: true}
        ];

        this.selectionColumn = new Ext.selection.CheckboxModel();

        var typesColumns = [
            this.selectionColumn,
            {header: t("id"), sortable: true, dataIndex: 'id', editable: false, flex: 40},
            {header: t("filename"), sortable: true, dataIndex: 'fullpath', editable: false, flex: 100, filter: 'string'},
            {header: t("type"), sortable: true, dataIndex: 'type', editable: false, flex: 50, filter: 'string'}

        ];


        typesColumns.push({header: t("creationDate"), width: 150, sortable: true, dataIndex: 'creationDate', editable: false, filter: 'date',
                                                                                renderer: function(d) {
            var date = new Date(d * 1000);
            return Ext.Date.format(date, "Y-m-d H:i:s");
        }});
        typesColumns.push({header: t("modificationDate"), width: 150, sortable: true, dataIndex: 'modificationDate', editable: false, filter: 'date',
        renderer: function(d) {
            var date = new Date(d * 1000);
            return Ext.Date.format(date, "Y-m-d H:i:s");
        }});

        typesColumns.push(
            {header: t("size"), sortable: false, dataIndex: 'size', editable: false, filter: 'string'}
        );

        var itemsPerPage = 20;
        this.store = new Ext.data.Store({
            proxy: proxy,
            remoteSort: true,
            filter: this.filterField,
            fields: readerFields
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_assets_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 80,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.store.setPageSize(intval(rec.data.field1));
                    this.store.getProxy().extraParams.limit = rec.data.field1;
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();

                }.bind(this)
            }
        }));

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            boxLabel: t("only_children"),
            listeners: {
                "change" : function (field, checked) {
                    this.grid.filters.clearFilters();

                    this.store.getProxy().setExtraParam("only_direct_children", checked);

                    this.onlyDirectChildren = checked;
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            title: "List",
            iconCls: "pimcore_icon_table_tab",
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            columns : typesColumns,
            plugins: ['gridfilters'],
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            selModel: this.selectionColumn,
            viewConfig: {
                forceFit: true
            },
            listeners: {
                activate: function() {
                    this.store.getProxy().setExtraParam("only_direct_children", this.onlyDirectChildren);
                    this.store.load();
                }.bind(this),
                rowdblclick: function(grid, record, tr, rowIndex, e, eOpts ) {
                    var data = this.store.getAt(rowIndex);
                    pimcore.helpers.openAsset(data.get("id"), data.get("type"));

                }.bind(this)
            },
            tbar: [
                "->"
                ,this.checkboxOnlyDirectChildren
                ]
        });

        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        return this.grid;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        //$(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( {
        //    backgroundColor: '#fff' }, 400);

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        var selModel = grid.getSelectionModel();
        var selectedRows = selModel.getSelection();

        if (selectedRows.length <= 1) {

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    pimcore.helpers.openAsset(data.data.id, data.data.type);
                }.bind(this, data)
            }));
            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: function (data) {
                    try {
                        try {
                            Ext.getCmp("pimcore_panel_tree_assets").expand();
                            var tree = pimcore.globalmanager.get("layout_asset_tree");
                            pimcore.helpers.selectPathInTree(tree.tree, data.data.idPath);
                        } catch (e) {
                            console.log(e);
                        }

                    } catch (e2) { console.log(e2); }
                }.bind(grid, data)
            }));
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var store = this.getStore();
                    pimcore.helpers.deleteAsset(data.data.id, function() {
                        this.getStore().reload();
                        var tree = pimcore.globalmanager.get("layout_asset_tree");
                        var treePanel = tree.tree;
                        tree.refresh(treePanel.getRootNode());
                    }.bind(this));
                }.bind(grid, data)
            }));
        } else {
            menu.add(new Ext.menu.Item({
                text: t('open_selected'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        var data = selectedRows[i].data;
                        pimcore.helpers.openAsset(data.id, data.type);
                    }
                }.bind(this, data)
            }));

            menu.add(new Ext.menu.Item({
                text: t('delete_selected'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var ids = [];
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        ids.push(selectedRows[i].data.id);
                    }
                    ids = ids.join(',');

                    pimcore.helpers.deleteAsset(ids, function() {
                        this.getStore().reload();

                        var tree = pimcore.globalmanager.get("layout_asset_tree").tree;
                        tree.getStore().load({
                            node: tree.getRootNode()
                        });
                    }.bind(this)
                    );
                }.bind(grid, data)
            }));
        }

        e.stopEvent();
        menu.showAt(e.getXY());
    }

});

