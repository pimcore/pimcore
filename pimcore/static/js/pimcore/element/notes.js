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

pimcore.registerNS("pimcore.element.notes");
pimcore.element.notes = Class.create({

    initialize: function(element, type) {

        this.inElementContext = false;

        if(element && type) {
            // in element context
            this.element = element;
            this.type = type;
            this.inElementContext = true;
        } else {
            // standalone version
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.getLayout());
            tabPanel.activate(this.getLayout());

            this.getLayout().on("destroy", function () {
                pimcore.globalmanager.remove("notes");
            });

            pimcore.layout.refresh();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate(this.getLayout());
    },

    getLayout: function () {

        if (this.layout == null) {

            var itemsPerPage = 20;
            var baseParams = {
                limit: itemsPerPage
            };

            // only when used in element context
            if(this.inElementContext) {
                baseParams["cid"] = this.element.id;
                baseParams["ctype"] = this.type;
            }

            this.store = new Ext.data.JsonStore({
                autoDestroy: true,
                url: "/admin/element/note-list",
                remoteSort: true,
                baseParams: baseParams,
                root: 'data',
                fields: ['id', 'type', 'title', 'description',"user","date","data","cpath","cid","ctype"]
            });

            this.filterField = new Ext.form.TextField({
                xtype: "textfield",
                width: 200,
                style: "margin: 0 10px 0 0;",
                enableKeyEvents: true,
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

            var tbar = ["->", {
              text: t("filter") + "/" + t("search"),
              xtype: "tbtext",
              style: "margin: 0 10px 0 0;"
            }, this.filterField];

            // only when used in element context
            if(this.inElementContext) {
                tbar.unshift({
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                });
            }

            this.grid = new Ext.grid.GridPanel({
                store: this.store,
                region: "center",
                columns: [
                    {header: "ID", sortable: true, dataIndex: 'id', hidden: true, width: 60},
                    {header: t("type"), sortable: true, dataIndex: 'type', width: 60},
                    {header: t("element"), sortable: true, dataIndex: 'cpath', width: 200,
                                hidden: this.inElementContext,
                                renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                    if(record.get("cid")) {
                                        return t(record.get("ctype")) + ": " + record.get("cpath");
                                    }
                                    return "";
                                }
                    },
                    {header: t("title"), sortable: true, dataIndex: 'title', width: 200},
                    {header: t("description"), id: "description", sortable: true, dataIndex: 'description'},
                    {header: t("fields"), sortable: true, dataIndex: 'data', renderer: function(v) {
                        if(v) {
                            return v.length;
                        }
                        return "";
                    }},
                    {header: t("user"), sortable: true, dataIndex: 'user', width: 100, renderer: function(v) {
                        if(v && v["name"]) {
                            return v["name"];
                        }
                        return "";
                    }},
                    {header: t("date"), sortable: true, dataIndex: 'date', width: 100, renderer: function(d) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    }},
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('details'),
                            icon: "/pimcore/static/img/icon/info.png",
                            handler: function (grid, rowIndex, event) {
                                this.showDetailedData(grid, rowIndex, event);
                            }.bind(this)
                        }]
                    }
                ],
                columnLines: true,
                bbar: this.pagingtoolbar,
                tbar: tbar,
                autoExpandColumn: "description",
                stripeRows: true,
                autoScroll: true,
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowdblclick : function(grid, rowIndex, event ) {
                        this.showDetailedData(grid, rowIndex, event);
                    }.bind(this)

                }
            });
            this.grid.on("rowclick", this.showDetail.bind(this));

            this.detailView = new Ext.Panel({
                region: "east",
                width: 350,
                layout: "fit"
            });

            this.layout = new Ext.Panel({
                title: t('notes') + " & " + t("events"),
                border: true,
                iconCls: "pimcore_icon_tab_notes",
                items: [this.grid, this.detailView],
                layout: "border",
                closable: !this.inElementContext
            });

            this.layout.on("activate", function () {
                this.store.load();
            }.bind(this));
        }

        return this.layout;
    },

    showDetail: function (grid, rowIndex, e) {
        var rec = this.store.getAt(rowIndex);

        var keyValueStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: rec.data,
            root: 'data',
            fields: ['data', 'name', 'type']
        });

        var keyValueGrid = new Ext.grid.GridPanel({
            store: keyValueStore,
            title: t("details_for_selected_event") + " (" + rec.get("id") + ")",
            columns: [
                {header: t("name"), sortable: true, dataIndex: 'name', width: 60},
                {header: t("type"), sortable: true, dataIndex: 'type',
                                renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                    return t(value);
                                }
                },
                {header: t("value"), sortable: true, dataIndex: 'data',
                                renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                            if(record.get("type") == "document" || record.get("type") == "asset"
                                                                            || record.get("type") == "object") {
                                                if(value && value["path"]) {
                                                    return value["path"];
                                                }
                                            } else if (record.get("type") == "date") {
                                                if(value) {
                                                    var date = new Date(value * 1000);
                                                    return date.format("Y-m-d H:i:s");
                                                }
                                            }

                                            return value;
                                        }
                },
                {
                    xtype: 'actioncolumn',
                    width: 30,
                    items: [{
                        tooltip: t('open'),
                        icon: "/pimcore/static/img/icon/pencil_go.png",
                        handler: function (grid, rowIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            if(rec.get("type") == "document" || rec.get("type") == "asset"
                                                                                || rec.get("type") == "object") {
                                if(rec.get("data") && rec.get("data")["id"]) {
                                    pimcore.helpers.openElement(rec.get("data").id,
                                                                    rec.get("type"),rec.get("data").type);
                                }
                            }
                        }.bind(this),
                        getClass: function(v, meta, rec) {  // Or return a class from a function
                            if(rec.get('type') != "object"
                                                && rec.get('type') != "document" && rec.get('type') != "asset") {
                                return "pimcore_hidden";
                            }
                        }
                    }]
                }
            ],
            columnLines: true,
            stripeRows: true,
            autoScroll: true,
            viewConfig: {
                forceFit: true
            }
        });

        this.detailView.removeAll();
        this.detailView.add(keyValueGrid);
        this.detailView.doLayout();
    },

    onAdd: function () {

        var formPanel = new Ext.form.FormPanel({
            bodyStyle: "padding:10px;",
            items: [{
                xtype: "combo",
                fieldLabel: t('type'),
                name: "type",
                store: ["","content","seo","warning","notice"],
                editable: true,
                mode: "local",
                triggerAction: "all",
                width: 150
            },{
                xtype: "textfield",
                fieldLabel: t("title"),
                name: "title",
                width: 350
            }, {
                xtype: "textarea",
                fieldLabel: t("description"),
                name: "description",
                width: 350
            },{
                xtype: "hidden",
                name: "cid",
                value: this.element.id
            },{
                xtype: "hidden",
                name: "ctype",
                value: this.type
            }]
        });

        var addWin = new Ext.Window({
            modal: true,
            width: 500,
            height: 210,
            closable: true,
            items: [formPanel],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_accept",
                handler: function () {

                    var values = formPanel.getForm().getFieldValues();

                    Ext.Ajax.request({
                        url: "/admin/element/note-add/",
                        method: "post",
                        params: values
                    });

                    addWin.close();
                    this.store.reload();
                }.bind(this)
            }]
        });

        addWin.show();
    },

    showDetailedData: function(grid, rowIndex, event) {
        var data = this.store.getAt(rowIndex);
        new pimcore.element.note_details(data.data);
    }

});
