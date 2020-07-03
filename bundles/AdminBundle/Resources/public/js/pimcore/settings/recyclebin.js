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

pimcore.registerNS("pimcore.settings.recyclebin");
pimcore.settings.recyclebin = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_recyclebin");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_recyclebin",
                title: t("recyclebin"),
                border: false,
                iconCls: "pimcore_icon_recyclebin",
                layout: "fit",
                closable: true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_recyclebin");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("recyclebin");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getGrid: function () {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        this.store = pimcore.helpers.grid.buildDefaultStore(
            Routing.generate('pimcore_admin_recyclebin_list'),
            [
                {name: 'id'},
                {name: 'type'},
                {name: 'subtype'},
                {name: 'path'},
                {name: 'amount'},
                {name: 'deletedby'},
                {name: 'date'}
            ],
            itemsPerPage
        );
        this.store.getProxy().setBatchActions(false);

        this.store.addListener('load', function () {
            if (this.store.getCount() > 0) {
                Ext.getCmp("pimcore_recyclebin_button_flush").enable();
            }
        }.bind(this));


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown": function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filterFullText = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var typesColumns = [
            {
                text: t("type"), width: 50, sortable: true, dataIndex: 'subtype', renderer: function (d) {
                    return '<img src="/bundles/pimcoreadmin/img/flat-color-icons/' + d + '.svg" style="height: 16px" />';
                }
            },
            {text: t("path"), flex: 200, sortable: true, dataIndex: 'path', filter: 'string', renderer: Ext.util.Format.htmlEncode},
            {text: t("amount"), flex: 60, sortable: true, dataIndex: 'amount'},
            {text: t("deletedby"), flex: 80, sortable: true, dataIndex: 'deletedby', filter: 'string'},
            {
                text: t("date"), flex: 140, sortable: true, dataIndex: 'date',
                renderer: function (d) {
                    var date = new Date(d * 1000);
                    return Ext.Date.format(date, "Y-m-d H:i:s");
                },
                filter: 'date'

            },
            {
                xtype: 'actioncolumn',
                menuText: t('delete'),
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            }
        ];

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t('restore'),
                    handler: this.restoreSelected.bind(this),
                    iconCls: "pimcore_icon_restore",
                    id: "pimcore_recyclebin_button_restore",
                    disabled: true
                }, '-', {
                    text: t('delete'),
                    handler: this.deleteSelected.bind(this),
                    iconCls: "pimcore_icon_delete",
                    id: "pimcore_recyclebin_button_delete",
                    disabled: true
                }, "-",
                {
                    text: t('flush_recyclebin'),
                    handler: this.onFlush.bind(this),
                    iconCls: "pimcore_icon_flush_recyclebin",
                    id: "pimcore_recyclebin_button_flush",
                    disabled: true
                },
                '->', {
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });

        this.selectionColumn = new Ext.selection.CheckboxModel();
        this.selectionColumn.on("selectionchange", this.updateButtonStates.bind(this));

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            bbar: this.pagingtoolbar,
            stripeRows: true,
            selModel: this.selectionColumn,
            plugins: ['pimcore.gridfilters'],
            columns: typesColumns,
            tbar: toolbar,
            listeners: {
                "rowclick": this.updateButtonStates.bind(this)
            },
            viewConfig: {
                forceFit: true
            }
        });

        this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));

        return this.grid;
    },

    updateButtonStates: function() {
        var selectedRows = this.grid.getSelectionModel().getSelection();

        if (selectedRows.length >= 1) {
            Ext.getCmp("pimcore_recyclebin_button_restore").enable();
            Ext.getCmp("pimcore_recyclebin_button_delete").enable();
        } else {
            Ext.getCmp("pimcore_recyclebin_button_restore").disable();
            Ext.getCmp("pimcore_recyclebin_button_delete").disable();
        }
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();
        var selModel = grid.getSelectionModel();
        var selectedRows = selModel.getSelection();

        menu.add(new Ext.menu.Item({
            text: t('restore'),
            iconCls: "pimcore_icon_restore",
            handler: this.restoreSelected.bind(this),
            disabled: !selectedRows.length
        }));
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteSelected.bind(this),
            disabled: !selectedRows.length
        }));


        e.stopEvent();
        menu.showAt(e.getXY());
    },

    deleteSelected: function () {
        var selectedRows = this.grid.getSelectionModel().getSelection();
        this.grid.getStore().remove(selectedRows);
    },

    onFlush: function (btn, ev) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_recyclebin_flush'),
            method: 'DELETE',
            success: function () {
                this.store.reload();
                this.grid.getView().refresh();
            }.bind(this)
        });
    },

    doRestore: function (ids, offset) {

        this.store.reload();
        this.grid.getView().refresh();

        if (offset == ids.length) {
            try {
                // would be nice if /admin/recyclebin/restore could return the affected types
                // so that we don't have to refresh all types
               const elementTypes = ["document", "asset", "object"];
               elementTypes.forEach(function(elementType, index) {
                   pimcore.elementservice.refreshRootNodeAllTrees(elementType);
                });
            }
            catch (e) {
                console.log(e);
            }
            pimcore.helpers.loadingHide();
            return;

        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_recyclebin_restore'),
            method: 'POST',
            params: {
                id: ids[offset]
            },
            success: function (ids, offset) {
                this.doRestore(ids, offset + 1);

            }.bind(this, ids, offset),

            failure: function (response) {
                pimcore.helpers.loadingHide();
                var message = t('restore_failed');

                try {
                    var json = Ext.decode(response.responseText);
                    if (json.message) {
                        message += ': ' + json.message;
                    }
                } catch (e) {
                }

                pimcore.helpers.showNotification(t("error"), message, "error");
            }.bind(this)
        });

    },

    restoreSelected: function () {

        var selectedRows = this.grid.getSelectionModel().getSelection();
        if (selectedRows.length <= 0) {
            return;
        }

        var ids = [];
        for (var i = 0; i < selectedRows.length; i++) {
            ids.push(selectedRows[i].data.id);
        }

        pimcore.helpers.loadingShow();
        Ext.getCmp("pimcore_recyclebin_button_restore").disable();
        Ext.getCmp("pimcore_recyclebin_button_delete").disable();

        this.doRestore(ids, 0);
    }
});
