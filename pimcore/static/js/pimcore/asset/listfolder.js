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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.asset.listfolder");
pimcore.asset.listfolder = Class.create({

    
    initialize: function (element) {
        this.element = element;

    },


    getLayout: function () {
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


        var proxy = new Ext.data.HttpProxy({
            url: "/admin/asset/grid-proxy/",
            method: 'post'
        });

        var readerFields = [
            {name: 'id', allowBlank: true},
            {name: 'filename', allowBlank: true},
            {name: 'type', allowBlank: true},
            {name: 'creationDate', allowBlank: true},
            {name: 'modificationDate', allowBlank: true}
        ];

        var typesColumns = [
            {header: t("filename"), sortable: true, dataIndex: 'filename', editable: false},
            {header: t("type"), sortable: true, dataIndex: 'type', editable: false, width: 50}

        ];


        typesColumns.push({header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                                                                                renderer: function(d) {
            var date = new Date(d * 1000);
            return date.format("Y-m-d H:i:s");
        }});
        typesColumns.push({header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
        renderer: function(d) {
            var date = new Date(d * 1000);
            return date.format("Y-m-d H:i:s");
        }});


        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data',
            idProperty: 'key'
        }, readerFields);

//        var writer = new Ext.data.JsonWriter();

        var itemsPerPage = 20;
        this.store = new Ext.data.Store({
            id: 'translation_store',
            restful: false,
            proxy: proxy,
            reader: reader,
//            writer: writer,
            remoteSort: true,

            listeners: {
                write : function(store, action, result, response, rs) {
                }
            },
            filter: this.filterField,
            baseParams: {
                limit: itemsPerPage,
                test: "test",
                folderId: this.element.data.id
            }

        });
//        this.store.load();

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
        

        var gridfilters = this.getGridFilters();
        this.grid = new Ext.grid.EditorGridPanel({
            title: "List",
            iconCls: "pimcore_icon_table_tab",
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            columns : typesColumns,
            plugins: [gridfilters],
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            viewConfig: {
                forceFit: true
            },
            listeners: {
                activate: function() {
                    this.store.load();
                }.bind(this),
                rowdblclick: function(grid, rowIndex, e) {
                    var data = this.store.getAt(rowIndex).data;
                    pimcore.helpers.openAsset(data.id);

                }.bind(this)
            }
        });

        return this.grid;
    },

    getGridFilters: function() {
        var configuredFilters = [{
            type: "date",
            dataIndex: "creationDate"
        },{
            type: "date",
            dataIndex: "modificationDate"
        },{
            type:"string",
            dataIndex: "filename"
        },{
            type:"string",
            dataIndex: "type"
        }
        ];


        // filters
        var gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: configuredFilters
        });

        return gridfilters;

    }


});

