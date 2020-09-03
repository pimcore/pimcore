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


pimcore.registerNS("pimcore.element.helpers.gridColumnConfig");
pimcore.element.helpers.gridColumnConfig = {

    batchJobDelay: 50,

    getSaveAsDialog: function () {
        var defaultName = new Date();

        var nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            length: 50,
            allowBlank: false,
            value: this.settings.gridConfigName ? this.settings.gridConfigName : defaultName
        });

        var descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            // height: 200,
            value: this.settings.gridConfigDescription
        });

        var configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [nameField, descriptionField],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.settings.gridConfigId = null;
                    this.settings.gridConfigName = nameField.getValue();
                    this.settings.gridConfigDescription = descriptionField.getValue();

                    pimcore.helpers.saveColumnConfig(this.object.id, this.classId, this.getGridConfig(), this.searchType, this.saveColumnConfigButton,
                        this.columnConfigurationSavedHandler.bind(this), this.settings, this.gridType);
                    this.saveWindow.close();
                }.bind(this)
            }]
        });

        this.saveWindow = new Ext.Window({
            width: 600,
            height: 300,
            modal: true,
            title: t('save_as_copy'),
            layout: "fit",
            items: [configPanel]
        });

        this.saveWindow.show();
        nameField.focus();
        nameField.selectText();
        return this.window;
    },

    deleteGridConfig: function () {

        Ext.MessageBox.show({
            title: t('delete'),
            msg: t('delete_message'),
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.MessageBox.INFO,
            fn: this.deleteGridConfigConfirmed.bind(this)
        });
    },

    deleteGridConfigConfirmed: function (btn) {
        var route = null;

        if (this.gridType === 'asset') {
            route = 'pimcore_admin_asset_assethelper_griddeletecolumnconfig';
        }
        else if(this.gridType === 'object') {
            route = 'pimcore_admin_dataobject_dataobjecthelper_griddeletecolumnconfig';
        }
        else {
            throw new Error('Type unknown');
        }

        if (btn === 'ok') {
            Ext.Ajax.request({
                url: Routing.generate(route),
                method: "DELETE",
                params: {
                    id: this.classId,
                    objectId:
                    this.object.id,
                    gridtype: "grid",
                    gridConfigId: this.settings.gridConfigId,
                    searchType: this.searchType
                },
                success: function (response) {

                    decodedResponse = Ext.decode(response.responseText);
                    if (!decodedResponse.deleteSuccess) {
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_item"), "error");
                    }

                    this.createGrid(false, response);
                }.bind(this)
            });
        }
    },

    switchToGridConfig: function (menuItem) {
        var gridConfig = menuItem.gridConfig;
        this.settings.gridConfigId = gridConfig.id;
        this.getTableDescription();
    },

    addGridConfigMenuItems: function(menu, list, onlyConfigs) {
        for (var i = 0; i < list.length; i++) {
            var disabled = false;
            var config = list[i];
            var text = config["name"];
            if (config.id == this.settings.gridConfigId) {
                text = this.settings.gridConfigName;
                if (!onlyConfigs) {
                    text = "<b>" + text + "</b>";
                    disabled = true;
                }
            }
            var menuConfig = {
                text: text,
                disabled: disabled,
                iconCls: 'pimcore_icon_gridcolumnconfig',
                gridConfig: config,
                handler: this.switchToGridConfig.bind(this)
            };
            menu.add(menuConfig);
        }
    },

    buildColumnConfigMenu: function (onlyConfigs) {
        var menu = this.columnConfigButton.getMenu();
        menu.removeAll();

        if (!onlyConfigs) {
            menu.add({
                text: t('save_as_copy'),
                iconCls: "pimcore_icon_save",
                handler: this.saveConfig.bind(this, true)
            });

            menu.add({
                text: t('set_as_favourite'),
                iconCls: "pimcore_icon_favourite",
                handler: function () {
                    pimcore.helpers.markColumnConfigAsFavourite(this.object.id, this.classId, this.settings.gridConfigId, this.searchType, true, this.gridType);
                }.bind(this)
            });

            menu.add({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                disabled: !this.settings.gridConfigId || this.settings.isShared,
                handler: this.deleteGridConfig.bind(this)
            });

            menu.add('-');
        }

        var disabled = false;
        var text = t('predefined');
        if (!this.settings.gridConfigId && !onlyConfigs) {
            text = "<b>" + text + "</b>";
            disabled = true;

        }

        menu.add({
            text: text,
            iconCls: "pimcore_icon_gridcolumnconfig",
            disabled: disabled,
            gridConfig: {
                id: 0
            },
            handler: this.switchToGridConfig.bind(this)
        });

        if (this.availableConfigs && this.availableConfigs.length > 0) {
            this.addGridConfigMenuItems(menu, this.availableConfigs, onlyConfigs);
        }

        if (this.sharedConfigs && this.sharedConfigs.length > 0) {
            menu.add('-');
            this.addGridConfigMenuItems(menu, this.sharedConfigs, onlyConfigs);
        }
    },

    saveConfig: function (asCopy, context) {
        if (asCopy) {
            this.getSaveAsDialog();
        } else {
            pimcore.helpers.saveColumnConfig(this.object.id, this.classId, this.getGridConfig(), this.searchType, this.saveColumnConfigButton,
                this.columnConfigurationSavedHandler.bind(this), this.settings, this.gridType, this.context);
        }
    },

    filterUpdateFunction: function (grid, toolbarFilterInfo, clearFilterButton) {
        var filterStringConfig = [];
        var filterData = grid.getStore().getFilters().items;

        // reset
        toolbarFilterInfo.setTooltip(" ");

        if (filterData.length > 0) {

            for (var i = 0; i < filterData.length; i++) {

                var operator = filterData[i].getOperator();
                if (operator == 'lt') {
                    operator = "&lt;";
                } else if (operator == 'gt') {
                    operator = "&gt;";
                } else if (operator == 'eq') {
                    operator = "=";
                }

                var value = filterData[i].getValue();

                if (value instanceof Date) {
                    value = Ext.Date.format(value, "Y-m-d");
                }

                if (value && typeof value == "object") {
                    filterStringConfig.push(filterData[i].getProperty() + " " + operator + " ("
                        + value.join(" OR ") + ")");
                } else {
                    filterStringConfig.push(filterData[i].getProperty() + " " + operator + " " + value);
                }
            }

            var filterCondition = filterStringConfig.join(" AND ") + "</b>";
            toolbarFilterInfo.setTooltip("<b>" + t("filter_condition") + ": " + filterCondition);
            toolbarFilterInfo.pimcore_filter_condition = filterCondition;
            toolbarFilterInfo.setHidden(false);
        }
        toolbarFilterInfo.setHidden(filterData.length == 0);
        clearFilterButton.setHidden(!toolbarFilterInfo.isVisible());
    },

    updateGridHeaderContextMenu: function (grid) {
        var columnConfig = new Ext.menu.Item({
            text: t("grid_options"),
            iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
            handler: this.openColumnConfig.bind(this)
        });
        var menu = grid.headerCt.getMenu();
        menu.add(columnConfig);
        //
        var batchAllMenu = new Ext.menu.Item({
            text: t("batch_change"),
            iconCls: "pimcore_icon_table pimcore_icon_overlay_go",
            handler: function (grid) {
                var menu = grid.headerCt.getMenu();
                var column = menu.activeHeader;
                this.batchPrepare(column, false, false, false);
            }.bind(this, grid)
        });
        menu.add(batchAllMenu);

        var batchSelectedMenu = new Ext.menu.Item({
            text: t("batch_change_selected"),
            iconCls: "pimcore_icon_structuredTable pimcore_icon_overlay_go",
            handler: function (grid) {
                var menu = grid.headerCt.getMenu();
                var column = menu.activeHeader;
                this.batchPrepare(column, true, false, false);
            }.bind(this, grid)
        });
        menu.add(batchSelectedMenu);

        var batchAppendAllMenu = new Ext.menu.Item({
            text: t("batch_append_all"),
            iconCls: "pimcore_icon_table pimcore_icon_overlay_go",
            handler: function (grid) {
                var menu = grid.headerCt.getMenu();
                var column = menu.activeHeader;
                this.batchPrepare(column, false, true, false);
            }.bind(this, grid)
        });
        menu.add(batchAppendAllMenu);

        var batchAppendSelectedMenu = new Ext.menu.Item({
            text: t("batch_append_selected"),
            iconCls: "pimcore_icon_structuredTable pimcore_icon_overlay_go",
            handler: function (grid) {
                var menu = grid.headerCt.getMenu();
                var column = menu.activeHeader;
                this.batchPrepare(column, true, true, false);
            }.bind(this, grid)
        });
        menu.add(batchAppendSelectedMenu);


        var batchRemoveAllMenu = new Ext.menu.Item({
            text: t("batch_remove_all"),
            iconCls: "pimcore_icon_table pimcore_icon_overlay_go",
            handler: function (grid) {
                var menu = grid.headerCt.getMenu();
                var column = menu.activeHeader;
                this.batchPrepare(column, false, false, true);
            }.bind(this, grid)
        });
        menu.add(batchRemoveAllMenu);

        var batchRemoveSelectedMenu = new Ext.menu.Item({
            text: t("batch_remove_selected"),
            iconCls: "pimcore_icon_structuredTable pimcore_icon_overlay_go",
            handler: function (grid) {
                var menu = grid.headerCt.getMenu();
                var column = menu.activeHeader;
                this.batchPrepare(column, true, false, true);
            }.bind(this, grid)
        });
        menu.add(batchRemoveSelectedMenu);

        //
        menu.on('beforeshow', function (batchAllMenu, batchSelectedMenu, grid) {
            var menu = grid.headerCt.getMenu();
            var columnDataIndex = menu.activeHeader.dataIndex;

            // no batch for system properties
            if (Ext.Array.contains(this.systemColumns, columnDataIndex) || Ext.Array.contains(this.noBatchColumns, columnDataIndex)) {
                batchAllMenu.hide();
                batchSelectedMenu.hide();
            } else {
                batchAllMenu.show();
                batchSelectedMenu.show();
            }

            if (!Ext.Array.contains(this.systemColumns, columnDataIndex) && Ext.Array.contains(this.batchAppendColumns ? this.batchAppendColumns : [], columnDataIndex)) {
                batchAppendAllMenu.show();
                batchAppendSelectedMenu.show();
            } else {
                batchAppendAllMenu.hide();
                batchAppendSelectedMenu.hide();
            }

            if (!Ext.Array.contains(this.systemColumns,columnDataIndex) && Ext.Array.contains(this.batchRemoveColumns ? this.batchRemoveColumns : [], columnDataIndex)) {
                batchRemoveAllMenu.show();
                batchRemoveSelectedMenu.show();
            } else {
                batchRemoveAllMenu.hide();
                batchRemoveSelectedMenu.hide();
            }
        }.bind(this, batchAllMenu, batchSelectedMenu, grid));
    },

    batchPrepare: function (column, onlySelected, append, remove) {
        var dataIndexName = column.dataIndex
        var gridColumns = this.grid.getColumns();
        var columnIndex = -1;
        for (let i = 0; i < gridColumns.length; i++) {
            let dataIndex = gridColumns[i].dataIndex;
            if (dataIndex == dataIndexName) {
                columnIndex = i;
                break;
            }
        }
        if (columnIndex < 0) {
            return;
        }

        // no batch for system properties

        if (this.systemColumns.indexOf(gridColumns[columnIndex].dataIndex) > -1) {
            return;
        }

        var jobs = [];
        if (onlySelected) {
            var selectedRows = this.grid.getSelectionModel().getSelection();
            for (var i = 0; i < selectedRows.length; i++) {
                jobs.push(selectedRows[i].get("id"));
            }
            this.batchOpen(columnIndex, jobs, append, remove, onlySelected);

        } else {
            let params = this.getGridParams(onlySelected);
            Ext.Ajax.request({
                url: this.batchPrepareUrl,
                params: params,
                success: function (columnIndex, response) {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata.success && rdata.jobs) {
                        this.batchOpen(columnIndex, rdata.jobs, append, remove, onlySelected);
                    }

                }.bind(this, columnIndex)
            });
        }

    },

    batchOpen: function (columnIndex, jobs, append, remove, onlySelected) {

        columnIndex = columnIndex - 1;

        var fieldInfo = this.grid.getColumns()[columnIndex + 1].config;

        // HACK: typemapping for published (systemfields) because they have no edit masks, so we use them from the
        // data-types
        if (fieldInfo.dataIndex == "published") {
            fieldInfo.layout = {
                layout: {
                    title: t("published"),
                    name: "published",
                    hideEmptyButton: true
                },
                type: "checkbox"
            };
        }
        // HACK END

        if((this.objecttype === "object") || (this.objecttype === "variant")) {
            if (!fieldInfo.layout || !fieldInfo.layout.layout) {
                return;
            }

            if (fieldInfo.layout.layout.noteditable) {
                Ext.MessageBox.alert(t('error'), t('this_element_cannot_be_edited'));
                return;
            }

            var tagType = fieldInfo.layout.type;
            var editor = new pimcore.object.tags[tagType](null, fieldInfo.layout.layout);
            editor.setObject(this.object);
        } else {
            var tagType = this.fieldObject[fieldInfo.dataIndex].layout.fieldtype;
            let layoutInfo = this.fieldObject[fieldInfo.dataIndex].layout
            try {
                if (typeof pimcore.asset.metadata.tags[tagType].prototype.prepareBatchEditLayout == "function") {
                    layoutInfo = pimcore.asset.metadata.tags[tagType].prototype.prepareBatchEditLayout(layoutInfo);
                }
            } catch (e) {
                console.log(e);
            }

            var editor = new pimcore.asset.metadata.tags[tagType](null, layoutInfo);
            editor.setAsset(this.asset);
        }

        editor.updateContext({
            containerType: "batch"
        });

        var formPanel = Ext.create('Ext.form.Panel', {
            xtype: "form",
            border: false,
            items: [editor.getLayoutEdit()],
            bodyStyle: "padding: 10px;",
            buttons: [
                {
                    text: t("save"),
                    handler: function () {
                        if (formPanel.isValid()) {
                            this.batchProcess(jobs, append, remove, editor, fieldInfo, true);
                        }
                    }.bind(this)
                }
            ]
        });
        var batchTitle = onlySelected ? "batch_edit_field_selected" : "batch_edit_field";
        var appendTitle = onlySelected ? "batch_append_selected_to" : "batch_append_to";
        var removeTitle = onlySelected ? "batch_remove_selected_from" : "batch_remove_from";
        var title = remove ? t(removeTitle) + " " + fieldInfo.text : (append ? t(appendTitle) + " " + fieldInfo.text : t(batchTitle) + " " + fieldInfo.text);
        this.batchWin = new Ext.Window({
            autoScroll: true,
            modal: false,
            title: title,
            items: [formPanel],
            bodyStyle: "background: #fff;",
            width: 700,
            maxHeight: 600
        });
        this.batchWin.show();
        this.batchWin.updateLayout();
    },

    batchProcess: function (jobs, append,  remove, editor, fieldInfo, initial) {
        if (initial) {
            this.batchErrors = [];
            this.batchJobCurrent = 0;

            var newValue = editor.getValue();

            var valueType = "primitive";
            if (newValue && typeof newValue == "object") {
                newValue = Ext.encode(newValue);
                valueType = "object";
            }

            this.batchParameters = {
                name: fieldInfo.dataIndex,
                value: newValue,
                valueType: valueType,
                language: this.gridLanguage
            };


            this.batchWin.close();

            this.batchProgressBar = new Ext.ProgressBar({
                text: t('initializing'),
                style: "margin: 10px;",
                width: 500
            });

            this.batchProgressWin = new Ext.Window({
                title: t('batch_operation'),
                items: [this.batchProgressBar],
                layout: 'fit',
                width: 400,
                bodyStyle: "padding: 10px;",
                closable: false,
                plain: true,
                modal: true
            });

            this.batchProgressWin.show();

        }

        if (this.batchJobCurrent >= jobs.length) {
            this.batchProgressWin.close();
            this.pagingtoolbar.moveFirst();
            try {
                var tree = pimcore.globalmanager.get("layout_object_tree").tree;
                tree.getStore().load({
                    node: tree.getRootNode()
                });
            } catch (e) {
                console.log(e);
            }

            // error handling
            if (this.batchErrors.length > 0) {
                var jobErrors = [];
                for (var i = 0; i < this.batchErrors.length; i++) {
                    jobErrors.push(this.batchErrors[i].job + ' - ' + this.batchErrors[i].error);
                }
                Ext.Msg.alert(t("error"), t("error_jobs") + ":<br>" + jobErrors.join("<br>"));
            }

            return;
        }

        var status = (this.batchJobCurrent / jobs.length);
        var percent = Math.ceil(status * 100);
        this.batchProgressBar.updateProgress(status, percent + "%");

        this.batchParameters.job = jobs[this.batchJobCurrent];
        if (append) {
            this.batchParameters.append = 1;
        }
        if (remove) {
            this.batchParameters.remove = 1;
        }

        Ext.Ajax.request({
            url: this.batchProcessUrl,
            method: 'PUT',
            params: {
                data: Ext.encode(this.batchParameters)
            },
            success: function (jobs, currentJob, response) {

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata) {
                        if (!rdata.success) {
                            throw "not successful";
                        }
                    }
                } catch (e) {
                    this.batchErrors.push({
                        job: currentJob,
                        error: (typeof(rdata.message) !== "undefined" && rdata.message) ?
                            rdata.message : 'Not Successful'
                    });
                }

                window.setTimeout(function () {
                    this.batchJobCurrent++;
                    this.batchProcess(jobs, append, remove);
                }.bind(this), this.batchJobDelay);
            }.bind(this, jobs, this.batchParameters.job)
        });
    },

    exportPrepare: function (settings, exportType) {
        let params = this.getGridParams();

        var fields = this.getGridConfig().columns;
        var fieldKeys = Object.keys(fields);
        params["fields[]"] = fieldKeys;
        if (this.context) {
            params["context"] = Ext.encode(this.context);
        }

        settings = Ext.encode(settings);
        params["settings"] = settings;
        Ext.Ajax.request({
            url: this.exportPrepareUrl,
            params: params,
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success && rdata.jobs) {
                    this.exportProcess(rdata.jobs, rdata.fileHandle, fieldKeys, true, settings, exportType);
                }
            }.bind(this)
        });
    },

    exportProcess: function (jobs, fileHandle, fields, initial, settings, exportType) {
        if (initial) {
            this.exportErrors = [];
            this.exportJobCurrent = 0;

            this.exportParameters = {
                fileHandle: fileHandle,
                language: this.gridLanguage,
                settings: settings
            };
            this.exportProgressBar = new Ext.ProgressBar({
                text: t('initializing'),
                style: "margin: 10px;",
                width: 500
            });

            this.exportProgressWin = new Ext.Window({
                title: t("export"),
                items: [this.exportProgressBar],
                layout: 'fit',
                width: 200,
                bodyStyle: "padding: 10px;",
                closable: false,
                plain: true,
                listeners: pimcore.helpers.getProgressWindowListeners()
            });
            this.exportProgressWin.show();
        }

        if (this.exportJobCurrent >= jobs.length) {
            this.exportProgressWin.close();

            // error handling
            if (this.exportErrors.length > 0) {
                var jobErrors = [];
                for (var i = 0; i < this.exportErrors.length; i++) {
                    jobErrors.push(this.exportErrors[i].job);
                }
                Ext.Msg.alert(t("error"), t("error_jobs") + ": " + jobErrors.join(","));
            } else {
                pimcore.helpers.download(exportType.getDownloadUrl(fileHandle));
            }

            return;
        }

        var status = (this.exportJobCurrent / jobs.length);
        var percent = Math.ceil(status * 100);
        this.exportProgressBar.updateProgress(status, percent + "%");

        this.exportParameters['ids[]'] = jobs[this.exportJobCurrent];
        this.exportParameters["fields[]"] = fields;
        this.exportParameters.classId = this.classId;
        this.exportParameters.initial = initial ? 1 : 0;
        this.exportParameters.language = this.gridLanguage;
        this.exportParameters.context = Ext.encode(this.context);

        Ext.Ajax.request({
            url: this.exportProcessUrl,
            method: 'POST',
            params: this.exportParameters,
            success: function (jobs, currentJob, response) {

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata) {
                        if (!rdata.success) {
                            throw "not successful";
                        }
                    }
                } catch (e) {
                    this.exportErrors.push({
                        job: currentJob
                    });
                }

                window.setTimeout(function () {
                    this.exportJobCurrent++;
                    this.exportProcess(jobs, fileHandle, fields, false, settings, exportType);
                }.bind(this), this.batchJobDelay);
            }.bind(this, jobs, jobs[this.exportJobCurrent])
        });
    },

    columnConfigurationSavedHandler: function (rdata) {
        this.settings = rdata.settings;
        this.availableConfigs = rdata.availableConfigs;
        this.buildColumnConfigMenu();
    },

    getGridParams: function (onlySelected) {
        var filters = "";
        var condition = "";
        var searchQuery = this.searchField ? this.searchField.getValue() : "";

        if (this.sqlButton && this.sqlButton.pressed) {
            condition = this.sqlEditor.getValue();
        } else {
            var filterData = this.store.getFilters().items;
            if (filterData.length > 0) {
                filters = this.store.getProxy().encodeFilters(filterData);
            }
        }

        var params = {
            filter: filters,
            condition: condition,
            classId: this.classId,
            folderId: this.element.id,
            objecttype: this.objecttype,
            language: this.gridLanguage,
            batch: true, // to avoid limit for export
        };

        if (searchQuery) {
            params["query"] = searchQuery;
        }

        if (onlySelected !== false) {
            //create the ids array which contains chosen rows to export
            ids = [];
            var selectedRows = this.grid.getSelectionModel().getSelection();
            for (var i = 0; i < selectedRows.length; i++) {
                ids.push(selectedRows[i].data.id);
            }

            if (ids.length > 0) {
                params["ids[]"] = ids;
            }
        }

        //tags filter
        if(this.tagsTree) {
            params["tagIds[]"] = this.tagsTree.getCheckedTagIds();

            if(this.tagsPanel) {
                params["considerChildTags"] = this.tagsPanel.considerChildTags;
            }
        }

        //only direct children filter
        if (this.checkboxOnlyDirectChildren) {
            params["only_direct_children"] = this.checkboxOnlyDirectChildren.getValue();
        }

        //only unreferenced filter
        if (this.checkboxOnlyUnreferenced) {
            params["only_unreferenced"] = this.checkboxOnlyUnreferenced.getValue();
        }

        return params;

    }
};
