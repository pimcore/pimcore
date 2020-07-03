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
pimcore.registerNS("pimcore.notification.panel");

pimcore.notification.panel = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_notification_panel");
    },

    getTabPanel: function () {
        if (!this.panel) {
            var gridPanel = new Ext.Panel({
                id: 'gridPanel',
                region: 'center',
                layout: "fit",
                items: [
                    this.getGrid()
                ]
            });

            this.panel = new Ext.Panel({
                id: "pimcore_notification_panel",
                title: t("notifications"),
                iconCls: "pimcore_icon_comments",
                border: false,
                layout: 'border',
                closable: true,
                items: [
                    gridPanel
                ],
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_notification_panel");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("notifications");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getGrid: function () {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        this.store = pimcore.helpers.grid.buildDefaultStore(
            Routing.generate('pimcore_admin_notification_findall'),
            ["id", "title", "sender", "date", "read"],
            itemsPerPage
        );

        var typesColumns = [
            {header: "ID", flex: 1, sortable: false, hidden: true, dataIndex: 'id'},
            {
                header: t("title"),
                flex: 10,
                sortable: true,
                filter: 'string',
                dataIndex: 'title',
                renderer: function (val, metaData, record, rowIndex, colIndex, store) {
                    var read = parseInt(store.getAt(rowIndex).get("read"));
                    if (read == 0) {
                        return '<strong style="font-weight: bold;">' + val + '</strong>'; // css style need to be added
                    }
                    return val;
                }
            },
            {header: t("sender"), flex: 2, sortable: false, dataIndex: 'sender'},
            {header: t("date"), flex: 3, sortable: true, filter: 'date', dataIndex: 'date'},
            {
                header: t("attachment"),
                xtype: 'actioncolumn',
                flex: 1,
                items: [
                    {
                        tooltip: t('open_linked_element'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/cursor.svg",
                        handler: function (grid, rowIndex) {
                            pimcore.notification.helper.openLinkedElement(grid.getStore().getAt(rowIndex).data);
                        }.bind(this),
                        isDisabled: function (grid, rowIndex) {
                            return !parseInt(grid.getStore().getAt(rowIndex).data['linkedElementId']);
                        }.bind(this)
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                flex: 1,
                items: [
                    {
                        tooltip: t('open'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/right.svg",
                        handler: function (grid, rowIndex) {
                            pimcore.notification.helper.openDetails(grid.getStore().getAt(rowIndex).get("id"), function () {
                                this.reload();
                            }.bind(this));
                        }.bind(this)
                    },
                    {
                        tooltip: t('mark_as_read'),
                        icon: '/bundles/pimcoreadmin/img/flat-color-icons/checkmark.svg',
                        handler: function (grid, rowIndex) {
                            pimcore.notification.helper.markAsRead(grid.getStore().getAt(rowIndex).get("id"), function () {
                                this.reload();
                            }.bind(this));
                        }.bind(this),
                        isDisabled: function (grid, rowIndex) {
                            return parseInt(grid.getStore().getAt(rowIndex).get("read"));
                        }.bind(this)
                    },
                    {
                        tooltip: t('delete'),
                        icon: '/bundles/pimcoreadmin/img/flat-color-icons/delete.svg',
                        handler: function (grid, rowIndex) {
                            pimcore.notification.helper.delete(grid.getStore().getAt(rowIndex).get("id"), function () {
                                this.reload();
                            }.bind(this));
                        }.bind(this)
                    }

                ]
            }
        ];

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t("delete_all"),
                    iconCls: "pimcore_icon_delete",
                    handler: function () {
                        Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"),
                            function (buttonValue) {
                                if (buttonValue == "yes") {
                                    pimcore.notification.helper.deleteAll(function () {
                                        this.reload();
                                    }.bind(this));
                                }
                            }.bind(this));
                    }.bind(this)
                }
            ]
        });

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            plugins: ['pimcore.gridfilters'],
            columns: typesColumns,
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            columnLines: true,
            stripeRows: true,
            listeners: {
                "itemdblclick": function (grid, record, tr, rowIndex, e, eOpts) {
                    pimcore.notification.helper.openDetails(record.data.id, function () {
                        this.reload();
                    }.bind(this));
                }.bind(this)

            },
            viewConfig: {
                forceFit: true
            },
            tbar: toolbar
        });

        return this.grid;
    },

    reload: function () {
        this.store.reload();
    }
});
