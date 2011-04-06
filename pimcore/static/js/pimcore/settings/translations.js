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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
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
                        this.store.baseParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.preconfiguredFilter = filter;
        this.filterField.setValue(filter);
        this.getAvailableLanguages();
    },


    getRowEditor: function () {

        var languages = this.languages;

        var proxy = new Ext.data.HttpProxy({
            url: this.dataUrl,
            method: 'post'
        });

        var readerFields = [
            {name: 'key', allowBlank: false},
            {name: 'date', allowBlank: true}
        ];
        var typesColumns = [
            {header: t("key"), sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({})}

        ];

        for (var i = 0; i < languages.length; i++) {
            readerFields.push({name: languages[i]});
            typesColumns.push({header: languages[i].toUpperCase(), sortable: false, dataIndex: languages[i], editor: new Ext.form.TextField({})});
        }

        typesColumns.push({header: t("date"), sortable: true, dataIndex: 'date', editable: false, renderer: function(d) {
            var date = new Date(d * 1000);
            return date.format("Y-m-d H:i:s");
        }});

        typesColumns.push({
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data',
            idProperty: 'key'
        }, readerFields);

        var writer = new Ext.data.JsonWriter();

        var itemsPerPage = 20;
        this.store = new Ext.data.Store({
            id: 'translation_store',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            remoteSort: true,
            baseParams: {
                limit: itemsPerPage,
                filter: this.preconfiguredFilter
            },            
            listeners: {
                write : function(store, action, result, response, rs) {
                }
            }
        });
        this.store.load();

        this.editor = new Ext.ux.grid.RowEditor();

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 50,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));        
        
        
        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            plugins: [this.editor],
            columnLines: true,
            stripeRows: true,
            columns : typesColumns,
            bbar: this.pagingtoolbar,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },
                '-',
                {
                    text: t('delete'),
                    handler: this.onDelete.bind(this),
                    iconCls: "pimcore_icon_delete"
                },
                '-',
                {
                    text: t('reload'),
                    handler: function () {
                        this.store.reload();
                    }.bind(this),
                    iconCls: "pimcore_icon_reload"
                },'-',{
                  text: this.getHint(),
                  xtype: "tbtext",
                  style: "margin: 0 10px 0 0;"
                },
                "->",
                {
                    text: t('import_csv'),
                    handler: this.doImport.bind(this),
                    iconCls: "pimcore_icon_import"
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
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },

    doImport:function(){

        if (!this.uploadWindow) {
            this.uploadWindow = new Ext.Window({
                layout: 'fit',
                title: 'Upload',
                closeAction: 'hide',
                width:400,
                height:170,
                modal: true
            });

            var uploadPanel = new Ext.ux.SwfUploadPanel({
                border: false,
                upload_url: this.importUrl,
                debug: true,
                flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
                single_select: false,
                file_queue_limit: 1,
                file_types: "*.csv",
                single_file_select: true,
                confirm_delete: false,
                remove_completed: true,
                listeners: {
                    "fileUploadComplete": function (translations,grid,upload) {
                        this.hide();
                        translations.store.reload();
                    }.bind(this.uploadWindow, this)
                }
            });

            this.uploadWindow.add(uploadPanel);
        }

        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();
    },

    doExport:function(){
        window.open(this.exportUrl);
    },

    onAdd: function (btn, ev) {
        var u = new this.grid.store.recordType();
        this.editor.stopEditing();
        this.grid.store.insert(0, u);
        this.editor.startEditing(0);
    },

    onDelete: function () {
        var rec = this.grid.getSelectionModel().getSelected();
        if (!rec) {
            return false;
        }
        this.grid.store.remove(rec);
    }

});

