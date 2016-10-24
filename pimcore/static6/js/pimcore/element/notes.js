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
            // global view
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.getLayout());
            tabPanel.setActiveTab(this.getLayout());

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

            var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                '/admin/element/note-list?',
                ['id', 'type', 'title', 'description',"user","date","data","cpath","cid","ctype"],
                itemsPerPage,
                {autoLoad: false}
            );

            // only when used in element context
            if(this.inElementContext) {
                var proxy = this.store.getProxy();
                proxy.extraParams["cid"] = this.element.id;
                proxy.extraParams["ctype"] = this.type;
            } else {

            }

            this.filterField = new Ext.form.TextField({
                xtype: "textfield",
                width: 200,
                style: "margin: 0 10px 0 0;",
                enableKeyEvents: true,
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            var input = field;
                            var proxy = this.store.getProxy();
                            proxy.extraParams.filter = input.getValue();

                            this.store.load();
                        }
                    }.bind(this)
                }
            });

            this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);


            var tbarItems = [
                "->",
                {
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ];

            // only when used in element context
            if(this.inElementContext) {
                tbarItems.unshift({
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                });
            }

            var tbar = Ext.create('Ext.Toolbar', {
                cls: 'main-toolbar',
                items: tbarItems
            });

            var columns = [
                {header: "ID", sortable: true, dataIndex: 'id', hidden: true, flex: 60},
                {header: t("type"), sortable: true, dataIndex: 'type', flex: 60},
                {header: t("element"), sortable: false, dataIndex: 'cpath', flex: 200,
                    hidden: this.inElementContext,
                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                        if(record.get("cid")) {
                            return t(record.get("ctype")) + ": " + record.get("cpath");
                        }
                        return "";
                    }
                },
                {header: t("title"), sortable: true, dataIndex: 'title', flex: 200},
                {header: t("description"), sortable: true, dataIndex: 'description'},
                {header: t("fields"), sortable: false, dataIndex: 'data', renderer: function(v) {
                    if(v) {
                        return v.length;
                    }
                    return "";
                }},
                {header: t("user"), sortable: true, dataIndex: 'user', flex: 100, renderer: function(v) {
                    if(v && v["name"]) {
                        return v["name"];
                    }
                    return "";
                }},
                {header: t("date"), sortable: true, dataIndex: 'date', flex: 100, renderer: function(d) {
                    var date = new Date(d * 1000);
                    return Ext.Date.format(date, "Y-m-d H:i:s");
                }}
            ];

            if (!this.inElementContext) {
                columns.push(
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('click_to_open'),
                            iconCls: "pimcore_icon_open",
                            handler: function (grid, rowIndex, event) {
                                var record = this.store.getAt(rowIndex);
                                var id = record.get("cid");
                                var type = record.get("ctype");
                                pimcore.helpers.openElement(id, type, null);
                            }.bind(this)
                        }]
                    }
                );
            }

            columns.push({
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('details'),
                    icon: "/pimcore/static6/img/flat-color-icons/info.svg",
                    handler: function (grid, rowIndex, event) {
                        this.showDetailedData(grid, rowIndex, event);
                    }.bind(this)
                }]
            });

            this.grid = new Ext.grid.GridPanel({
                store: this.store,
                region: "center",
                columns: columns,
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
                    rowdblclick : function(grid, record, tr, rowIndex, e, eOpts ) {
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

            var layoutConf = {
                tabConfig: {
                    tooltip: t('notes_events')
                },
                iconCls: "pimcore_icon_notes",
                items: [this.grid, this.detailView],
                layout: "border",
                closable: !this.inElementContext
            };

            if(!this.inElementContext) {
                layoutConf["title"] = t('notes_events');
            }

            this.layout = new Ext.Panel(layoutConf);

            this.layout.on("activate", function () {
                this.store.load();
            }.bind(this));
        }

        return this.layout;
    },

    showDetail: function (grid, record, tr, rowIndex, e, eOpts ) {
        var rec = this.store.getAt(rowIndex);

        var keyValueStore = new Ext.data.Store({
            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            autoDestroy: true,
            data: rec.data,
            fields: ['data', 'name', 'type']
        });

        var keyValueGrid = new Ext.grid.GridPanel({
            store: keyValueStore,
            title: t("details_for_selected_event") + " (" + rec.get("id") + ")",
            columns: [
                {header: t("name"), sortable: true, dataIndex: 'name', flex: 60},
                {header: t("type"), sortable: true, dataIndex: 'type', flex: 30,
                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                        return t(value);
                    }
                },
                {header: t("value"), sortable: true, dataIndex: 'data', flex: 60,
                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                        if(record.get("type") == "document" || record.get("type") == "asset"
                            || record.get("type") == "object") {
                            if(value && value["path"]) {
                                return value["path"];
                            }
                        } else if (record.get("type") == "date") {
                            if(value) {
                                var date = new Date(value * 1000);
                                return Ext.Date.format(date, "Y-m-d H:i:s");
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
                        icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
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
        this.detailView.updateLayout();
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
                width: 250
            },{
                xtype: "textfield",
                fieldLabel: t("title"),
                name: "title",
                width: 450
            }, {
                xtype: "textarea",
                fieldLabel: t("description"),
                name: "description",
                width: 450
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
            height: 280,
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
