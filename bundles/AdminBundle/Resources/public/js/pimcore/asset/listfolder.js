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

pimcore.registerNS("pimcore.asset.listfolder");
pimcore.asset.listfolder = Class.create(pimcore.asset.helpers.gridTabAbstract, {

    systemColumns: ["id", "type", "fullpath", "filename", "creationDate", "modificationDate", "preview", "size"],
    onlyDirectChildren: false,
    onlyUnreferenced: false,
    fieldObject: {},
    object: {},
    gridType: 'asset',

    initialize: function (element, searchType) {
        this.element = element;
        this.searchType = searchType;
        this.classId = element.id;
        this.object.id = element.id;
        this.noBatchColumns = [];
        this.batchAppendColumns = [];
    },

    getLayout: function () {
        if (this.layout == null) {

            this.layout = new Ext.Panel({
                title: t("list"),
                iconCls: "pimcore_material_icon_list pimcore_material_icon",
                border: false,
                layout: "fit"
            });

            this.layout.on("afterrender", this.getGrid.bind(this, false));
        }

        return this.layout;
    },

    //for parent switchToGridConfig call
    getTableDescription: function () {
        this.getGrid();
    },

    getGrid: function () {
        Ext.Ajax.request({
            url: "/admin/asset-helper/grid-get-column-config",
            params: {
                id: this.element.data.id,
                type: "asset",
                gridConfigId: this.settings ? this.settings.gridConfigId : null,
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },

    createGrid: function (fromConfig, response, settings, save) {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        var fields = [];

        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.gridPageSize = response.pageSize;
            this.sortinfo = response.sortinfo;

            this.settings = response.settings || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;

            if (response.onlyDirectChildren) {
                this.onlyDirectChildren = response.onlyDirectChildren;
            }

            if (response.onlyUnreferenced) {
                this.onlyUnreferenced = response.onlyUnreferenced;
            }
        } else {
            itemsPerPage = this.gridPageSize;
            fields = response;
            this.settings = settings;
            this.buildColumnConfigMenu();
        }

        this.fieldObject = {};

        for(var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            }
        );

        var fieldParam = Object.keys(this.fieldObject);

        var gridHelper = new pimcore.asset.helpers.grid(
            fields,
            "/admin/asset/grid-proxy",
            {
                language: this.gridLanguage,
                // limit: itemsPerPage
            },
            false
        );

        gridHelper.showSubtype = false;
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;

        var existingFilters;
        if (this.store) {
            existingFilters = this.store.getFilters();
        }

        this.store = gridHelper.getStore(this.noBatchColumns, this.batchAppendColumns);
        if (this.sortinfo) {
            this.store.sort(this.sortinfo.field, this.sortinfo.direction);
        }

        this.store.getProxy().extraParams = {
            limit: itemsPerPage,
            folderId: this.element.data.id,
            "fields[]": fieldParam,
            language: this.gridLanguage,
            only_direct_children: this.onlyDirectChildren,
            only_unreferenced: this.onlyUnreferenced
        };

        this.store.setPageSize(itemsPerPage);

        if (existingFilters) {
            this.store.setFilters(existingFilters.items);
        }

        var gridColumns = gridHelper.getGridColumns();

        // add filters
        this.gridfilters = gridHelper.getGridFilters();


        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + (this.gridLanguage == "default" ? t("default") : pimcore.available_languages[this.gridLanguage])
        });

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            boxLabel: t("only_children"),
            listeners: {
                "change" : function (field, checked) {
                    this.store.getProxy().setExtraParam("only_direct_children", checked);
                    this.onlyDirectChildren = checked;

                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });

        this.checkboxOnlyUnreferenced = new Ext.form.Checkbox({
            name: "onlyUnreferenced",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyUnreferenced,
            boxLabel: t("only_unreferenced"),
            listeners: {
                "change" : function (field, checked) {
                    this.store.getProxy().setExtraParam("only_unreferenced", checked);
                    this.onlyUnreferenced = checked;

                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });

        var hideSaveColumnConfig = !fromConfig || save;

        this.saveColumnConfigButton = new Ext.Button({
            tooltip: t('save_grid_options'),
            iconCls: "pimcore_icon_publish",
            hidden: hideSaveColumnConfig,
            handler: function () {
                var asCopy = !(this.settings.gridConfigId > 0);
                this.saveConfig(asCopy)
            }.bind(this)
        });

        this.columnConfigButton = new Ext.SplitButton({
            text: t('grid_options'),
            iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
            handler: function () {
                this.openColumnConfig(true);
            }.bind(this),
            menu: []
        });

        this.buildColumnConfigMenu();

        var exportButtons = this.getExportButtons();
        var firstButton = exportButtons.pop();

        this.exportButton = new Ext.SplitButton({
            text: firstButton.text,
            iconCls: firstButton.iconCls,
            handler: firstButton.handler,
            menu: exportButtons,
        });

        this.downloadSelectedZipButton = new Ext.Button({
            text: t("download_selected_as_zip"),
            iconCls: "pimcore_icon_zip pimcore_icon_overlay_download",
            handler: function () {
                var ids = [];

                var selectedRows = this.grid.getSelectionModel().getSelection();
                for (var i = 0; i < selectedRows.length; i++) {
                    ids.push(selectedRows[i].data.id);
                }

                if(ids.length) {
                    pimcore.elementservice.downloadAssetFolderAsZip(this.element.id, ids);
                } else {
                    Ext.Msg.alert(t('error'), t('please_select_items_to_download'));
                }
            }.bind(this)
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            columns : gridColumns,
            plugins: [this.cellEditing, 'pimcore.gridfilters'],
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            selModel: gridHelper.getSelectionColumn(),
            viewConfig: {
                forceFit: true,
                enableTextSelection: true
            },
            listeners: {
                activate: function() {
                    this.store.load();
                }.bind(this),
                celldblclick: function(grid, td, cellIndex, record, tr, rowIndex, e, eOpts) {
                    var columnName = grid.ownerGrid.getColumns();
                    if(columnName[cellIndex].dataIndex == 'id' || columnName[cellIndex].dataIndex == 'fullpath'
                        || columnName[cellIndex].dataIndex == 'preview') {
                        var data = this.store.getAt(rowIndex);
                        pimcore.helpers.openAsset(data.get("id"), data.get("type"));
                    }
                }
            },
            tbar: [
                this.languageInfo, "->",
                this.checkboxOnlyDirectChildren, "-",
                this.checkboxOnlyUnreferenced, "-",
                this.downloadSelectedZipButton, "-",
                this.exportButton, "-",
                this.columnConfigButton,
                this.saveColumnConfigButton
            ]
        });

        this.grid.on("columnmove", function () {
            this.saveColumnConfigButton.show();
        }.bind(this));
        this.grid.on("columnresize", function () {
            this.saveColumnConfigButton.show();
        }.bind(this));

        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();

        if (save) {
            if (this.settings.isShared) {
                this.settings.gridConfigId = null;
            }
            this.saveConfig(false);
        }

        //this.grid.getView().on("refresh", this.updateRows.bind(this, "view-refresh"));
    },

    getColumnWidth: function(field, defaultValue) {
        if (field.width) {
            return field.width;
        } else if(field.layout && field.layout.width) {
            return field.layout.width;
        } else {
            return defaultValue;
        }
    },

    getExportButtons: function () {
        var buttons = [];
        pimcore.globalmanager.get("pimcore.asset.gridexport").forEach(function (exportType) {
            buttons.push({
                text: t(exportType.text),
                iconCls: exportType.icon || "pimcore_icon_export",
                handler: function () {
                    pimcore.helpers.exportWarning(exportType, function (settings) {
                        this.exportPrepare(settings, exportType);
                    }.bind(this));
                }.bind(this),
            })
        }.bind(this));

        return buttons;
    },

    getGridConfig: function ($super) {
        var config = $super();
        config.onlyDirectChildren = this.onlyDirectChildren;
        config.onlyUnreferenced = this.onlyUnreferenced;
        config.pageSize = this.pagingtoolbar.pageSize;
        return config;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        var selModel = grid.getSelectionModel();
        var selectedRows = selModel.getSelection();

        if (selectedRows.length <= 1) {

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    pimcore.helpers.openAsset(data.data.id, data.data.type);
                }.bind(this, data)
            }));

            if (pimcore.elementservice.showLocateInTreeButton("asset")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function () {
                        try {
                            try {
                                pimcore.treenodelocator.showInTree(record.id, "asset", this);
                            } catch (e) {
                                console.log(e);
                            }

                        } catch (e2) {
                            console.log(e2);
                        }
                    }
                }));
            }

            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var options = {
                        "elementType" : "asset",
                        "id": data.data.id,
                        "success": function() {
                            this.getStore().reload();
                        }.bind(this)
                    };

                    pimcore.elementservice.deleteElement(options);

                }.bind(grid, data)
            }));
        } else {
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        var data = selectedRows[i].data;
                        pimcore.helpers.openAsset(data.id, data.type);
                    }
                }.bind(this, data)
            }));

            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var ids = [];
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        ids.push(selectedRows[i].data.id);
                    }
                    ids = ids.join(',');

                    var options = {
                        "elementType" : "asset",
                        "id": ids,
                        "success": function() {
                            this.store.reload();
                        }.bind(this)
                    };

                    pimcore.elementservice.deleteElement(options);

                }.bind(grid, data)
            }));
        }

        e.stopEvent();
        menu.showAt(e.getXY());
    }

});

pimcore.asset.listfolder.addMethods(pimcore.element.helpers.gridColumnConfig);