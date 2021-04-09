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

    systemColumns: ["id~system", "type~system", "fullpath~system", "filename~system", "creationDate~system", "modificationDate~system", "preview~system", "size~system"],
    onlyDirectChildren: false,
    onlyUnreferenced: false,
    fieldObject: {},
    object: {},
    gridType: 'asset',

    initialize: function ($super, element, searchType) {
        $super();

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
                layout: "border"
            });

            var user = pimcore.globalmanager.get("user");
            if(user.isAllowed("tags_search")) {
                this.layout.add(this.getTagsPanel());
            }


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
            url: Routing.generate('pimcore_admin_asset_assethelper_gridgetcolumnconfig'),
            params: {
                id: this.element.data.id,
                type: "asset",
                gridConfigId: this.settings ? this.settings.gridConfigId : null,
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },

    createGrid: function (fromConfig, response, settings, save, context) {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        var fields = [];

        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridPageSize = response.pageSize;
            this.sortinfo = response.sortinfo;

            this.settings = response.settings || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;

            if (typeof response.onlyDirectChildren != "undefined") {
                this.onlyDirectChildren = response.onlyDirectChildren;
            }

            if (typeof response.onlyUnreferenced != "undefined") {
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
            Routing.generate('pimcore_admin_asset_gridproxy'),
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

        let extraParams = {
            folderId: this.element.data.id,
            "fields[]": fieldParam,
            language: this.gridLanguage,
            only_direct_children: this.onlyDirectChildren,
            only_unreferenced: this.onlyUnreferenced
        };

        //tags filter
        if (this.tagsPanel) {
            extraParams["tagIds[]"] = this.tagsTree.getCheckedTagIds();
            extraParams["considerChildTags"] = this.considerChildTags;
        }

        this.store.getProxy().extraParams = extraParams;
        this.store.setPageSize(itemsPerPage);

        if (existingFilters) {
            this.store.setFilters(existingFilters.items);
        }

        var gridColumns = gridHelper.getGridColumns();

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

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
            store: this.store,
            columnLines: true,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            columns : gridColumns,
            enableLocking: true,
            bufferedRenderer: false,
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
                    var columns = grid.grid.getColumnManager().getColumns();
                    if(columns[cellIndex].dataIndex == 'id~system' || columns[cellIndex].dataIndex == 'fullpath~system'
                        || columns[cellIndex].dataIndex == 'preview~system') {
                        var data = this.store.getAt(rowIndex);
                        pimcore.helpers.openAsset(data.id, data.get("type~system"));
                    }
                }
            },
            tbar: [
                "->",
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
        this.grid.on("lockcolumn", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));
        this.grid.on("unlockcolumn", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));

        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            var grids = grid.items.items;
            for (var i = 0; i < grids.length; i++) {
                this.updateGridHeaderContextMenu(grids[i]);
            }
        }.bind(this));

        this.layout.remove("gridPanel_" + this.element.data.id);

        this.gridPanel = new Ext.Panel({
            id: "gridPanel_" + this.element.data.id,
            region: "center",
            layout: "fit",
            items: [this.grid],
        });

        this.layout.add(this.gridPanel);
        this.layout.updateLayout();

        if (save) {
            if (this.settings.isShared) {
                this.settings.gridConfigId = null;
            }
            this.saveConfig(false);
        }
    },

    getGridColumns: function(fields) {
        var gridColumns = [];

        for (i = 0; i < fields.length; i++) {
            var field = fields[i];
            var key = field.key;
            var language = field.language;
            if (!key) {
                key = "";
            }
            if (!language) {
                language = "";
            }

            if (!field.type) {
                continue;
            }

            if(key.indexOf("~") >= 0 ) {
                key = key.substr(0, key.lastIndexOf('~'));
            }

            if (field.type == "system") {
                if(key == "preview") {
                    gridColumns.push({
                        text: t(field.label), sortable: false, dataIndex: field.key, editable: false, width: this.getColumnWidth(field, 150),
                        renderer: function (value) {
                            if (value) {
                                return '<img src="' + value + '" />';
                            }
                        }.bind(this)
                    });
                } else if (key == "creationDate" || field.key == "modificationDate") {
                    gridColumns.push({text: t(field.label), width: this.getColumnWidth(field, 150), sortable: true, dataIndex: field.key, editable: false, filter: 'date',
                       renderer: function(d) {
                            var date = new Date(d * 1000);
                            return Ext.Date.format(date, "Y-m-d H:i:s");
                        }
                    });
                } else if (key == "filename") {
                    gridColumns.push({text: t(field.label), sortable: true, dataIndex: field.key, editable: false,
                        width: this.getColumnWidth(field, 250), filter: 'string', renderer: Ext.util.Format.htmlEncode});
                } else if (key == "fullpath") {
                    gridColumns.push({text: t(field.label), sortable: true, dataIndex: field.key, editable: false,
                        width: this.getColumnWidth(field, 400), filter: 'string', renderer: Ext.util.Format.htmlEncode});
                } else if (key == "size") {
                    gridColumns.push({text: t(field.label), sortable: false, dataIndex: field.key, editable: false,
                        width: this.getColumnWidth(field, 130)});
                } else {
                    gridColumns.push({text: t(field.label),  width: this.getColumnWidth(field, 130), sortable: true,
                        dataIndex: field.key});
                }
            } else if (field.type == "date") {
                gridColumns.push({text: field.label,  width: this.getColumnWidth(field, 120), sortable: false,
                    dataIndex: field.key, filter: 'date', editable: false,
                    renderer: function(d) {
                        if (d) {
                            var date = new Date(d * 1000);
                            return Ext.Date.format(date, "Y-m-d");
                        }

                    }
                });
            } else if (field.type == "checkbox") {
                gridColumns.push(new Ext.grid.column.Check({
                    text:  field.label,
                    editable: false,
                    width: this.getColumnWidth(field, 40),
                    sortable: false,
                    filter: 'boolean',
                    dataIndex: field.key
                }));
            } else if (field.type == "select") {
                gridColumns.push({text: field.key,  width: this.getColumnWidth(field, 200), sortable: false,
                    dataIndex: field.label, filter: 'string'});
            } else if (field.type == "document" || field.type == "asset" || field.type == "object") {
                gridColumns.push({text: field.key,  width: this.getColumnWidth(field, 300), sortable: false,
                    dataIndex: field.label});
            } else {
                gridColumns.push({text: field.label,  width: this.getColumnWidth(field, 250), sortable: false,
                    dataIndex: field.key, filter: 'string'});
            }
        }

        return gridColumns;
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
                    pimcore.helpers.openAsset(data.id, data.data['type~system']);
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
                        var data = selectedRows[i];
                        pimcore.helpers.openAsset(data.id, data.data['type~system']);
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
