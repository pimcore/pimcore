/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.settings.gdpr.dataproviders.pimcoreUsers");
pimcore.settings.gdpr.dataproviders.pimcoreUsers = Class.create({

    searchParams: [],

    initialize: function (searchParams) {
        this.searchParams = searchParams;
        this.getPanel();
    },

    getPanel: function () {

        if(!this.panel) {

            this.panel = new Ext.Panel({
                title: t("gdpr_dataSource_pimcoreUsers"),
                layout: "border",
                iconCls: "pimcore_icon_user",
                closable: false
            });

            this.initGrid();
        }

        return this.panel;
    },

    initGrid: function () {

        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: pimcore.helpers.grid.getDefaultPageSize(),
            proxy : {
                type: 'ajax',
                url: "/admin/gdpr/pimcore-users/search-users",
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                },
                extraParams: this.searchParams
            },
            autoLoad: true,
            fields: ["id","username","firstname","lastname","email"]
        });

        var columns = [
            {header: 'ID', width: 60, sortable: true, dataIndex: 'id', hidden: false},
            {header: t("username"), flex: 100, sortable: true, dataIndex: 'username'},
            {header: t("firstname"), flex: 200, sortable: true, dataIndex: 'firstname'},
            {header: t("lastname"), flex: 200, sortable: true, dataIndex: 'lastname'},
            {header: t("email"), flex: 200, sortable: true, dataIndex: 'email'},
            {
                xtype: 'actioncolumn',
                width: 40,
                items: [
                    {
                        tooltip: t('gdpr_dataSource_export'),
                        icon: "/pimcore/static6/img/flat-color-icons/export.svg",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            pimcore.helpers.download("/admin/gdpr/pimcore-users/export-user-data?id=" + data.data.id);
                        }.bind(this)
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                width: 40,
                items: [
                    {
                        tooltip: t('remove'),
                        icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                        handler: function (grid, rowIndex) {

                            var data = grid.getStore().getAt(rowIndex);

                            Ext.MessageBox.show({
                                title:t('delete'),
                                msg: t("are_you_sure"),
                                buttons: Ext.Msg.YESNO ,
                                icon: Ext.MessageBox.QUESTION,
                                fn: function (button) {
                                    if (button == "yes") {
                                        Ext.Ajax.request({
                                            url: "/admin/user/delete",
                                            params: {
                                                id: data.data.id
                                            },
                                            success: function() {
                                                this.store.reload();
                                            }.bind(this, data)
                                        });
                                    }
                                }.bind(this)
                            });

                        }.bind(this),
                        isDisabled: function(view, rowIndex, colIndex, item, record) {
                            return record.data["__gdprIsDeletable"] == false;
                        }
                    }
                ]
            }
        ];


        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);
        this.gridPanel = Ext.create('Ext.grid.Panel', {
            region: "center",
            store: this.store,
            border: false,
            columns: columns,
            loadMask: true,
            columnLines: true,
            stripeRows: true,
            plugins: ['pimcore.gridfilters'],
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            cls: 'pimcore_object_grid_panel',
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar
        });

        this.panel.add(this.gridPanel);
    }

});
