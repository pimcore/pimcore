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

pimcore.registerNS("pimcore.settings.properties.predefined");
pimcore.settings.properties.predefined = Class.create({

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
            {name: 'description', allowBlank: true},
            {name: 'key', allowBlank: false},
            {name: 'type', allowBlank: false},
            {name: 'data', allowBlank: true},
            {name: 'config', allowBlank: true},
            {name: 'ctype', allowBlank: false},
            {name: 'inheritable', allowBlank: true}
        ]);

        var writer = new Ext.data.JsonWriter();

        var itemsPerPage = 20;

        this.store = new Ext.data.Store({
            id: 'properties',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            remoteSort: true,
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


        var inheritableCheck = new Ext.grid.CheckColumn({
            header: t("inheritable"),
            dataIndex: "inheritable",
            width: 50
        });

        var propertiesColumns = [
            {header: t("name"), width: 100, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("description"), sortable: true, dataIndex: 'description', editor: new Ext.form.TextArea({}), renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                if(empty(value)) {
                    return "";
                }
                return nl2br(value);
            }
            },
            {header: t("key"), width: 50, sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({})},
            {header: t("type"), width: 50, sortable: true, dataIndex: 'type', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["text","document","asset","object","bool","select"]

            })},
            {header: t("value"), width: 50, sortable: true, dataIndex: 'data', editor: new Ext.form.TextField({})},
            {header: t("configuration"), width: 50, sortable: false, dataIndex: 'config', editor: new Ext.form.TextField({})},
            {header: t("content_type"), width: 50, sortable: true, dataIndex: 'ctype', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["document","asset","object"]
            })},
            inheritableCheck,
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

        this.grid = new Ext.grid.EditorGridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            trackMouseOver: true,
            plugins: [inheritableCheck],
            columns : propertiesColumns,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.pagingtoolbar,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },"->",{
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
            name: t('new_property'),
            key: "new_key",
            ctype: "document",
            type: "text"
        });

        this.grid.store.insert(0, u);
    }
});