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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.settings.staticroutes");
pimcore.settings.staticroutes = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_staticroutes");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_staticroutes",
                title: t("static_routes"),
                iconCls: "pimcore_icon_routes",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_staticroutes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("staticroutes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/settings/staticroutes'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'name', allowBlank: true},
            {name: 'pattern', allowBlank: false},
            {name: 'reverse', allowBlank: true},
            {name: 'module', allowBlank: true},
            {name: 'controller', allowBlank: true},
            {name: 'action', allowBlank: true},
            {name: 'variables', allowBlank: true},
            {name: 'defaults', allowBlank: true},
            {name: 'priority',type:'int',allowBlank: true}
        ]);
        var writer = new Ext.data.JsonWriter();


        var itemsPerPage = 20;

        this.store = new Ext.data.Store({
            id: 'staticroutes_store',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            baseParams: {
                limit: itemsPerPage,
                filter: ""
            }, 
            listeners: {
                write : function(store, action, result, response, rs) {
                }
            }
        });
        this.store.load();


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        this.store.baseParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
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
            width: 50,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        this.editor = new Ext.ux.grid.RowEditor();

        var typesColumns = [
            {header: t("name"), width: 50, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("pattern"), width: 100, sortable: true, dataIndex: 'pattern', editor: new Ext.form.TextField({})},
            {header: t("reverse"), width: 100, sortable: true, dataIndex: 'reverse', editor: new Ext.form.TextField({})},
            {header: t("module"), hidden: true, width: 50, sortable: false, dataIndex: 'module', editor: new Ext.form.TextField({})},
            {header: t("controller"), width: 50, sortable: false, dataIndex: 'controller', editor: new Ext.form.TextField({})},
            {header: t("action"), width: 50, sortable: false, dataIndex: 'action', editor: new Ext.form.TextField({})},
            {header: t("variables"), width: 50, sortable: false, dataIndex: 'variables', editor: new Ext.form.TextField({})},
            {header: t("defaults"), width: 50, sortable: false, dataIndex: 'defaults', editor: new Ext.form.TextField({})},
            {header: t("priority"), width: 50, sortable: true, dataIndex: 'priority', editor: new Ext.form.ComboBox({
                store: [1,2,3,4,5,6,7,8,9,10],
                mode: "local",
                triggerAction: "all"
            })},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            }
        ];

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            plugins: [this.editor],
            columns : typesColumns,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.pagingtoolbar,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },
                '-',
                {
                    text: t('delete'),
                    handler: this.onDelete.bind(this),
                    iconCls: "pimcore_icon_delete"
                },
                '-',"->",{
                  text: t("filter") + "/" + t("search"),
                  xtype: "tbtext",
                  style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },


    onAdd: function (btn, ev) {
        var u = new this.grid.store.recordType({
            name: ""
        });
        this.editor.stopEditing();
        this.grid.store.insert(0, u);
        this.editor.startEditing(0);
    },

    onDelete: function () {
        var rec = this.grid.getSelectionModel().getSelected();
        if (!rec) {
            return false;
        }
        this.grid.store.remove(rec);
    }

});