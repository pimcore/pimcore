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

pimcore.registerNS("pimcore.settings.gdpr.dataproviders.dataObjects");
pimcore.settings.gdpr.dataproviders.dataObjects = Class.create({

    title: t("gdpr_dataSource_dataObjects"),
    iconCls: "pimcore_icon_object",

    searchParams: [],

    initialize: function (searchParams) {
        this.searchParams = searchParams;
        this.getPanel();
    },

    getPanel: function () {

        if(!this.panel) {

            this.panel = new Ext.Panel({
                title: this.title,
                layout: "border",
                iconCls: this.iconCls,
                closable: false
            });

            this.initGrid();
            this.store.load();
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
                url: Routing.generate('pimcore_admin_gdpr_dataobject_searchdataobjects'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                },
                extraParams: this.searchParams
            },
            fields: ["id","fullpath","type","subtype","filename",{name:"classname",convert: function(v, rec){
                return t(rec.data.classname);
            }},"published"]
        });

        var columns = [
            {text: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_' + value + '" name="'
                        + t(record.data.subtype) + '">&nbsp;</div>';
                }
            },
            {text: 'ID', width: 60, sortable: true, dataIndex: 'id', hidden: false},
            {text: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
            {text: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath'},
            {text: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: true},
            {text: t("class"), width: 200, sortable: true, dataIndex: 'classname'},
            {
                xtype: 'actioncolumn',
                menuText: t('gdpr_dataSource_export'),
                width: 40,
                items: [
                    {
                        tooltip: t('gdpr_dataSource_export'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/export.svg",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            if (!data.get("permissions").view) {
                                pimcore.helpers.showPermissionError("view");
                                return;
                            }
                            pimcore.helpers.download(Routing.generate('pimcore_admin_gdpr_dataobject_exportdataobject', {id: data.data.id}));
                        }.bind(this),
                        getClass: function (v, meta, rec) {
                            if (!rec.get("permissions").view) {
                                return "inactive_actioncolumn";
                            }
                        }
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                menuText: t('open'),
                width: 40,
                items: [
                    {
                        tooltip: t('open'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            if (!data.get("permissions").view) {
                                pimcore.helpers.showPermissionError("view");
                                return;
                            }

                            pimcore.helpers.openObject(data.data.id, "object");
                        }.bind(this),
                        getClass: function (v, meta, rec) {
                            if (!rec.get("permissions").view) {
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

                            var data = grid.getStore().getAt(rowIndex);
                            if (!data.get("permissions").delete) {
                                pimcore.helpers.showPermissionError("delete");
                                return;
                            }

                            var options = {
                                "elementType": "object",
                                "id": data.data.id,
                                "success": function () {
                                    this.store.reload();
                                    pimcore.elementservice.refreshRootNodeAllTrees("object");
                                }.bind(this)
                            };
                            pimcore.elementservice.deleteElement(options);

                        }.bind(this),
                        isDisabled: function (view, rowIndex, colIndex, item, record) {
                            return record.data["__gdprIsDeletable"] == false;
                        },
                        getClass: function (v, meta, rec) {
                            if (!rec.get("permissions").delete) {
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
            bbar: this.pagingtoolbar,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    pimcore.helpers.openObject(data.data.id, "object");
                }.bind(this)
            }
        });

        this.panel.add(this.gridPanel);

    }

});
