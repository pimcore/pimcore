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

        var itemsPerPage = 20;

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
                {name: 'creationDate'},
                {name: 'modificationDate'}
            ],
            itemsPerPage
        );
        this.store.setAutoSync(true);

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);


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
                    triggerAction:"all",
                    displayField:'name',
                    valueField:'name',
                    listeners:{
                        "focus":function (field, x1, x2, x3, x4, x5) {
                            var currentRecord = this.grid.getSelection();
                            console.log(currentRecord[0]);
                            field.getStore().reload({
                                params:{
                                    controllerName:currentRecord[0].data.controller
                                }
                            });
                        }.bind(this)
                    }
                })},
            {header:t("variables"), flex:50, sortable:false, dataIndex:'variables',
                editor:new Ext.form.TextField({})},
            {header:t("defaults"), flex:50, sortable:false, dataIndex:'defaults',
                editor:new Ext.form.TextField({})},
            {header:t("site"), flex:100, sortable:true, dataIndex:"siteId",
                editor:new Ext.form.ComboBox({
                    store:pimcore.globalmanager.get("sites"),
                    valueField:"id",
                    displayField:"domain",
                    triggerAction:"all"
                }), renderer:function (siteId) {
                var store = pimcore.globalmanager.get("sites");
                var pos = store.findExact("id", siteId);
                if (pos >= 0) {
                    return store.getAt(pos).get("domain");
                }
            }},
            {header:t("priority"), flex:50, sortable:true, dataIndex:'priority', editor:new Ext.form.ComboBox({
                store:[1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                mode:"local",
                triggerAction:"all"
            })},
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
                        icon:"/pimcore/static6/img/icon/cross.png",
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
            trackMouseOver:true,
            stripeRows:true,
            columns:typesColumns,
            sm: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            bbar:this.pagingtoolbar,
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
            name: "gaga"
        };

        this.grid.store.add(u);
    }
});