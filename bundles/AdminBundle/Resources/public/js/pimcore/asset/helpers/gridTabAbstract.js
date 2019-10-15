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

pimcore.registerNS("pimcore.asset.helpers.gridTabAbstract");
pimcore.asset.helpers.gridTabAbstract = Class.create({

    createGrid: function (columnConfig) {
    },

    openColumnConfig: function (allowPreview) {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for (var i = 0; i < fieldKeys.length; i++) {
            var field = fields[fieldKeys[i]];
            if (!field.hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: field.fieldConfig.label,
                    dataType: field.fieldConfig.type,
                    layout: field.fieldConfig.layout
                };
                if (field.fieldConfig.width) {
                    fc.width = field.fieldConfig.width;
                }

                if (field.isOperator) {
                    fc.isOperator = true;
                    fc.attributes = field.fieldConfig.attributes;

                }

                visibleColumns.push(fc);
            }
        }

        var objectId;
        if (this["object"] && this.object["id"]) {
            objectId = this.object.id;
        } else if (this["element"] && this.element["id"]) {
            objectId = this.element.id;
        }

        var columnConfig = {
            language: this.gridLanguage,
            pageSize: this.gridPageSize,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.asset.helpers.gridConfigDialog(columnConfig, function (data, settings, save) {
                this.gridLanguage = data.language;
                this.gridPageSize = data.pageSize;
                this.createGrid(true, data.columns, settings, save);
            }.bind(this),
            function () {
                Ext.Ajax.request({
                    url: "/admin/asset-helper/grid-get-column-config",
                    params: {
                        gridtype: "grid",
                        searchType: this.searchType
                    },
                    success: function (response) {
                        response = Ext.decode(response.responseText);
                        if (response) {
                            fields = response.availableFields;
                            this.createGrid(false, fields, response.settings, false);
                            if (typeof this.saveColumnConfigButton !== "undefined") {
                                this.saveColumnConfigButton.hide();
                            }
                        } else {
                            pimcore.helpers.showNotification(t("error"), t("error_resetting_config"),
                                "error", t(rdata.message));
                        }
                    }.bind(this),
                    failure: function () {
                        pimcore.helpers.showNotification(t("error"), t("error_resetting_config"), "error");
                    }
                });
            }.bind(this),
            true,
            this.settings,
            {
                allowPreview: true,
                folderId: this.element.id
            }
        )

    },

    getGridConfig: function () {
        var config = {
            language: this.gridLanguage,
            pageSize: this.gridPageSize,
            sortinfo: this.sortinfo,
            columns: {}
        };

        var cm = this.grid.getView().getHeaderCt().getGridColumns();
        console.log(cm);

        for (var i = 0; i < cm.length; i++) {
            if (cm[i].dataIndex) {
                var name = cm[i].dataIndex;
                //preview column uses data index ID
                if(cm[i].text == "Preview") {
                    name = "preview";
                    console.log(this.fieldObject[name]);
                }
                config.columns[name] = {
                    name: name,
                    position: i,
                    hidden: cm[i].hidden,
                    width: cm[i].width,
                    fieldConfig: this.fieldObject[name],
                    //isOperator: this.fieldObject[name].isOperator
                };
            }
        }

        return config;
    },

    exportPrepare: function (settings, exportType) {
        var jobs = [];
        var filters = "";

        var filterData = this.store.getFilters().items;
        if (filterData.length > 0) {
            filters = this.store.getProxy().encodeFilters(filterData);
        }

        var fields = this.getGridConfig().columns;
        var fieldKeys = Object.keys(fields);


        //remove unsupported fields for export
        var ignoreFields = ['preview', 'size'];
        ignoreFields.forEach(function (field) {
            var index = fieldKeys.indexOf(field);
            if (index > -1) {
                fieldKeys.splice(index, 1);
            }
        });

        //create the ids array which contains chosen rows to export
        ids = [];
        var selectedRows = this.grid.getSelectionModel().getSelection();
        for (var i = 0; i < selectedRows.length; i++) {
            ids.push(selectedRows[i].data.id);
        }

        settings = Ext.encode(settings);

        var params = {
            filter: filters,
            folderId: this.element.id,
            "ids[]": ids,
            "fields[]": fieldKeys,
            settings: settings,
            only_direct_children: this.onlyDirectChildren,
            only_unreferenced: this.onlyUnreferenced
        };

        Ext.Ajax.request({
            url: "/admin/asset-helper/get-export-jobs",
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
                settings: settings
            };
            this.exportProgressBar = new Ext.ProgressBar({
                text: t('initializing'),
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
                pimcore.helpers.download(exportType.downloadUrl + "?fileHandle=" + fileHandle);
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

        Ext.Ajax.request({
            url: "/admin/asset-helper/do-export",
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
                }.bind(this), 400);
            }.bind(this, jobs, jobs[this.exportJobCurrent])
        });
    },
});