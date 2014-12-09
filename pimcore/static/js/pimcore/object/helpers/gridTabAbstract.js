/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.helpers.gridTabAbstract");
pimcore.object.helpers.gridTabAbstract = Class.create({

    objecttype: 'object',

    filterUpdateFunction: function(gridfilters, toolbarFilterInfo) {
        var filterString = "";
        var filterStringConfig = [];
        var filterData = gridfilters.getFilterData();
        var operator;

        // reset
        toolbarFilterInfo.setText(" ");

        if(filterData.length > 0) {

            for (var i=0; i<filterData.length; i++) {

                operator = "=";
                if (filterData[i].data.type == "string") {
                    operator = "LIKE";
                } else if (filterData[i].data.type == "numeric" || filterData[i].data.type == "date") {
                    if(filterData[i].data.comparison == "lt") {
                        operator = "&lt;";
                    } else if(filterData[i].data.comparison == "gt") {
                        operator = "&gt;";
                    }
                } else if (filterData[i].data.type == "boolean") {
                    filterData[i].value = filterData[i].data.value ? "true" : "false";
                }

                if(filterData[i].data.value && typeof filterData[i].data.value == "object") {
                    filterStringConfig.push(filterData[i].field + " " + operator + " ("
                        + filterData[i].data.value.join(" OR ") + ")");
                } else {
                    filterStringConfig.push(filterData[i].field + " " + operator + " " + filterData[i].data.value);
                }
            }

            toolbarFilterInfo.setText("<b>" + t("filter_condition") + ": " + filterStringConfig.join(" AND ") + "</b>");
        }
    },



    updateGridHeaderContextMenu: function(grid) {
        var columnConfig = new Ext.menu.Item({
            text: t("grid_column_config"),
            iconCls: "pimcore_icon_grid_column_config",
            handler: this.openColumnConfig.bind(this)
        });
        grid.getView().hmenu.add(columnConfig);

        var batchAllMenu = new Ext.menu.Item({
            text: t("batch_change"),
            iconCls: "pimcore_icon_batch",
            handler: function (view) {
                this.batchPrepare(view.hdCtxIndex, false);
            }.bind(this, grid.getView())
        });
        grid.getView().hmenu.add(batchAllMenu);

        var batchSelectedMenu = new Ext.menu.Item({
            text: t("batch_change_selected"),
            iconCls: "pimcore_icon_batch",
            handler: function (view) {
                this.batchPrepare(view.hdCtxIndex, true);
            }.bind(this, grid.getView())
        });
        grid.getView().hmenu.add(batchSelectedMenu);

        grid.getView().hmenu.on('beforeshow', function (batchAllMenu, batchSelectedMenu, view) {
            // no batch for system properties
            if(this.systemColumns.indexOf(view.cm.config[view.hdCtxIndex].dataIndex) < 0) {
                batchAllMenu.show();
                batchSelectedMenu.show();
            } else {
                batchAllMenu.hide();
                batchSelectedMenu.hide();
            }

        }.bind(this, batchAllMenu, batchSelectedMenu, grid.getView()));
    },

    batchPrepare: function(columnIndex, onlySelected){
        // no batch for system properties
        if(this.systemColumns.indexOf(this.grid.getColumnModel().config[columnIndex].dataIndex) > -1) {
            return;
        }

        var jobs = [];
        if(onlySelected) {
            var selectedRows = this.grid.getSelectionModel().getSelections();
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
                var filterData = this.gridfilters.getFilterData();
                if(filterData.length > 0) {
                    filters = this.gridfilters.buildQuery(filterData).filter;
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

        var fieldInfo = this.grid.getColumnModel().config[columnIndex+1];

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
        if (tagType == "keyValue") {
            var gridType = fieldInfo.layout.layout.gridType;
            if (gridType == "select") {
                tagType ="select";
            } else if (gridType == "number") {
                tagType = "numeric";
            } else if (gridType == "bool") {
                tagType = "checkbox";
            }  else {
                tagType ="input";
            }
        }

        var editor = new pimcore.object.tags[tagType](null, fieldInfo.layout.layout);
        this.batchWin = new Ext.Window({
            modal: false,
            title: t("batch_edit_field") + " " + fieldInfo.header,
            items: [
                {
                    xtype: "form",
                    border: false,
                    items: [editor.getLayoutEdit()],
                    bodyStyle: "padding: 10px;",
                    buttons: [
                        {
                            text: t("save"),
                            handler: this.batchProcess.bind(this, jobs, editor, fieldInfo, true)
                        }
                    ]
                }
            ],
            bodyStyle: "background: #fff;",
            width: 700
        });
        this.batchWin.show();

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
                valueType: valueType
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
                pimcore.globalmanager.get("layout_object_tree").tree.getRootNode().reload();
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
            this.createGrid(data.columns);
        }.bind(this) );
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
        var cm = this.grid.getColumnModel();
        for (var i=0; i<cm.config.length; i++) {
            if(cm.config[i].dataIndex) {
                config.columns[cm.config[i].dataIndex] = {
                    name: cm.config[i].dataIndex,
                    position: i,
                    hidden: cm.config[i].hidden,
                    width: cm.config[i].width,
                    fieldConfig: this.fieldObject[cm.config[i].dataIndex]
                };
            }
        }

        return config;
    },

    startCsvExport: function () {
        var values = [];
        var filters = "";
        var condition = "";

        if(this.sqlButton.pressed) {
            condition = this.sqlEditor.getValue();
        } else {
            var filterData = this.gridfilters.getFilterData();
            if(filterData.length > 0) {
                filters = this.gridfilters.buildQuery(filterData).filter;
            }
        }

        var path = "/admin/object-helper/export/classId/" + this.classId + "/folderId/" + this.element.id ;
        path = path + "/?" + Ext.urlEncode({
            filter: filters,
            condition: condition,
            objecttype: this.objecttype
        });
        pimcore.helpers.download(path);
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
                        this.gridfilters.clearFilters();

                        this.store.baseparams = {};
                        this.store.setBaseParam("condition", field.getValue());

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

                this.gridfilters.clearFilters();
                this.sqlEditor.setValue("");

                // reset base params, because of the condition
                this.store.baseparams = {};
                this.store.setBaseParam("condition", null);
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