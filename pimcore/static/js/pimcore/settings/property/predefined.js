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

pimcore.registerNS("pimcore.settings.property.predefined");
pimcore.settings.property.predefined = Class.create({

    initialize: function () {
        this.getTabPanel();
    },
    
    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("predefined_properties");
    },
    
    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "predefined_properties",
                title: t("predefined_properties"),
                iconCls: "pimcore_icon_properties",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("predefined_properties");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("predefined_properties");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/settings/properties'
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'name', allowBlank: false},
            {name: 'key', allowBlank: false},
            {name: 'type', allowBlank: false},
            {name: 'data', allowBlank: true},
            {name: 'config', allowBlank: true},
            {name: 'ctype', allowBlank: false},
            {name: 'inheritable', allowBlank: true}
        ]);

        var writer = new Ext.data.JsonWriter();

        var store = new Ext.data.Store({
            id: 'properties',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            listeners: {
                write : function(store, action, result, response, rs) {
                }
            }
        });

        var propertiesColumns = [
            {header: t("name"), width: 100, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("key"), width: 50, sortable: false, dataIndex: 'key', editor: new Ext.form.TextField({})},
            {header: t("type"), width: 50, sortable: false, dataIndex: 'type', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["text","document","asset","object","bool","select"]
            })},
            {header: t("value"), width: 50, sortable: false, dataIndex: 'data', editor: new Ext.form.TextField({})},
            {header: t("configuration"), width: 50, sortable: false, dataIndex: 'config', editor: new Ext.form.TextField({})},
            {header: t("content_type"), width: 50, sortable: false, dataIndex: 'ctype', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["document","asset","object"]
            })},
            {header: t("inheritable"), width: 50, sortable: false, dataIndex: 'inheritable', editor: new Ext.form.Checkbox({})},
            {
                xtype: 'actioncolumn',
                width: 10,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },{
                xtype: 'actioncolumn',
                width: 10,
                items: [{
                    tooltip: t('translate'),
                    icon: "/pimcore/static/img/icon/translation.png",
                    handler: function(grid, rowIndex){
                        var rec = grid.getStore().getAt(rowIndex);
                        try {
                            pimcore.globalmanager.get("translationadminmanager").activate(rec.data.name);
                        }
                        catch (e) {
                            pimcore.globalmanager.add("translationadminmanager", new pimcore.settings.translation.admin(rec.data.name));
                        }
                    }.bind(this)
                }]
            }
        ];

        store.load();


        this.editor = new Ext.ux.grid.RowEditor();

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: store,
            columnLines: true,
            stripeRows: true,
            plugins: [this.editor],
            columns : propertiesColumns,
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
            name: t('new_property'),
            key: "new_key",
            ctype: "document"
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