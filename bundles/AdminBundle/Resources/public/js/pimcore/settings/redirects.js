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
        var that = this;

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url = '/admin/redirects/list?';

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            [
                {name: 'id'},
                {name: 'type', allowBlank: false},
                {name: 'source', allowBlank: false},
                {name: 'sourceSite'},
                {name: 'target', allowBlank: false},
                {name: 'targetSite'},
                {name: 'statusCode'},
                {name: 'priority', type:'int'},
                {name: 'regex'},
                {name: 'passThroughParameters'},
                {name: 'active'},
                {name: 'expiry', type: "date", convert: function (v, r) {
                    if(v && !(v instanceof Date)) {
                        var d = new Date(intval(v) * 1000);
                        return d;
                    } else {
                        return v;
                    }
                }},
                {name: 'creationDate'},
                {name: 'modificationDate'}
            ],
            itemsPerPage
        );

        this.store.getProxy().setBatchActions(false);

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

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

        var sourceEntireUrlCheck = new Ext.grid.column.Check({
            text: t("source_entire_url"),
            dataIndex: "sourceEntireUrl",
            width: 70
        });

        var activeCheck = new Ext.grid.column.Check({
            text: t("active"),
            dataIndex: "active",
            width: 70
        });

        var passThroughParametersCheck = new Ext.grid.column.Check({
            text: t("pass_through_params"),
            dataIndex: "passThroughParameters",
            width: 70
        });

        var redirectTypesStore = Ext.create('Ext.data.ArrayStore', {
            fields: ['type', 'name'],
            data : [
                ["entire_uri", t('redirects_type_entire_uri') + ': https://host.com/test?key=value'],
                ["path_query", t('redirects_type_path_query') + ': /test?key=value'],
                ["path", t('redirects_type_path') + ': /test']
            ]
        });

        var typesColumns = [
            {
                text: t("type"),
                width: 200,
                sortable: true,
                dataIndex: 'type',
                editor: new Ext.form.ComboBox({
                    store: redirectTypesStore,
                    mode: "local",
                    queryMode: "local",
                    typeAhead: false,
                    editable: false,
                    displayField: 'name',
                    valueField: 'type',
                    listConfig: {
                        minWidth: 350
                    },
                    forceSelection: true,
                    triggerAction: "all"
                }),
                renderer: function (redirectType) {
                    var pos = redirectTypesStore.findExact("type", redirectType);
                    if(pos >= 0) {
                        return redirectTypesStore.getAt(pos).get("name");
                    }
                    return redirectType;
                }
            },
            {text: t("source"), flex: 200, sortable: true, dataIndex: 'source', editor: new Ext.form.TextField({})},
            {text: t("source_site"), flex: 200, sortable:true, dataIndex: "sourceSite",
                editor: new Ext.form.ComboBox({
                store: pimcore.globalmanager.get("sites"),
                valueField: "id",
                displayField: "domain",
                editable: false,
                triggerAction: "all"
            }), renderer: function (siteId) {
                var store = pimcore.globalmanager.get("sites");
                var pos = store.findExact("id", siteId);
                if(pos >= 0) {
                    return store.getAt(pos).get("domain");
                }
            }},
            {text: t("target"), flex: 200, sortable: false, dataIndex: 'target',
                editor: new Ext.form.TextField({}),
                tdCls: "input_drop_target"
            },
            {text: t("target_site"), flex: 200, sortable:true, dataIndex: "targetSite",
                editor: new Ext.form.ComboBox({
                store: pimcore.globalmanager.get("sites"),
                valueField: "id",
                displayField: "domain",
                editable: false,
                triggerAction: "all"
            }), renderer: function (siteId) {
                var store = pimcore.globalmanager.get("sites");
                var pos = store.findExact("id", siteId);
                if(pos >= 0) {
                    return store.getAt(pos).get("domain");
                }
            }},
            {text: t("type"), width: 70, sortable: true, dataIndex: 'statusCode', editor: new Ext.form.ComboBox({
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
                listConfig: {minWidth: 200},
                forceSelection: true,
                triggerAction: "all"
            })},
            {text: t("priority"), width: 60, sortable: true, dataIndex: 'priority', editor: new Ext.form.ComboBox({
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
                listConfig: {minWidth: 200},
                editable: false,
                forceSelection: true,
                triggerAction: "all"
            })},
            new Ext.grid.column.Check({
                text: t("regex"),
                dataIndex: "regex",
                width: 70
            }),
            new Ext.grid.column.Check({
                text: t("pass_through_params"),
                dataIndex: "passThroughParameters",
                width: 70
            }),
            new Ext.grid.column.Check({
                text: t("active"),
                dataIndex: "active",
                width: 70
            }),
            {
                text: t("expiry"),
                width: 150, sortable:true, dataIndex: "expiry",
                editor: {
                    xtype: 'datefield',
                    format: 'Y-m-d'
                },
                renderer:
                    function(d) {
                        if(d instanceof Date) {
                            return Ext.Date.format(d, "Y-m-d");
                        }
                    }
            },
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
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
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
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
                menuText: t('delete'),
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
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

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [
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
                },
                {
                    text: t("export_csv"),
                    iconCls: "pimcore_icon_export",
                    handler: function () {
                        pimcore.helpers.download('/admin/redirects/csv-export');
                    }
                },
                {
                    text: t("import_csv"),
                    iconCls: "pimcore_icon_import",
                    handler: function () {
                        pimcore.helpers.uploadDialog(
                            '/admin/redirects/csv-import', 'redirects',
                            function (res) {
                                that.store.reload();

                                var json;

                                try {
                                    json = Ext.decode(res.response.responseText);
                                } catch (e) {
                                    console.error(e);
                                }

                                if (json && json.data) {
                                    var stats = json.data;

                                    var icon = 'pimcore_icon_success';
                                    if (stats.errored > 0) {
                                        icon = 'pimcore_icon_warning';
                                    }

                                    var message = '';

                                    message += '<table class="pimcore_stats_table">';
                                    message += '<tr><th>' + t('redirects_import_total') + '</th><td class="pimcore_stats_table--number">' + stats.total + '</td></tr>';
                                    message += '<tr><th>' + t('redirects_import_created') + '</th><td class="pimcore_stats_table--number">' + stats.created + '</td></tr>';
                                    message += '<tr><th>' + t('redirects_import_updated') + '</th><td  class="pimcore_stats_table--number">' + stats.updated + '</td></tr>';

                                    if (stats.errored > 0) {
                                        message += '<tr><th>' + t('redirects_import_errored') + '</th><td class="pimcore_stats_table--number">' + stats.errored + '</td></tr>';
                                    }

                                    message += '</table>';

                                    if (stats.errors && Object.keys(stats.errors).length > 0) {
                                        message += '<h4 style="margin-top: 15px; margin-bottom: 0; color: red">' + t('redirects_import_errors') + '</h4>';
                                        message += '<table class="pimcore_stats_table">';

                                        var errorKeys = Object.keys(stats.errors);
                                        for (var i = 0; i < errorKeys.length; i++) {
                                            message += '<tr><td>' + t('redirects_import_error_line') + ' ' + errorKeys[i] + ':</td><td>' + stats.errors[errorKeys[i]] + '</td></tr>';
                                        }

                                        message += '</table>';
                                    }

                                    var win = new Ext.Window({
                                        modal: true,
                                        iconCls: icon,
                                        title: t('redirects_csv_import'),
                                        width: 400,
                                        maxHeight: 500,
                                        html: message,
                                        autoScroll: true,
                                        bodyStyle: "padding: 10px; background:#fff;",
                                        buttonAlign: "center",
                                        shadow: false,
                                        closable: false,
                                        buttons: [{
                                            text: t("OK"),
                                            handler: function () {
                                                win.close();
                                            }
                                        }]
                                    });

                                    win.show();
                                }
                            },
                            function () {
                                Ext.MessageBox.alert(t("error"), t("error"));
                            }
                        )
                    }
                },
                "->",
                {
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
			columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            bodyCls: "pimcore_editable_grid",
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            stripeRows: true,
            bbar: this.pagingtoolbar,
            tbar: toolbar,
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
            layout: 'hbox',
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
        });

        this.wizardWindow = new Ext.Window({
            width: 650,
            modal: true,
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
        var values = this.wizardForm.getForm().getFieldValues();
        var pattern = values.pattern;

        var record = {
            type: 'entire_uri',
            source: '',
            priority: 1,
            regex: false,
            active: true
        };

        var escapeRegex = function(pattern) {
            pattern = preg_quote(pattern);
            pattern = str_replace("@", "\\@", pattern);

            return pattern;
        };

        if (values.mode === "begin") {
            record.type = 'path';
            record.source = "@^" + escapeRegex(pattern) + "@";
            record.regex = true;
        } else if (values.mode === "exact") {
            record.type = 'path';
            record.source = pattern.replace('+', ' ');
        } else if (values.mode === "contain") {
            record.type = 'path_query';
            record.source = "@" + escapeRegex(pattern) + "@i";
            record.regex = true;
        } else if (values.mode === "begin_end_slash") {
            if (pattern.charAt(0) !== "/") {
                pattern = "/" + pattern;
            }

            record.type = 'path';
            record.source = "@^" + escapeRegex(pattern) + "[\\/]?$@i";
            record.regex = true;
        } else if (values.mode === "domain") {
            if (values.pattern.indexOf("http") >= 0) {
                pattern = parse_url(values.pattern, "host");
            } else {
                pattern = values.pattern;
            }
            pattern = preg_quote(pattern);

            record.type = 'entire_uri';
            record.source = "@https?://" + pattern + "@";
            record.regex = true;
            record.priority = 99;
        }

        this.grid.store.insert(0, record);
        this.updateRows();

        this.wizardWindow.close();
    }
});
