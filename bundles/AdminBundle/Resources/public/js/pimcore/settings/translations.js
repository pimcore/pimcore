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

pimcore.registerNS("pimcore.settings.translations");
pimcore.settings.translations = Class.create({
    filterField: null,
    preconfiguredFilter: "",
    dataUrl: '',
    exportUrl: '',
    uploadImportUrl: '',
    importUrl: '',
    mergeUrl: '',
    cleanupUrl: '',

    initialize: function (filter) {

        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            value: this.preconfiguredFilter,
            listeners: {
                "keydown": function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.searchString = input.getValue();
                        this.store.load({
                            page: 1
                        });
                    }
                }.bind(this)
            }
        });

        this.preconfiguredFilter = filter;
        this.filterField.setValue(filter);
        this.getAvailableLanguages();
        this.config = {};
    },


    getRowEditor: function () {

        var stateId = "tr_" + this.translationType;
        var applyInitialSettings = false;
        var showInfo = false;
        var state = Ext.state.Manager.getProvider().get(stateId, null);
        var languages = this.languages;

        var maxCols = 7;   // include creation date / modification date / action column)
        var maxLanguages = maxCols - 3;

        if (state == null) {
            applyInitialSettings = true;
            if (languages.length > maxLanguages) {
                showInfo = true;
            }
        } else {
            if (state.columns) {
                for (var i = 0; i < state.columns.length; i++) {
                    var colState = state.columns[i];
                    if (colState.hidden) {
                        showInfo = true;
                        break;
                    }
                }
            }
        }

        var dateConverter = function (v, r) {
            var d = new Date(intval(v));
            return d;
        };

        var readerFields = [
            {name: 'id', persist: false},
            {name: 'editor', persist: false},
            {name: 'key', allowBlank: false},
            {name: 'creationDate', type: 'date', convert: dateConverter, persist: false},
            {name: 'modificationDate', type: 'date', convert: dateConverter, persist: false}
        ];

        var typesColumns = [
            {text: t("key"), sortable: true, dataIndex: 'key', editable: false, filter: 'string'}
        ];

        for (var i = 0; i < languages.length; i++) {
            readerFields.push({name: "_" + languages[i], defaultValue: ''});

            var columnConfig = {
                cls: "x-column-header_" + languages[i].toLowerCase(),
                text: pimcore.available_languages[languages[i]],
                sortable: true,
                dataIndex: "_" + languages[i],
                filter: 'string',
                getEditor: this.getCellEditor.bind(this, languages[i]),
                renderer: function (text) {
                    if (text) {
                        return replace_html_event_attributes(strip_tags(text, 'div,span,b,strong,em,i,small,sup,sub,p'));
                    }
                },
                id: "translation_column_" + this.translationType + "_" + languages[i].toLowerCase()
            };
            if (applyInitialSettings) {
                var hidden = i >= maxLanguages;
                columnConfig.hidden = hidden;
            }

            typesColumns.push(columnConfig);
        }

        if (showInfo) {
            pimcore.helpers.showNotification(t("info"), t("there_are_more_columns"), null, null, 2000);
        }

        var dateRenderer = function (d) {
            var date = new Date(d * 1000);
            return Ext.Date.format(date, "Y-m-d H:i:s");
        };
        typesColumns.push({
            text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false
            , renderer: dateRenderer, filter: 'date'
        });
        typesColumns.push({
            text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false
            , renderer: dateRenderer, filter: 'date'
        })
        ;

        if (pimcore.settings.websiteLanguages.length == this.editableLanguages.length || this.translationType === 'admin') {
            typesColumns.push({
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
            });
        }

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        this.store = pimcore.helpers.grid.buildDefaultStore(
            this.dataUrl,
            readerFields,
            itemsPerPage, {
                idProperty: 'key'
            }
        );

        var store = this.store;

        this.store.getProxy().on('exception', function (proxy, request, operation) {
            operation.config.records.forEach(function (item) {
                store.remove(item);
            });
        });

        if (this.preconfiguredFilter) {
            this.store.getProxy().extraParams.searchString = this.preconfiguredFilter;
        }

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, context, eOpts) {
                    editor.editors.each(function (e) {
                        try {
                            // complete edit, so the value is stored when hopping around with TAB
                            e.completeEdit();
                            Ext.destroy(e);
                        } catch (exception) {
                            // garbage collector was faster
                            // already destroyed
                        }
                    });

                    editor.editors.clear();
                }
            }
        });

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },
                '-', {
                    text: this.getHint(),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                "->",
                {
                    text: t('cleanup'),
                    handler: this.cleanup.bind(this),
                    iconCls: "pimcore_icon_cleanup"
                },
                "-",
                {
                    text: t('merge_csv'),
                    handler: this.doMerge.bind(this),
                    iconCls: "pimcore_icon_merge"
                },
                '-',
                {
                    text: t('export_csv'),
                    handler: this.doExport.bind(this),
                    iconCls: "pimcore_icon_export"
                }, '-', {
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                }, this.filterField
            ]
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            bodyCls: "pimcore_editable_grid",
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            columns: {
                items: typesColumns,
                defaults: {
                    flex: 1,
                    renderer: Ext.util.Format.htmlEncode
                }
            },
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            stateful: true,
            stateId: stateId,
            stateEvents: ['columnmove', 'columnresize', 'sortchange', 'groupchange'],
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                "pimcore.gridfilters",
                this.cellEditing
            ],
            tbar: toolbar,
            viewConfig: {
                forceFit: true,
                loadingText: t('please_wait'),
                enableTextSelection: true
            },
            listeners: {
                cellcontextmenu: this.createCellContextMenu.bind(this),
                cellClick: function( grid, cell, cellIndex, record, row, recordIndex, e ) {
                    var cm = grid.headerCt.getGridColumns()
                    var dataIndex = cm[cellIndex].dataIndex;
                    if (!in_array(trim(dataIndex, "_"), this.languages)) {
                        return;
                    }

                    var data = record.get(dataIndex);

                    var htmlRegex = /<([A-Za-z][A-Za-z0-9]*)\b[^>]*>(.*?)<\/\1>/;
                    if (htmlRegex.test(data)) {
                        record.set("editor", "html");
                    } else if (data && data.match(/\n/gm))  {
                        record.set("editor", "plain");
                    } else {
                        record.set("editor", null);
                    }
                    return true;

                }.bind(this)
            }
        });

        this.store.load();

        return this.grid;
    },

    createCellContextMenu: function (grid, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
        var cm = grid.headerCt.getGridColumns();
        var dataIndex = trim(cm[cellIndex].dataIndex, "_");
        if (!in_array(dataIndex, this.languages)) {
            return;
        }

        e.stopEvent();

        var handler = function(rowIndex, cellIndex, mode) {
            record.set("editor", mode);
            this.cellEditing.startEditByPosition({
                row : rowIndex,
                column: cellIndex
            });
        };

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('edit_as_plain_text'),
            iconCls: "pimcore_icon_edit",
            handler: handler.bind(this, rowIndex, cellIndex, "plain")
        }));


        menu.add(new Ext.menu.Item({
            text: t('edit_as_html'),
            iconCls: "pimcore_icon_edit",
            handler: handler.bind(this, rowIndex, cellIndex, "html")
        }));

        menu.showAt(e.pageX, e.pageY);
    },

    doMerge: function () {
        pimcore.helpers.uploadDialog(this.uploadImportUrl, "Filedata", function (result) {
            var data = result.response.responseText;
            data = Ext.decode(data);

            if(data && data.success == true) {
                this.config = data.config;
                this.showImportForm();
            } else {
                Ext.MessageBox.alert(t("error"), t("error"));
            }
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    refresh: function () {
        this.store.reload();
    },

    showImportForm: function () {
        this.csvSettingsPanel = new pimcore.object.helpers.import.csvSettingsTab(this.config, false, this);

        var ImportForm = new Ext.form.FormPanel({
            width: 500,
            bodyStyle: 'padding: 10px;',
            items: [{
                    xtype: "form",
                    bodyStyle: "padding: 10px;",
                    defaults: {
                        labelWidth: 250,
                        width: 550
                    },
                    itemId: "form",
                    items: [this.csvSettingsPanel.getPanel()],
                    buttons: [{
                        text: t("cancel"),
                        iconCls: "pimcore_icon_cancel",
                        handler: function () {
                            win.close();
                        }
                    },
                    {
                    text: t("import"),
                    iconCls: "pimcore_icon_import",
                    handler: function () {
                        if(ImportForm.isValid()) {
                            this.csvSettingsPanel.commitData();
                            var csvSettings = Ext.encode(this.config.csvSettings);
                            ImportForm.getForm().submit({
                                url: this.mergeUrl,
                                params: {importFile: this.config.tmpFile, csvSettings: csvSettings},
                                waitMsg: t("please_wait"),
                                success: function (el, response) {
                                    try {
                                        var data = response.response.responseText;
                                        data = Ext.decode(data);
                                        var merger = new pimcore.settings.translation.translationmerger(this.translationType, data, this);
                                        this.refresh();
                                        win.close();
                                    } catch (e) {
                                        Ext.MessageBox.alert(t("error"), t("error"));
                                        win.close();
                                    }
                                }.bind(this),
                                failure: function (el, res) {
                                    Ext.MessageBox.alert(t("error"), t("error"));
                                    win.close();
                                }
                            });
                        }
                    }.bind(this)
                    }]
                }]
        });

        var windowCfg = {
            title: t("merge_csv"),
            width: 600,
            layout: "fit",
            closeAction: "close",
            items: [ImportForm]
        };

        var win = new Ext.Window(windowCfg);

        win.show();
    },

    doExport: function () {
        var store = this.grid.store;
        var storeFilters = store.getFilters().items;
        var proxy = store.getProxy();

        var filtersActive = this.filterField.getValue() || storeFilters.length > 0;
        if (filtersActive) {
            Ext.MessageBox.confirm("", t("filter_active_message"), function (buttonValue) {
                if (buttonValue == "yes") {
                    var queryString = "searchString=" + this.filterField.getValue();
                    var encodedFilters = proxy.encodeFilters(storeFilters);
                    queryString += "&filter=" + encodedFilters;
                    pimcore.helpers.download(Ext.urlAppend(this.exportUrl, queryString));
                } else {
                    pimcore.helpers.download(this.exportUrl);
                }
            }.bind(this));
        } else {
            pimcore.helpers.download(this.exportUrl);
        }
    },

    onAdd: function (btn, ev) {

        Ext.MessageBox.prompt("", t("please_enter_the_new_name"), function (button, value) {
            if (button == "ok") {
                this.cellEditing.cancelEdit();

                this.grid.store.insert(0, {
                    key: value
                });

                this.cellEditing.startEditByPosition({
                    row: 0,
                    column: 1
                });
            }
        }.bind(this));
    },

    cleanup: function () {
        Ext.Ajax.request({
            url: this.cleanupUrl,
            method: 'DELETE',
            success: function (response) {
                this.store.reload();
            }.bind(this)
        });
    },

    getCellEditor: function(language, record) {

        var editor;

        if (!record.data.editor) {
            editor = this.editableLanguages.indexOf(language) >= 0 ? new Ext.form.TextField({}) : null;
        } else {
            editor = new pimcore.settings.translationEditor({
                __editorType: record.data.editor,
                __outerTitle: record.data.editor == "plain" ? t("edit_as_plain_text") : t("edit_as_html"),
                __innerTitle: record.data.key
            });

        }

        return editor;
    }

});
