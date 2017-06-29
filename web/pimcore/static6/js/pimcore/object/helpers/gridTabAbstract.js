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

pimcore.registerNS("pimcore.object.helpers.gridTabAbstract");
pimcore.object.helpers.gridTabAbstract = Class.create({

    objecttype: 'object',

    filterUpdateFunction: function(grid, toolbarFilterInfo, clearFilterButton) {
        var filterStringConfig = [];
        var filterData = grid.getStore().getFilters().items;

        // reset
        toolbarFilterInfo.setTooltip(" ");

        if(filterData.length > 0) {

            for (var i=0; i < filterData.length; i++) {

                var operator = filterData[i].getOperator();
                if(operator == 'lt') {
                    operator = "&lt;";
                } else if(operator == 'gt') {
                    operator = "&gt;";
                } else if(operator == 'eq') {
                    operator = "=";
                }

                var value = filterData[i].getValue();

                if(value instanceof Date) {
                    value = Ext.Date.format(value, "Y-m-d");
                }

                if(value && typeof value == "object") {
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



    updateGridHeaderContextMenu: function(grid) {

        var columnConfig = new Ext.menu.Item({
            text: t("grid_column_config"),
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
                menu = grid.headerCt.getMenu();
                var columnDataIndex = menu.activeHeader;
                this.batchPrepare(columnDataIndex.fullColumnIndex, false);
            }.bind(this, grid)
        });
        menu.add(batchAllMenu);

        var batchSelectedMenu = new Ext.menu.Item({
            text: t("batch_change_selected"),
            iconCls: "pimcore_icon_table pimcore_icon_overlay_go",
            handler: function (grid) {
                menu = grid.headerCt.getMenu();
                var columnDataIndex = menu.activeHeader;
                this.batchPrepare(columnDataIndex.fullColumnIndex, true);
            }.bind(this, grid)
        });
        menu.add(batchSelectedMenu);
        //
        menu.on('beforeshow', function (batchAllMenu, batchSelectedMenu, grid) {
            var menu = grid.headerCt.getMenu();
            var columnDataIndex = menu.activeHeader.dataIndex;

            var view = grid.getView();
            // no batch for system properties
            if (Ext.Array.contains(this.systemColumns,columnDataIndex) || Ext.Array.contains(this.noBatchColumns, columnDataIndex)) {
                batchAllMenu.hide();
                batchSelectedMenu.hide();
            } else {
                batchAllMenu.show();
                batchSelectedMenu.show();
            }

        }.bind(this, batchAllMenu, batchSelectedMenu, grid));
    },

    batchPrepare: function(columnIndex, onlySelected){
        // no batch for system properties
        if(this.systemColumns.indexOf(this.grid.getColumns()[columnIndex].dataIndex) > -1) {
            return;
        }

        var jobs = [];
        if(onlySelected) {
            var selectedRows = this.grid.getSelectionModel().getSelection();
            for (var i=0; i<selectedRows.length; i++) {
                jobs.push(selectedRows[i].get("id"));
            }
            this.batchOpen(columnIndex,jobs);

        } else {

            var filters = "";
            var condition = "";

            if(this.sqlButton.pressed) {
                condition = this.sqlEditor.getValue();
            } else {
                var filterData = this.store.getFilters().items;
                if(filterData.length > 0) {
                    filters = this.store.getProxy().encodeFilters(filterData);
                }
            }

            var params = {
                filter: filters,
                condition: condition,
                classId: this.classId,
                folderId: this.element.id,
                objecttype: this.objecttype,
                language: this.gridLanguage
            };


            Ext.Ajax.request({
                url: "/admin/object-helper/get-batch-jobs",
                params: params,
                success: function (columnIndex,response) {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata.success && rdata.jobs) {
                        this.batchOpen(columnIndex, rdata.jobs);
                    }

                }.bind(this,columnIndex)
            });
        }

    },

    batchOpen: function (columnIndex, jobs) {

        columnIndex = columnIndex-1;

        var fieldInfo = this.grid.getColumns()[columnIndex+1].config;

        // HACK: typemapping for published (systemfields) because they have no edit masks, so we use them from the
        // data-types
        if(fieldInfo.dataIndex == "published") {
            fieldInfo.layout = {
                layout: {
                    title: t("published"),
                    name: "published"
                },
                type: "checkbox"
            };
        }
        // HACK END

        if(!fieldInfo.layout || !fieldInfo.layout.layout) {
            return;
        }

        if(fieldInfo.layout.layout.noteditable) {
            Ext.MessageBox.alert(t('error'), t('this_element_cannot_be_edited'));
            return;
        }

        var tagType = fieldInfo.layout.type;
        var editor = new pimcore.object.tags[tagType](null, fieldInfo.layout.layout);
        editor.setObject(this.object);
        editor.updateContext({
            containerType : "batch"
        });

        var formPanel = Ext.create('Ext.form.Panel', {
            xtype: "form",
            border: false,
            items: [editor.getLayoutEdit()],
            bodyStyle: "padding: 10px;",
            buttons: [
                {
                    text: t("save"),
                    handler: function() {
                        if(formPanel.isValid()) {
                            this.batchProcess(jobs, editor, fieldInfo, true);
                        }
                    }.bind(this)
                }
            ]
        });
        this.batchWin = new Ext.Window({
            modal: false,
            title: t("batch_edit_field") + " " + fieldInfo.header,
            items: [formPanel],
            bodyStyle: "background: #fff;",
            width: 700,
            maxHeight: 600
        });
        this.batchWin.show();
        this.batchWin.updateLayout();
    },

    batchProcess: function (jobs,  editor, fieldInfo, initial) {

        if(initial){

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
                text: t('Initializing'),
                style: "margin: 10px;",
                width: 500
            });

            this.batchProgressWin = new Ext.Window({
                items: [this.batchProgressBar],
                modal: true,
                bodyStyle: "background: #fff;",
                closable: false
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
                    jobErrors.push(this.batchErrors[i].job);
                }
                Ext.Msg.alert(t("error"), t("error_jobs") + ": " + jobErrors.join(","));
            }

            return;
        }

        var status = (this.batchJobCurrent / jobs.length);
        var percent = Math.ceil(status * 100);
        this.batchProgressBar.updateProgress(status, percent + "%");

        this.batchParameters.job = jobs[this.batchJobCurrent];
        Ext.Ajax.request({
            url: "/admin/object-helper/batch",
            params: this.batchParameters,
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
                        job: currentJob
                    });
                }

                window.setTimeout(function() {
                    this.batchJobCurrent++;
                    this.batchProcess(jobs);
                }.bind(this), 400);
            }.bind(this,jobs, this.batchParameters.job)
        });
    },

    openColumnConfig: function() {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for(var i = 0; i < fieldKeys.length; i++) {
            if(!fields[fieldKeys[i]].hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: fields[fieldKeys[i]].fieldConfig.label,
                    dataType: fields[fieldKeys[i]].fieldConfig.type,
                    layout: fields[fieldKeys[i]].fieldConfig.layout
                };
                if (fields[fieldKeys[i]].fieldConfig.width) {
                    fc.width = fields[fieldKeys[i]].fieldConfig.width;
                }
                visibleColumns.push(fc);
            }
        }

        var objectId;
        if(this["object"] && this.object["id"]) {
            objectId = this.object.id;
        } else if (this["element"] && this.element["id"]) {
            objectId = this.element.id;
        }

        var columnConfig = {
            language: this.gridLanguage,
            classid: this.classId,
            objectId: objectId,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig, function(data) {
                this.gridLanguage = data.language;
                this.createGrid(true, data.columns);
            }.bind(this),
            function() {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-delete-column-config",
                    params: {
                        id: objectId,
                        class_id: this.classId,
                        searchType: this.searchType
                    },
                    success: function() {
                        Ext.Ajax.request({
                            url: "/admin/object-helper/grid-get-column-config",
                            params: {
                                id: this.classId,
                                objectId: this.object.id,
                                gridtype: "grid",
                                searchType: this.searchType
                            },
                            success: function(response) {
                                response = Ext.decode(response.responseText);
                                if (response) {
                                    fields = response.availableFields;
                                    this.createGrid(false, fields);
                                    if (typeof this.saveColumnConfigButton !== "undefined") {
                                        this.saveColumnConfigButton.hide();
                                    }
                                } else {
                                    pimcore.helpers.showNotification(t("error"), t("error_resetting_config"),
                                        "error",t(rdata.message));
                                }
                            }.bind(this),
                            failure: function () {
                                pimcore.helpers.showNotification(t("error"), t("error_resetting_config"), "error");
                            }
                        });
                    }.bind(this)
                });
            }.bind(this)
        )

    },

    createGrid: function(columnConfig) {

    },

    getGridConfig : function () {
        var config = {
            language: this.gridLanguage,
            sortinfo: this.sortinfo,
            classId: this.classId,
            columns: {}
        };

        //var header = this.grid.getHeader();
        var cm = this.grid.getView().getHeaderCt().getGridColumns();
        //var cm = this.grid.getColumnModel();
        for (var i=0; i < cm.length; i++) {
            if(cm[i].dataIndex) {
                config.columns[cm[i].dataIndex] = {
                    name: cm[i].dataIndex,
                    position: i,
                    hidden: cm[i].hidden,
                    width: cm[i].width,
                    fieldConfig: this.fieldObject[cm[i].dataIndex]
                };
            }
        }

        return config;
    },


    exportPrepare: function(){
        var jobs = [];

        var filters = "";
        var condition = "";

        if(this.sqlButton.pressed) {
            condition = this.sqlEditor.getValue();
        } else {
            var filterData = this.store.getFilters().items;
            if(filterData.length > 0) {
                filters = this.store.getProxy().encodeFilters(filterData);
            }
        }


        var fields = this.getGridConfig().columns;
        var fieldKeys = Object.keys(fields);

        //create the ids array which contains chosen rows to export
        ids = [];
        var selectedRows = this.grid.getSelectionModel().getSelection();
        for (var i = 0; i < selectedRows.length; i++) {
            ids.push(selectedRows[i].data.id);
        }

        var params = {
            filter: filters,
            condition: condition,
            classId: this.classId,
            folderId: this.element.id,
            objecttype: this.objecttype,
            language: this.gridLanguage,
            "ids[]": ids,
            "fields[]": fieldKeys
        };


        Ext.Ajax.request({
            url: "/admin/object-helper/get-export-jobs",
            params: params,
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success && rdata.jobs) {
                    this.exportProcess(rdata.jobs, rdata.fileHandle, fieldKeys, true);
                }

            }.bind(this)
        });
    },

    exportProcess: function (jobs, fileHandle, fields, initial) {

        if(initial){

            this.exportErrors = [];
            this.exportJobCurrent = 0;

            this.exportParameters = {
                fileHandle: fileHandle,
                language: this.gridLanguage
            };
            this.exportProgressBar = new Ext.ProgressBar({
                text: t('Initializing'),
                style: "margin: 10px;",
                width: 500
            });

            this.exportProgressWin = new Ext.Window({
                items: [this.exportProgressBar],
                modal: true,
                bodyStyle: "background: #fff;",
                closable: false
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
                pimcore.helpers.download("/admin/object-helper/download-csv-file?fileHandle=" + fileHandle);
            }

            return;
        }

        var status = (this.exportJobCurrent / jobs.length);
        var percent = Math.ceil(status * 100);
        this.exportProgressBar.updateProgress(status, percent + "%");

        this.exportParameters['ids[]'] = jobs[this.exportJobCurrent];
        this.exportParameters["fields[]"] = fields;
        this.exportParameters.classId = this.classId;
        this.exportParameters.initial = initial;

        Ext.Ajax.request({
            url: "/admin/object-helper/do-export",
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

                window.setTimeout(function() {
                    this.exportJobCurrent++;
                    this.exportProcess(jobs, fileHandle, fields);
                }.bind(this), 400);
            }.bind(this,jobs, jobs[this.exportJobCurrent])
        });
    },

    createSqlEditor: function() {
        this.sqlEditor = new Ext.form.TextField({
            xtype: "textfield",
            width: 500,
            name: "condition",
            hidden: true,
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var proxy = this.store.getProxy();
                        proxy.setExtraParams(
                            {
                                class: proxy.extraParams.class,
                                objectId: proxy.extraParams.objectId,
                                "fields[]": proxy.extraParams["fields[]"]
                            }
                        );
                        proxy.setExtraParam("condition", field.getValue());
                        this.grid.filters.clearFilters();

                        this.pagingtoolbar.moveFirst();
                    }
                }.bind(this)
            }
        });

        this.sqlButton = new Ext.Button({
            iconCls: "pimcore_icon_sql",
            enableToggle: true,
            tooltip: t("direct_sql_query"),
            handler: function (button) {

                this.sqlEditor.setValue("");

                // reset base params, because of the condition
                var proxy = this.store.getProxy();
                proxy.setExtraParams(
                    {
                        class: proxy.extraParams.class,
                        objectId: proxy.extraParams.objectId,
                        "fields[]": proxy.extraParams["fields[]"]
                    }
                );

                this.grid.filters.clearFilters();

                this.pagingtoolbar.moveFirst();

                if(button.pressed) {
                    this.sqlEditor.show();
                } else {
                    this.sqlEditor.hide();
                }
            }.bind(this)
        });
    }


});
