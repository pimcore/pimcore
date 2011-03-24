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
            {name: 'controller', allowBlank: true},
            {name: 'action', allowBlank: true},
            {name: 'variables', allowBlank: true},
            {name: 'defaults', allowBlank: true},
            {name: 'priority',type:'int',allowBlank: true}
        ]);
        var writer = new Ext.data.JsonWriter();

        this.store = new Ext.data.Store({
            id: 'staticroutes_store',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            listeners: {
                write : function(store, action, result, response, rs) {
                }
            }
        });
        this.store.load();

        this.editor = new Ext.ux.grid.RowEditor();

        var typesColumns = [
            {header: t("name"), width: 50, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("pattern"), width: 100, sortable: true, dataIndex: 'pattern', editor: new Ext.form.TextField({})},
            {header: t("reverse"), width: 100, sortable: true, dataIndex: 'reverse', editor: new Ext.form.TextField({})},
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
                '-'
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