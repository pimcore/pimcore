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

pimcore.registerNS("pimcore.settings.email.blacklist");
pimcore.settings.email.blacklist = Class.create({

    initialize:function () {

        this.getTabPanel();
    },

    activate:function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("email_blacklist");
    },

    getTabPanel:function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id:"email_blacklist",
                title:t("email_blacklist"),
                iconCls:"pimcore_icon_email_blacklist",
                border:false,
                layout:"fit",
                closable:true,
                items:[this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("email_blacklist");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("email_blacklist");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor:function () {

        var itemsPerPage = 20;
        var url ='/admin/email/blacklist?';

        var proxy = {
            type: 'ajax',
            extraParams:{
                limit:itemsPerPage,
                filter:""
            },
            reader: {
                type: 'json',
                rootProperty: 'data',
                idProperty: "address"
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
            proxy:proxy,
            remoteSort:true,
            autoSync: true,
            autoLoad: true,
            fields: [
                {name:'address', allowBlank: false},
                {name:'creationDate'},
                {name:'modificationDate'}
            ]
        });


        this.filterField = new Ext.form.TextField({
            xtype:"textfield",
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
                    var pageSize = intval(rec.data.field1);
                    this.store.getProxy().extraParams.limit = pageSize;
                    this.pagingtoolbar.pageSize = pageSize;
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        var typesColumns = [
            {header:t("email_address"), flex:50, sortable:true, dataIndex:'address', editable: false},
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: false,
                width: 150,
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
                width: 150,
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
            selModel: Ext.create('Ext.selection.RowModel', {}),
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
        Ext.MessageBox.prompt("", t("email_address"), function (button, value) {
            if(button == "ok") {
                var u = {
                    "address": value
                };

                this.grid.store.insert(0, u);
            }

        }.bind(this));
    }
});