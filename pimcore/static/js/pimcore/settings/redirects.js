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

pimcore.registerNS("pimcore.settings.redirects");
pimcore.settings.redirects = Class.create({

    initialize: function () {

        this.getTabPanel();

    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_redirects");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_redirects",
                title: t("redirects"),
                iconCls: "pimcore_icon_redirects",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_redirects");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("redirects");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/settings/redirects'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'source', allowBlank: false},
            {name: 'target', allowBlank: false},
            {name: 'statusCode', allowBlank: true},

            {name: 'priority', type:'int' ,allowBlank: true}
        ]);
        var writer = new Ext.data.JsonWriter();

        this.store = new Ext.data.Store({
            id: 'redirects_store',
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




        var typesColumns = [
            {header: t("source"), width: 200, sortable: true, dataIndex: 'source', editor: new Ext.form.TextField({})},
            {header: t("target"), width: 200, sortable: false, dataIndex: 'target', editor: new Ext.form.TextField({}), css: "background: url(/pimcore/static/img/icon/drop-16.png) right 2px no-repeat;"},
            {header: t("type"), width: 50, sortable: true, dataIndex: 'statusCode', editor: new Ext.form.ComboBox({
                store: [
                    ["301", "Moved Permanently (301)"],
                    ["307", "Temporary Redirect (307)"],
                    ["300", "Multiple Choices (300)"],
                    ["302", "Found (302)"],
                    ["303", "See Other (303)"]
                ],
                mode: "local",
                typeAhead: false,
                editable: false,
                forceSelection: true,
                triggerAction: "all"
            })},
            {header: t("priority"), width: 50, sortable: true, dataIndex: 'priority', editor: new Ext.form.ComboBox({
                store: [
                    [1, "1 - " + t("lowest")],
                    [2, 2],
                    [3, 3],
                    [4, 4],
                    [5, 5],
                    [6, 6],
                    [7, 7],
                    [8, 8],
                    [9, 9],
                    [10, "10 - " + t("highest")],
                    [99, "99 - " + t("override_all")]
                ],
                mode: "local",
                typeAhead: false,
                editable: false,
                forceSelection: true,
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
                        this.updateRows();
                    }.bind(this)
                }]
            }
        ];

        this.grid = new Ext.grid.EditorGridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
			columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            stripeRows: true,

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
                forceFit: true,
                listeners: {
                    rowupdated: this.updateRows.bind(this),
                    refresh: this.updateRows.bind(this)
                }
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));


        return this.grid;
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid3-row");

        for (var i = 0; i < rows.length; i++) {

            var dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    var rec = this.grid.getStore().getAt(myRowIndex);
                    rec.set("target", data.node.attributes.path);

                    this.updateRows();

                    return true;
                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        var u = new this.grid.store.recordType({
            name: t('/')
        });
        this.grid.store.insert(0, u);

		this.updateRows();
    },

    onDelete: function () {
        var rec = this.grid.getSelectionModel().getSelectedCell();
        if (!rec) {
            return false;
        }

        this.grid.store.removeAt(rec[0]);


        this.updateRows();
    }

});