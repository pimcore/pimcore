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
    batchPrepareUrl: "/admin/object-helper/get-batch-jobs",
    batchProcessUrl: "/admin/object-helper/batch",
    exportPrepareUrl: "/admin/object-helper/get-export-jobs",
    exportProcessUrl: "/admin/object-helper/do-export",

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
            language: gridConfig.language,
            pageSize: gridConfig.pageSize,
            classid: this.classId,
            objectId: objectId,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig, function (data, settings, save) {
                this.gridLanguage = data.language;
                this.gridPageSize = data.pageSize;
                this.createGrid(true, data.columns, settings, save);
            }.bind(this),
            function () {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {
                        id: this.classId,
                        objectId: objectId,
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
                classId: this.classId,
                objectId: objectId
            }
        )

    },

    createGrid: function (columnConfig) {
    },

    getGridConfig: function () {
        var config = {
            language: this.gridLanguage,
            pageSize: this.gridPageSize,
            sortinfo: this.sortinfo,
            classId: this.classId,
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
                    isOperator: this.fieldObject[name].isOperator
                };
            }
        }

        return config;
    },

    createSqlEditor: function () {
        this.sqlEditor = new Ext.form.TextField({
            xtype: "textfield",
            width: 500,
            name: "condition",
            hidden: true,
            enableKeyEvents: true,
            listeners: {
                "keydown": function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var proxy = this.store.getProxy();
                        proxy.setExtraParams(
                            {
                                class: proxy.extraParams.class,
                                objectId: proxy.extraParams.objectId,
                                "fields[]": proxy.extraParams["fields[]"],
                                language: proxy.extraParams.language
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
            hidden: !pimcore.currentuser.admin,
            handler: function (button) {

                this.sqlEditor.setValue("");
                this.searchField.setValue("");

                // reset base params, because of the condition
                var proxy = this.store.getProxy();
                proxy.setExtraParams(
                    {
                        class: proxy.extraParams.class,
                        objectId: proxy.extraParams.objectId,
                        "fields[]": proxy.extraParams["fields[]"],
                        language: proxy.extraParams.language
                    }
                );

                this.grid.filters.clearFilters();

                this.pagingtoolbar.moveFirst();

                if (button.pressed) {
                    this.sqlEditor.show();
                } else {
                    this.sqlEditor.hide();
                }
            }.bind(this)
        });
    },

    getToolbar: function (fromConfig, save) {
        this.searchQuery = function(field) {
            this.store.getProxy().setExtraParam("query", field.getValue());
            this.pagingtoolbar.moveFirst();
        }.bind(this);

        this.searchField = new Ext.form.TextField(
            {
                name: "query",
                width: 200,
                hideLabel: true,
                enableKeyEvents: true,
                triggers: {
                    search: {
                        weight: 1,
                        cls: 'x-form-search-trigger',
                        scope: 'this',
                        handler: function(field, trigger, e) {
                            this.searchQuery(field);
                        }.bind(this)
                    }
                },
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            this.searchQuery(field);
                        }
                    }.bind(this)
                }
            }
        );

        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + (this.gridLanguage == "default" ? t("default") : pimcore.available_languages[this.gridLanguage])
        });

        this.toolbarFilterInfo = new Ext.Button({
            iconCls: "pimcore_icon_filter_condition",
            hidden: true,
            text: '<b>' + t("filter_active") + '</b>',
            tooltip: t("filter_condition"),
            handler: function (button) {
                Ext.MessageBox.alert(t("filter_condition"), button.pimcore_filter_condition);
            }.bind(this)
        });

        this.clearFilterButton = new Ext.Button({
            iconCls: "pimcore_icon_clear_filters",
            hidden: true,
            text: t("clear_filters"),
            tooltip: t("clear_filters"),
            handler: function (button) {
                this.grid.filters.clearFilters();
                this.toolbarFilterInfo.hide();
                this.clearFilterButton.hide();
            }.bind(this)
        });


        this.createSqlEditor();

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            boxLabel: t("only_children"),
            listeners: {
                "change": function (field, checked) {
                    this.grid.getStore().setRemoteFilter(false);
                    this.grid.filters.clearFilters();

                    this.store.getProxy().setExtraParam("only_direct_children", checked);

                    this.onlyDirectChildren = checked;
                    this.pagingtoolbar.moveFirst();

                    this.grid.getStore().setRemoteFilter(true);
                }.bind(this)
            }
        });

        var exportButtons = this.getExportButtons();
        var firstButton = exportButtons.pop();

        this.exportButton = new Ext.SplitButton({
            text: firstButton.text,
            iconCls: firstButton.iconCls,
            handler: firstButton.handler,
            menu: exportButtons,
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

        var toolbar = new Ext.Toolbar({
            scrollable: "x",
            items: [this.searchField, "-",
                this.languageInfo, "-",
                this.toolbarFilterInfo,
                this.clearFilterButton, "->",
                this.checkboxOnlyDirectChildren, "-",
                this.sqlEditor, this.sqlButton, "-",
                this.exportButton, "-",
                this.columnConfigButton,
                this.saveColumnConfigButton
            ]
        });

        return toolbar;
    },

    getExportButtons: function () {
        var buttons = [];
        pimcore.globalmanager.get("pimcore.object.gridexport").forEach(function (exportType) {
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
    }
});
