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

    objecttype: 'asset',
    batchPrepareUrl: "/admin/asset-helper/get-batch-jobs",
    batchProcessUrl: "/admin/asset-helper/batch",
    exportPrepareUrl: "/admin/asset-helper/get-export-jobs",
    exportProcessUrl: "/admin/asset-helper/do-export",

    createGrid: function (columnConfig) {
    },

    openColumnConfig: function (allowPreview) {
        var gridConfig = this.getGridConfig();
        var fields = gridConfig.columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for (var i = 0; i < fieldKeys.length; i++) {
            var field = fields[fieldKeys[i]];
            if (!field.hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: field.fieldConfig.label,
                    dataType: field.fieldConfig.type,
                    layout: field.fieldConfig.layout,
                    language: field.fieldConfig.language,
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
            language: gridConfig.language,
            pageSize: gridConfig.pageSize,
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

        for (var i = 0; i < cm.length; i++) {
            if (cm[i].dataIndex) {
                var name = cm[i].dataIndex;
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
});