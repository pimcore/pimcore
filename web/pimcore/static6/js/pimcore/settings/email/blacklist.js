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
                iconCls:"pimcore_icon_email pimcore_icon_overlay_delete",
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

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url ='/admin/email/blacklist?';

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            [
                {name:'address', allowBlank: false},
                {name:'creationDate'},
                {name:'modificationDate'}
            ],
            itemsPerPage
        );


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

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

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

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [
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
            ]
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
            tbar: toolbar,
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