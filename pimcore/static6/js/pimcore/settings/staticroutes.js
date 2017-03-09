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

pimcore.registerNS("pimcore.settings.staticroutes");
pimcore.settings.staticroutes = Class.create({

    initialize:function () {

        this.getTabPanel();
    },

    activate:function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_staticroutes");
    },

    getTabPanel:function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id:"pimcore_staticroutes",
                title:t("static_routes"),
                iconCls:"pimcore_icon_routes",
                border:false,
                layout:"fit",
                closable:true,
                items:[this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_staticroutes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("staticroutes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor:function () {

        var url = '/admin/settings/staticroutes?';

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            [
                {name:'id', type: 'int'},
                {name:'name'},
                {name:'pattern', allowBlank:false},
                {name:'reverse', allowBlank:true},
                {name:'module'},
                {name:'controller'},
                {name:'action'},
                {name:'variables'},
                {name:'defaults'},
                {name:'siteId'},
                {name:'priority', type:'int'},
                {name:'legacy', type:'bool'},
                {name:'creationDate'},
                {name:'modificationDate'}
            ], null, {
                remoteSort: false,
                remoteFilter: false
            }
        );
        this.store.setAutoSync(true);

        this.filterField = new Ext.form.TextField({
            width:200,
            style:"margin: 0 10px 0 0;",
            enableKeyEvents:true,
            listeners:{
                "keydown":function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        var legacyCheck = new Ext.grid.column.Check({
            header: t("legacy_mode"),
            dataIndex: "legacy",
            width: 90
        });

        var typesColumns = [
            {header:t("name"), flex:50, sortable:true, dataIndex:'name',
                editor:new Ext.form.TextField({})},
            {header:t("pattern"), flex:100, sortable:true, dataIndex:'pattern',
                editor:new Ext.form.TextField({})},
            {header:t("reverse"), flex:100, sortable:true, dataIndex:'reverse',
                editor:new Ext.form.TextField({})},
            {header:t("module_optional"), flex:50, sortable:false, dataIndex:'module',
                editor:new Ext.form.TextField({})},
            {header:t("controller"), flex:50, sortable:false, dataIndex:'controller',
                editor:new Ext.form.ComboBox({
                    store:new Ext.data.JsonStore({
                        autoDestroy:true,
                        proxy: {
                            type: 'ajax',
                            url:"/admin/misc/get-available-controllers",
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields:["name"]
                    }),
                    triggerAction:"all",
                    displayField:'name',
                    valueField:'name'
                })},
            {header:t("action"), flex:50, sortable:false, dataIndex:'action',
                editor:new Ext.form.ComboBox({
                    store:new Ext.data.Store({
                        autoDestroy:true,
                        proxy: {
                            type: 'ajax',
                            url:"/admin/misc/get-available-actions",
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields:["name"]
                    }),
                    queryMode: 'local',
                    triggerAction:"all",
                    displayField:'name',
                    valueField:'name',
                    listeners:{
                        "focus":function (el) {
                            var currentRecord = this.grid.getSelection();
                            el.getStore().reload({
                                params:{
                                    controllerName:currentRecord[0].data.controller
                                },
                                callback: function() {
                                    el.expand();
                                }
                            });
                        }.bind(this),
                    }
                })},
            {header:t("variables"), flex:50, sortable:false, dataIndex:'variables',
                editor:new Ext.form.TextField({})},
            {header:t("defaults"), flex:50, sortable:false, dataIndex:'defaults',
                editor:new Ext.form.TextField({})},
            {header:t("site_ids"), flex:100, sortable:true, dataIndex:"siteId",
                editor:new Ext.form.TextField({}),
                tooltip: t("site_ids_tooltip")
            },
            {header:t("priority"), flex:50, sortable:true, dataIndex:'priority', editor:new Ext.form.ComboBox({
                store:[1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                mode:"local",
                triggerAction:"all"
            })},
            legacyCheck,
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
            },
            {
                xtype:'actioncolumn',
                width: 40,
                items:[
                    {
                        tooltip:t('delete'),
                        icon:"/pimcore/static6/img/flat-color-icons/delete.svg",
                        handler:function (grid, rowIndex) {
                            grid.getStore().removeAt(rowIndex);
                        }.bind(this)
                    }
                ]
            }
        ];

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });


        this.grid = Ext.create('Ext.grid.Panel', {
            frame:false,
            autoScroll:true,
            store:this.store,
            columnLines:true,
            bodyCls: "pimcore_editable_grid",
            trackMouseOver:true,
            stripeRows:true,
            columns:typesColumns,
            sm: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            tbar:[
                {
                    text:t('add'),
                    handler:this.onAdd.bind(this),
                    iconCls:"pimcore_icon_add"
                },
                "->",
                {
                    text:t("filter") + "/" + t("search"),
                    xtype:"tbtext",
                    style:"margin: 0 10px 0 0;"
                },
                this.filterField
            ],
            viewConfig:{
                forceFit:true
            }
        });

        return this.grid;
    },


    onAdd:function (btn, ev) {
        var u = {
            name: ""
        };

        this.grid.store.add(u);
    }
});