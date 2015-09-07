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

pimcore.registerNS("pimcore.settings.metadata.predefined");
pimcore.settings.metadata.predefined = Class.create({

    initialize: function () {
        this.getTabPanel();
    },
    
    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("predefined_metadata");
    },
    
    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "predefined_metadata",
                title: t("predefined_metadata_definitions"),
                iconCls: "pimcore_icon_metadata",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("predefined_metadata");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("predefined_metadata");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {
        var itemsPerPage = 20;

        var url =  '/admin/settings/metadata?';

        var proxy = {
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            api: {
                create  : url + "xaction=create",
                read    : url + "xaction=read",
                update  : url + "xaction=update",
                destroy : url + "xaction=destroy"
            },
            actionMethods: {
                create : 'POST',
                read   : 'POST',
                update : 'POST',
                destroy: 'POST'
            },
            extraParams: {
                limit: itemsPerPage,
                filter: ""
            }
        };

        this.store = new Ext.data.Store({
            id: 'predefined_metadata',
            proxy: proxy,
            remoteSort: true,
            autoLoad: true,
            autoSync: true,
            listeners: {
                exception : function(proxy, mode, action, options, response) {
                    Ext.Msg.show({
                        title: t("error"),
                        msg: t(response.raw.message),
                        buttons: Ext.Msg.OK,
                        animEl: 'elId',
                        icon: Ext.MessageBox.ERROR
                    });
                }
            },
            fields: [
                {name: 'id'},
                {name: 'name', allowBlank: false},
                {name: 'description', allowBlank: true},
                {name: 'type', allowBlank: true},
                {name: 'data', allowBlank: true,
                    convert: function (v, r) {
                        if (r.data.type == "date") {
                            var d = new Date(intval(v) * 1000);
                            return d;
                        }
                        return v;
                    }
                },
                {name: 'config', allowBlank: true},
                {name: 'targetSubtype', allowBlank: true},
                {name: 'language', allowBlank: true},
                {name: 'creationDate', allowBlank: true},
                {name: 'modificationDate', allowBlank: true}
            ]
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
                        var proxy = this.store.getPropxy();
                        proxy.extraParams.filter = input.getValue();
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
            emptyMsg: t("no_items_found")
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


        var languagestore = [["",t("none")]];
        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {
            languagestore.push([pimcore.settings.websiteLanguages[i],pimcore.settings.websiteLanguages[i]]);
        }

        var metadataColumns = [
            {
                header: t("type"),
                dataIndex: 'type',
                editable: false,
                width: 30,
                renderer: this.getTypeRenderer.bind(this),
                sortable: true
            },
            {header: t("name"), width: 200, sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("description"), sortable: true, dataIndex: 'description', editor: new Ext.form.TextArea({}),
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                                    if(empty(value)) {
                                        return "";
                                    }
                                    return nl2br(value);
                               }
            },
            {header: t("type"), width: 70, sortable: true,
                dataIndex: 'type', editor: new Ext.form.ComboBox({
                editable: false,
                store: [
                    ["input", t("input")],
                    ["textarea", t("textarea")],
                    ["document", "Document"],
                    ["asset", "Asset"],
                    ["object", "Object"],
                    ["date", "Date"],
                    ["checkbox", "checkbox"],
                    ["select", "select"]
                ]

            })},
            {header: t("value"),
                flex: 510,
                sortable: true,
                dataIndex: 'data',
                editable: true,
                getEditor: this.getCellEditor.bind(this),
                renderer: this.getCellRenderer.bind(this)
            },
            {header: t("configuration"),
                width: 100,
                sortable: false,
                dataIndex: 'config',
                editor: new Ext.form.TextField({})
            },
            {
                header: t('language'),
                sortable: true,
                dataIndex: "language",
                editor: new Ext.form.ComboBox({
                    name: "language",
                    store: languagestore,
                    editable: false,
                    triggerAction: 'all',
                    mode: "local"
                }),
                width: 80
            },
            {header: t("target_subtype"), width: 50, sortable: true, dataIndex: 'targetSubtype', editor: new Ext.form.ComboBox({
                editable: true,
                store: ["image", "text", "audio", "video", "document", "archive", "unknown"]
            })},
            {
                xtype: 'actioncolumn',
                width: 20,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    }
                    return "";
                }
            },
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    }
                    return "";
                }
            }
        ];

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            trackMouseOver: true,
            columns : metadataColumns,
            clicksToEdit: 1,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar,
            autoExpandColumn: "value_col",
            plugins: [
                this.cellEditing
            ],

            viewConfig: {
                listeners: {
                    rowupdated: this.updateRows.bind(this, "rowupdated"),
                    refresh: this.updateRows.bind(this, "refresh")
                },
                forceFit: true
            },
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },"->",{
                  text: t("filter") + "/" + t("search"),
                  xtype: "tbtext",
                  style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });

        this.grid.on("viewready", this.updateRows.bind(this));
        this.store.on("update", this.updateRows.bind(this));

        return this.grid;
    },

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        if (value == "input") {
            value = "text";
        }
        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) '
            + 'center center no-repeat; height: 16px;" recordid=' + record.id + '>&nbsp;</div>';
    },


    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (type == "textarea") {
            if (value) {
                return nl2br(value);
            } else {
                return "";
            }
        } else if (type == "document" || type == "asset" || type == "object") {
            if (value) {
                return '<div class="pimcore_property_droptarget">' + value + '</div>';
            } else {
                return '<div class="pimcore_property_droptarget">&nbsp;</div>';
            }
        } else if (type == "date") {
            if (value) {
                return Ext.Date.format(value, "Y-m-d");
            }
        }

        return value;
    },

    onAdd: function (btn, ev) {
        var model = this.grid.store.getModel();
        var u = new model({
            name: t('new_definition'),
            key: "new_key",
            subtype: "image",
            type: "input"
        });

        this.grid.store.insert(0, u);
    },

    updateRows: function (event) {
        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            try {
                var list = Ext.get(rows[i]).query(".x-grid-cell-first div div");
                var firstItem = list[0];
                if (!firstItem) {
                    continue;
                }


                var recordid = firstItem.getAttribute("recordid");
                var data = this.grid.getStore().getById(recordid);
                if (!data) {
                    continue;
                }

                data = data.data;

                if(in_array(data.name, this.disallowedKeys)) {
                    Ext.get(rows[i]).addCls("pimcore_properties_hidden_row");
                }

                if (data.type == "document" || data.type == "asset" || data.type == "object") {

                    // add dnd support
                    var dd = new Ext.dd.DropZone(rows[i], {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver: function(dataRow, target, dd, e, data) {

                            var record = data.records[0];
                            var data = record.data;

                            if(dataRow.type == data.elementType) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }.bind(this, data),

                        onNodeDrop : function(recordid, target, dd, e, data) {

                            var rec = this.grid.getStore().getById(recordid);

                            var record = data.records[0];
                            var data = record.data;

                            if(data.elementType != rec.get("type")) {
                                return false;
                            }


                            rec.set("data", data.path);
                            rec.set("all",{
                                data: {
                                    id: data.id,
                                    type: data.type
                                }
                            });

                            this.updateRows();

                            return true;
                        }.bind(this, recordid)
                    });

                }
            }
            catch (e) {
                console.log(e);
            }
        }
    },

    getCellEditor: function (record) {

        var store = this.grid.getStore();
        var data = record.data;

        var type = data.type;
        var property;

        if (type == "input") {
            property = new Ext.form.TextField();
        } else if (type == "textarea") {
            property = new Ext.form.TextArea();
        } else if (type == "document" || type == "asset" || type == "object") {

            property = new Ext.form.TextField({
                disabled: true,
                propertyGrid: this.grid,
                style: {
                    visibility: "hidden"
                }
            });
        } else if (type == "date") {
            property = new Ext.form.DateField();
        } else {
            return null;
        }

        return property;
    }


});