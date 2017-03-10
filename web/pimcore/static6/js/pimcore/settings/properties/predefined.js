/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.settings.properties.predefined");
pimcore.settings.properties.predefined = Class.create({

    initialize: function () {
        this.getTabPanel();
    },
    
    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("predefined_properties");
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
            tabPanel.setActiveItem("predefined_properties");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("predefined_properties");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var url = '/admin/settings/properties?';

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            [
                {name: 'id'},
                {name: 'name', allowBlank: false},
                {name: 'description'},
                {name: 'key', allowBlank: false},
                {name: 'type', allowBlank: false},
                {name: 'data'},
                {name: 'config', allowBlank: true},
                {name: 'ctype', allowBlank: false},
                {name: 'inheritable'},
                {name: 'creationDate'},
                {name: 'modificationDate'}
            ], null, {
                remoteSort: false,
                remoteFilter: false
            }
        );
        this.store.setAutoSync(true);

        this.filterField = new Ext.form.TextField({
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        var inheritableCheck = new Ext.grid.column.Check({
            header: t("inheritable"),
            dataIndex: "inheritable",
            width: 50
        });

        var propertiesColumns = [
            {header: t("name"), flex: 100, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("description"), sortable: true, dataIndex: 'description', editor: new Ext.form.TextArea({}),
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                                    if(empty(value)) {
                                        return "";
                                    }
                                    return nl2br(value);
                               }
            },
            {header: t("key"), flex: 50, sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({})},
            {header: t("type"), flex: 50, sortable: true, dataIndex: 'type', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["text","document","asset","object","bool","select"]

            })},
            {header: t("value"), flex: 50, sortable: true, dataIndex: 'data', editor: new Ext.form.TextField({})},
            {header: t("configuration"), flex: 50, sortable: false, dataIndex: 'config',
                                                                editor: new Ext.form.TextField({})},
            {header: t("content_type"), flex: 50, sortable: true, dataIndex: 'ctype', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["document","asset","object"]
            })},
            inheritableCheck,
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },{
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('translate'),
                    icon: "/pimcore/static6/img/flat-color-icons/collaboration.svg",
                    handler: function(grid, rowIndex){
                        var rec = grid.getStore().getAt(rowIndex);
                        try {
                            pimcore.globalmanager.get("translationadminmanager").activate(rec.data.name);
                        }
                        catch (e) {
                            pimcore.globalmanager.add("translationadminmanager",
                                                        new pimcore.settings.translation.admin(rec.data.name));
                        }
                    }.bind(this)
                }]
            },
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            }

        ];

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            bodyCls: "pimcore_editable_grid",
            stripeRows: true,
            trackMouseOver: true,
            columns : propertiesColumns,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
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
        this.grid.store.insert(0, {
            name: t('new_property'),
            key: "new_key",
            ctype: "document",
            type: "text"
        });
    }
});