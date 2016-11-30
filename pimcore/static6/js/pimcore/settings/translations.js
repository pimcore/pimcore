/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.settings.translations");
pimcore.settings.translations = Class.create({


    filterField: null,
    preconfiguredFilter: "",

    initialize: function (filter) {

        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            value: this.preconfiguredFilter,
            listeners: {
                "keydown" : function (field, key) {
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
            {name: 'key', allowBlank: false},
            {name: 'creationDate', allowBlank: true, type: 'date', convert: dateConverter, persist: false},
            {name: 'modificationDate', allowBlank: true, type: 'date', convert: dateConverter, persist: false}
        ];

        var typesColumns = [
            {header: t("key"), sortable: true, dataIndex: 'key', editable: false, filter: 'string'}
        ];

        for (var i = 0; i < languages.length; i++) {
            readerFields.push({name: languages[i]});
            //TODO do we really need the id attribute?
            var columnConfig = {
                cls: "x-column-header_" + languages[i].toLowerCase(),
                header: pimcore.available_languages[languages[i]],
                sortable: true,
                dataIndex: languages[i],
                filter: 'string',
                editor: this.editableLanguages.indexOf(languages[i]) >= 0 ? new Ext.form.TextField({}) : null,
                id: "translation_column_" + this.translationType + "_" + languages[i].toLowerCase()};
            if (applyInitialSettings) {
                var hidden = i >= maxLanguages;
                columnConfig.hidden = hidden;
            }

            typesColumns.push(columnConfig);
        }

        if (showInfo) {
            pimcore.helpers.showNotification(t("info"), t("there_are_more_columns"), null, null, 2000);
        }

        var dateRenderer = function(d) {
            var date = new Date(d * 1000);
            return Ext.Date.format(date, "Y-m-d H:i:s");
        };
        typesColumns.push({header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false
            ,renderer: dateRenderer, filter: 'date'
        });
        typesColumns.push({header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false
            ,renderer: dateRenderer, filter: 'date'
        })
        ;

        typesColumns.push({
            xtype: 'actioncolumn',
            width: 30,
            items: [{
                tooltip: t('delete'),
                icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                handler: function (grid, rowIndex) {
                    grid.getStore().removeAt(rowIndex);
                }.bind(this)
            }]
        });

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        this.store = pimcore.helpers.grid.buildDefaultStore(
            this.dataUrl,
            readerFields,
            itemsPerPage, {
                idProperty: 'key'
            }
        );

        if(this.preconfiguredFilter) {
            this.store.getProxy().extraParams.searchString = this.preconfiguredFilter;
        }

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },
                '-',{
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
                },'-',{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },this.filterField
            ]
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            bodyCls: "pimcore_editable_grid",
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            columns : {
                items: typesColumns,
                defaults: {
                    flex: 1
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
                loadingText: t('loading_texts'),
                enableTextSelection: true
            }
        });

        this.store.load();

        return this.grid;
    },

    doImport:function(){
        pimcore.helpers.uploadDialog(this.importUrl, "Filedata", function() {
            this.store.reload();
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    doMerge:function(){
        pimcore.helpers.uploadDialog(this.mergeUrl, "Filedata", function(result) {
            var data = result.response.responseText;
            data = Ext.decode(data);

            var merger = new pimcore.settings.translation.translationmerger(this.translationType, data, this);
            this.refresh();
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    refresh: function() {
        this.store.reload();
    },


    doExport:function(){

        var store = this.grid.store;
        var storeFilters = store.getFilters().items;
        var proxy = store.getProxy();

        var filtersActive = this.filterField.getValue() || storeFilters.length > 0;
        if(filtersActive) {
            Ext.MessageBox.confirm("", t("filter_active_message"), function (buttonValue) {
                if (buttonValue == "yes") {
                    var queryString = "searchString=" + this.filterField.getValue();
                    var encodedFilters = proxy.encodeFilters(storeFilters);
                    queryString += "&filter=" + encodedFilters + "&extjs6=1";
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
            if(button == "ok") {
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
            success: function (response) {
                this.store.reload();
            }.bind(this)
        });
    }
});
