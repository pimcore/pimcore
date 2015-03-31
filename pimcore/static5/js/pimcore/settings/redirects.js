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
        tabPanel.setActiveItem("pimcore_redirects");
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
            tabPanel.setActiveItem("pimcore_redirects");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("redirects");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var itemsPerPage = 20;
        var url = '/admin/settings/redirects?';

        var proxy = {
            type: 'ajax',
            extraParams:{
                limit:itemsPerPage,
                filter:""
            },
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
            }
        };

        this.store = new Ext.data.Store({
            proxy: proxy,
            autoLoad: true,
            autoSync: true,
            remoteSort: true,
            fields: [
                {name: 'id'},
                {name: 'source', allowBlank: false},
                {name: 'sourceEntireUrl'},
                {name: 'sourceSite'},
                {name: 'passThroughParameters'},
                {name: 'target', allowBlank: false},
                {name: 'targetSite'},
                {name: 'statusCode'},
                {name: 'priority', type:'int'},
                {name: 'expiry', type: "date", convert: function (v, r) {
                    if(v) {
                        var d = new Date(intval(v) * 1000);
                        return d;
                    }
                }},
                {name: 'creationDate'},
                {name: 'modificationDate'}
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
                        var proxy = this.store.getProxy();
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
                    var input = field;
                    var proxy = this.store.getProxy();
                    proxy.extraParams.filter = input.getValue();
                    this.store.load();
                }.bind(this)
            }
        }));

        var sourceEntireUrlCheck = new Ext.grid.column.Check({
            header: t("source_entire_url"),
            dataIndex: "sourceEntireUrl",
            width: 70
        });

        var passThroughParametersCheck = new Ext.grid.column.Check({
            header: t("pass_through_params"),
            dataIndex: "passThroughParameters",
            width: 70
        });

        var typesColumns = [
            {header: t("source"), flex: 200, sortable: true, dataIndex: 'source', editor: new Ext.form.TextField({})},
            sourceEntireUrlCheck,
            {header: t("source_site"), flex: 200, sortable:true, dataIndex: "sourceSite",
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
            {header: t("target"), flex: 200, sortable: false, dataIndex: 'target',
                editor: new Ext.form.TextField({}),
                tdCls: "input_drop_target"
            },
            {header: t("target_site"), flex: 200, sortable:true, dataIndex: "targetSite",
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
            {header: t("expiry"), width: 80, sortable:true, dataIndex: "expiry", editor: new Ext.form.DateField(),
                                                                            renderer: function(d) {
                if(d instanceof Date) {
                    return Ext.Date.format(d, "Y-m-d");
                }
            }},
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                width: 150,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                width: 150,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
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

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
			columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
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

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            var dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    try {
                        var record = data.records[0];
                        var data = record.data;

                        if(in_array(data.type,["page","link","hardlink"])) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }
                    } catch (e) {
                        console.log(e);
                    }
                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    try {
                        var record = data.records[0];
                        var data = record.data;
                        if (in_array(data.type, ["page", "link", "hardlink"])) {
                            var rec = this.grid.getStore().getAt(myRowIndex);
                            rec.set("target", data.path);
                            this.updateRows();
                            return true;
                        }
                    } catch (e) {
                        console.log(e);
                    }
                    return false;

                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0, {
            source: ""
        });

		this.updateRows();
    },

    openWizard: function () {

        this.wizardForm = new Ext.form.FormPanel({
            bodyStyle: "padding:10px;",
            items: [{
                xtype:"fieldset",
                layout: 'hbox',
                border: false,
                padding: 0,
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
                    fieldLabel: t("pattern"),
                    emptyText: t("select")
                }, {
                    xtype: "textfield",
                    name: "pattern",
                    margin: "0 0 0 20",
                    width: 330,
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

        var u = {
            source: source,
            sourceEntireUrl: sourceEntireUrl,
            priority: priority
        };
        this.grid.store.insert(0, u);

		this.updateRows();

        this.wizardWindow.close();
    }

});