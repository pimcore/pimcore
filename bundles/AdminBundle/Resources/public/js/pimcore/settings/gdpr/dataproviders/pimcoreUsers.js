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
                title: t("users") + " (Pimcore)",
                layout: "border",
                iconCls: "pimcore_icon_user",
                closable: false
            });

            this.initGrid();
        }

        return this.panel;
    },

    initGrid: function () {

        var user = pimcore.globalmanager.get("user");

        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: pimcore.helpers.grid.getDefaultPageSize(),
            proxy : {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_gdpr_pimcoreusers_searchusers'),
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
            {text: 'ID', width: 60, sortable: true, dataIndex: 'id', hidden: false},
            {text: t("username"), flex: 100, sortable: true, dataIndex: 'username'},
            {text: t("firstname"), flex: 200, sortable: true, dataIndex: 'firstname'},
            {text: t("lastname"), flex: 200, sortable: true, dataIndex: 'lastname'},
            {text: t("email"), flex: 200, sortable: true, dataIndex: 'email'},
            {
                xtype: 'actioncolumn',
                menuText: t('gdpr_dataSource_export'),
                width: 40,
                items: [
                    {
                        tooltip: t('gdpr_dataSource_export'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/export.svg",
                        handler: function (grid, rowIndex) {
                            if (!user.isAllowed("users")) {
                                pimcore.helpers.showPermissionError("users");
                                return;
                            }

                            var data = grid.getStore().getAt(rowIndex);
                            pimcore.helpers.download(Routing.generate('pimcore_admin_gdpr_pimcoreusers_exportuserdata', {id: data.data.id}));
                        }.bind(this),
                        getClass: function(v, meta, rec) {
                            if(!user.isAllowed('users')){
                                return "inactive_actioncolumn";
                            }
                        }
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                menuText: t('remove'),
                width: 40,
                items: [
                    {
                        tooltip: t('remove'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                        handler: function (grid, rowIndex) {
                            if (!user.isAllowed("users")) {
                                pimcore.helpers.showPermissionError("users");
                                return;
                            }

                            var data = grid.getStore().getAt(rowIndex);

                            Ext.MessageBox.show({
                                title:t('delete'),
                                msg: t("are_you_sure"),
                                buttons: Ext.Msg.YESNO ,
                                icon: Ext.MessageBox.QUESTION,
                                fn: function (button) {
                                    if (button == "yes") {
                                        Ext.Ajax.request({
                                            url: Routing.generate('pimcore_admin_user_delete'),
                                            method: 'DELETE',
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
                        },
                        getClass: function(v, meta, rec) {
                            if(!user.isAllowed('users')){
                                return "inactive_actioncolumn";
                            }
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
