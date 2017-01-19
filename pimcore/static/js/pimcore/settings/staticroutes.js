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

    preconfiguredFilter: "",

    initialize:function (filter) {
        if(filter)
            this.preconfiguredFilter = filter;

        this.getTabPanel();
    },

    activate:function (filter) {
        if(filter) {
            this.filterField.setValue(filter);
            this.store.baseParams.filter = filter;
            this.store.load();
        }
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_staticroutes");
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
            tabPanel.activate("pimcore_staticroutes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("staticroutes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor:function () {

        var proxy = new Ext.data.HttpProxy({
            url:'/admin/settings/staticroutes'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty:'total',
            successProperty:'success',
            root:'data'
        }, [
            {name:'id'},
            {name:'name', allowBlank:true},
            {name:'pattern', allowBlank:false},
            {name:'reverse', allowBlank:true},
            {name:'module', allowBlank:true},
            {name:'controller', allowBlank:true},
            {name:'action', allowBlank:true},
            {name:'variables', allowBlank:true},
            {name:'defaults', allowBlank:true},
            {name:'siteId', allowBlank:true},
            {name:'priority', type:'int', allowBlank:true},
            {name: 'creationDate', allowBlank: true},
            {name: 'modificationDate', allowBlank: true}
        ]);
        var writer = new Ext.data.JsonWriter();


        this.store = new Ext.data.Store({
            id:'staticroutes_store',
            restful:false,
            proxy:proxy,
            reader:reader,
            writer:writer,
            remoteSort:false,
            baseParams:{
                filter:this.preconfiguredFilter
            },
            listeners:{
                write:function (store, action, result, response, rs) {
                }
            }
        });
        this.store.load();


        this.filterField = new Ext.form.TextField({
            xtype:"textfield",
            width:200,
            style:"margin: 0 10px 0 0;",
            value: this.preconfiguredFilter,
            enableKeyEvents:true,
            listeners:{
                "keydown":function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        this.store.baseParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        var typesColumns = [
            {header:t("name"), width:50, sortable:true, dataIndex:'name',
                editor:new Ext.form.TextField({})},
            {header:t("pattern"), width:100, sortable:true, dataIndex:'pattern',
                editor:new Ext.form.TextField({})},
            {header:t("reverse"), width:100, sortable:true, dataIndex:'reverse',
                editor:new Ext.form.TextField({})},
            {header:t("module_optional"), width:50, sortable:false, dataIndex:'module',
                editor:new Ext.form.ComboBox({
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        url: "/admin/misc/get-available-modules",
                        root: "data",
                        fields: ["name"]
                    }),
                    triggerAction: "all",
                    displayField: 'name',
                    valueField: 'name'
                })},
            {header:t("controller"), width:50, sortable:false, dataIndex:'controller',
                editor:new Ext.form.ComboBox({
                    store:new Ext.data.JsonStore({
                        autoDestroy:true,
                        url:"/admin/misc/get-available-controllers",
                        root:"data",
                        fields:["name"]
                    }),
                    triggerAction:"all",
                    displayField:'name',
                    valueField:'name',
                    listeners:{
                        "focus":function (el) {
                            el.getStore().reload({
                                params:{
                                    moduleName:this.store.data.items[el.gridEditor.row].data.module
                                }
                            });
                        }.bind(this)
                    }
                })},
            {header:t("action"), width:50, sortable:false, dataIndex:'action',
                editor:new Ext.form.ComboBox({
                    store:new Ext.data.JsonStore({
                        autoDestroy:true,
                        url:"/admin/misc/get-available-actions",
                        root:"data",
                        fields:["name"]
                    }),
                    triggerAction:"all",
                    displayField:'name',
                    valueField:'name',
                    listeners:{
                        "focus":function (el) {
                            el.getStore().reload({
                                params:{
                                    moduleName:this.store.data.items[el.gridEditor.row].data.module,
                                    controllerName:this.store.data.items[el.gridEditor.row].data.controller
                                }
                            });
                        }.bind(this)
                    }
                })},
            {header:t("variables"), width:50, sortable:false, dataIndex:'variables',
                editor:new Ext.form.TextField({})},
            {header:t("defaults"), width:50, sortable:false, dataIndex:'defaults',
                editor:new Ext.form.TextField({})},
            {header:t("site_ids"), width:100, sortable:true, dataIndex:"siteId",
                editor:new Ext.form.TextField({})
            },
            {header:t("priority"), width:50, sortable:true, dataIndex:'priority', editor:new Ext.form.ComboBox({
                store:[1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                mode:"local",
                triggerAction:"all"
            })},
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
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
                        return date.format("Y-m-d H:i:s");
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

        this.grid = new Ext.grid.EditorGridPanel({
            frame:false,
            autoScroll:true,
            store:this.store,
            columnLines:true,
            trackMouseOver:true,
            stripeRows:true,
            columns:typesColumns,
            sm:new Ext.grid.RowSelectionModel({singleSelect:true}),
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
        var u = new this.grid.store.recordType({
            name:""
        });

        this.grid.store.insert(0, u);
    }
});
