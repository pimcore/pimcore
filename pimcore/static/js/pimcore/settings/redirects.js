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

pimcore.registerNS("pimcore.settings.redirects");
pimcore.settings.redirects = Class.create({

    initialize: function () {

        this.getTabPanel();

    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_redirects");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_redirects",
                title: t("redirects"),
                iconCls: "pimcore_icon_redirects",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_redirects");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("redirects");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/settings/redirects'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'source', allowBlank: false},
            {name: 'sourceEntireUrl', allowBlank: true},
            {name: 'sourceSite', allowBlank: true},
            {name: 'passThroughParameters', allowBlank: true},
            {name: 'target', allowBlank: false},
            {name: 'targetSite', allowBlank: true},
            {name: 'statusCode', allowBlank: true},
            {name: 'priority', type:'int' ,allowBlank: true},
            {name: 'expiry', type: "date", convert: function (v, r) {
                if(v) {
                    var d = new Date(intval(v) * 1000);
                    return d;
                }
            } ,allowBlank: true},
            {name: 'creationDate', allowBlank: true},
            {name: 'modificationDate', allowBlank: true}
        ]);
        var writer = new Ext.data.JsonWriter();

        var itemsPerPage = 20;

        this.store = new Ext.data.Store({
            id: 'redirects_store',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            remoteSort: true,
            baseParams: {
                limit: itemsPerPage,
                filter: ""
            },
            listeners: {
                write : function(store, action, result, response, rs) {
                }
            }
        });
        this.store.load();


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

        var sourceEntireUrlCheck = new Ext.grid.CheckColumn({
            header: t("source_entire_url"),
            dataIndex: "sourceEntireUrl",
            width: 70
        });

        var passThroughParametersCheck = new Ext.grid.CheckColumn({
            header: t("pass_through_params"),
            dataIndex: "passThroughParameters",
            width: 70
        });

        var typesColumns = [
            {header: t("source"), width: 200, sortable: true, dataIndex: 'source', editor: new Ext.form.TextField({})},
            sourceEntireUrlCheck,
            {header: t("source_site"), width: 200, sortable:true, dataIndex: "sourceSite",
                editor: new Ext.form.ComboBox({
                store: pimcore.globalmanager.get("sites"),
                valueField: "id",
                displayField: "domain",
                triggerAction: "all"
            }), renderer: function (siteId) {
                var store = pimcore.globalmanager.get("sites");
                var pos = store.findExact("id", siteId);
                if(pos >= 0) {
                    return store.getAt(pos).get("domain");
                }
            }},
            passThroughParametersCheck,
            {header: t("target"), width: 200, sortable: false, dataIndex: 'target',
                editor: new Ext.form.TextField({}),
                css: "background: url(/pimcore/static/img/icon/drop-16.png) right 2px no-repeat;"},
            {header: t("target_site"), width: 200, sortable:true, dataIndex: "targetSite",
                editor: new Ext.form.ComboBox({
                store: pimcore.globalmanager.get("sites"),
                valueField: "id",
                displayField: "domain",
                triggerAction: "all"
            }), renderer: function (siteId) {
                var store = pimcore.globalmanager.get("sites");
                var pos = store.findExact("id", siteId);
                if(pos >= 0) {
                    return store.getAt(pos).get("domain");
                }
            }},
            {header: t("type"), width: 50, sortable: true, dataIndex: 'statusCode', editor: new Ext.form.ComboBox({
                store: [
                    ["301", "Moved Permanently (301)"],
                    ["307", "Temporary Redirect (307)"],
                    ["300", "Multiple Choices (300)"],
                    ["302", "Found (302)"],
                    ["303", "See Other (303)"]
                ],
                mode: "local",
                typeAhead: false,
                editable: false,
                forceSelection: true,
                triggerAction: "all"
            })},
            {header: t("priority"), width: 50, sortable: true, dataIndex: 'priority', editor: new Ext.form.ComboBox({
                store: [
                    [1, "1 - " + t("lowest")],
                    [2, 2],
                    [3, 3],
                    [4, 4],
                    [5, 5],
                    [6, 6],
                    [7, 7],
                    [8, 8],
                    [9, 9],
                    [10, "10 - " + t("highest")],
                    [99, "99 - " + t("override_all")]
                ],
                mode: "local",
                typeAhead: false,
                editable: false,
                forceSelection: true,
                triggerAction: "all"
            })},
            {header: t("expiry"), width: 60, sortable:true, dataIndex: "expiry", editor: new Ext.form.DateField(),
                                                                            renderer: function(d) {
                if(d instanceof Date) {
                    return d.format("Y-m-d");
                }
            }},
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                        this.updateRows();
                    }.bind(this)
                }]
            }
        ];

        this.grid = new Ext.grid.EditorGridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
			columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            selModel:new Ext.grid.RowSelectionModel({singleSelect:true}),
            stripeRows: true,
            bbar: this.pagingtoolbar,
            tbar: [
                {
                    xtype: "splitbutton",
                    text: t('add'),
                    iconCls: "pimcore_icon_add",
                    handler: this.openWizard.bind(this),
                    menu: [{
                        iconCls: "pimcore_icon_add",
                        text: t("add_expert_mode"),
                        handler: this.onAdd.bind(this)
                    },{
                        iconCls: "pimcore_icon_add",
                        text: t("add_beginner_mode"),
                        handler: this.openWizard.bind(this)
                    }]
                }, "->", {
                  text: t("filter") + "/" + t("search"),
                  xtype: "tbtext",
                  style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ],
            viewConfig: {
                forceFit: true,
                listeners: {
                    rowupdated: this.updateRows.bind(this),
                    refresh: this.updateRows.bind(this)
                }
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));


        return this.grid;
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid3-row");

        for (var i = 0; i < rows.length; i++) {

            var dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    console.log(data.node.attributes.type);
                    if(in_array(data.node.attributes.type,["page","link","hardlink"])) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }
                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    if(in_array(data.node.attributes.type,["page","link","hardlink"])) {
                        var rec = this.grid.getStore().getAt(myRowIndex);
                        rec.set("target", data.node.attributes.path);
                        this.updateRows();
                        return true;
                    }
                    return false;

                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        var u = new this.grid.store.recordType({
            source: ""
        });
        this.grid.store.insert(0, u);

		this.updateRows();
    },

    openWizard: function () {

        this.wizardForm = new Ext.form.FormPanel({
            xtype: "form",
            bodyStyle: "padding:10px;",
            items: [{
                xtype:"compositefield",
                items: [{
                    xtype: "combo",
                    name: "mode",
                    store: [
                        ["begin", t("beginning_with")],
                        ["exact", t("matching_exact")],
                        ["contain", t("contain")],
                        ["begin_end_slash", t("short_url")],
                        ["domain", t("domain")]
                    ],
                    mode: "local",
                    typeAhead: false,
                    editable: false,
                    forceSelection: true,
                    triggerAction: "all",
                    emptyText: t("select")
                }, {
                    xtype: "textfield",
                    name: "pattern",
                    fieldLabel: t("pattern"),
                    width: 320,
                    emptyText: "/some/example/path"
                }]
            }]
        });

        this.wizardWindow = new Ext.Window({
            width: 650,
            modal:true,
            items: [this.wizardForm],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_accept",
                handler: this.saveWizard.bind(this)
            }]
        });

        this.wizardWindow.show();
    },

    saveWizard: function () {

        var source = "";
        var sourceEntireUrl = false;
        var priority = 1;
        var values = this.wizardForm.getForm().getFieldValues();
        var pattern = preg_quote(values.pattern);
        pattern = str_replace("@","\\@",pattern);

        if(values.mode == "begin") {
            source = "@^" + pattern + "@";
        } else if (values.mode == "exact") {
            source = "@^" + pattern + "$@";
        } else if (values.mode == "contain") {
            source = "@" + pattern + "@";
        } else if (values.mode == "begin_end_slash") {
            if(pattern.charAt(0) != "/") {
                pattern = "/" + pattern;
            }
            source = "@^" + pattern + "[\\/]?$@";
        } else if (values.mode == "domain") {
            if(values.pattern.indexOf("http") >= 0) {
                pattern = parse_url(values.pattern, "host");
            } else {
                pattern = values.pattern;
            }
            pattern = preg_quote(pattern);
            source = "@https?://" + pattern + "@";
            sourceEntireUrl = true;
            priority = 99;
        }

        var u = new this.grid.store.recordType({
            source: source,
            sourceEntireUrl: sourceEntireUrl,
            priority: priority
        });
        this.grid.store.insert(0, u);

		this.updateRows();

        this.wizardWindow.close();
    }

});