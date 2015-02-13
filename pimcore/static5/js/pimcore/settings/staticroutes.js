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
        var proxy = {
            type: 'ajax',
            extraParams:{
                limit:itemsPerPage,
                filter:""
            },
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            api: {
                create  : url + "xaction=create",
                read    : url + "xaction=read",
                update  : url + "xaction=update",
                destroy : url + "xaction=destroy"
            },
            actionMethods: {
                create : 'POST',
                read   : 'POST',
                update : 'POST',
                destroy: 'POST'
            }

        };

        this.store = new Ext.data.Store({
            //id:'staticroutes_store',
            //restful:false,
            proxy:proxy,
            autoLoad: true,
            autoSync: true,
            fields: [
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
            remoteSort:true
        });

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

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize:itemsPerPage,
            store:this.store,
            displayInfo:true,
            displayMsg:'{0} - {1} / {2}',
            emptyMsg:t("no_items_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text:t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store:[
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode:"local",
            width:50,
            value:20,
            triggerAction:"all",
            listeners:{
                select:function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

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
                        "focus":function (el) {
                            console.log();
                            el.getStore().reload({
                                params:{
                                    controllerName:this.store.data.items[el.gridEditor.row].data.controller
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
                width:30,
                items:[
                    {
                        tooltip:t('delete'),
                        icon:"/pimcore/static/img/icon/cross.png",
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