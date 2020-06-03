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
        var url = Routing.generate('pimcore_admin_redirects_redirects');

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
        var redirectStore = this.store;

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 400,
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

        var getRedirectTypeCombo = this.getRedirectTypeCombo();

        var typesColumns = [
            {
                text: t("type"),
                width: 200,
                sortable: true,
                dataIndex: 'type',
                editor: getRedirectTypeCombo,
                renderer: function (redirectType) {
                    var store = getRedirectTypeCombo.getStore();
                    var pos = store.findExact("type", redirectType);
                    if(pos >= 0) {
                        return store.getAt(pos).get("name");
                    }
                    return redirectType;
                }
            },
            {text: t("source_site") + ' (' + t('optional') + ')', flex: 200, sortable:true, dataIndex: "sourceSite",
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
            {
                text: t("source"),
                flex: 200,
                sortable: true,
                dataIndex: 'source',
                editor: new Ext.form.TextField({}),
                renderer: function (value) {
                    return Ext.util.Format.htmlEncode(value);
                }
            },
            {
                text: t("target_site") + ' (' + t('optional') + ')', flex: 200, sortable: true, dataIndex: "targetSite",
                editor: new Ext.form.ComboBox({
                    store: pimcore.globalmanager.get("sites"),
                    valueField: "id",
                    displayField: "domain",
                    editable: false,
                    triggerAction: "all"
                }), renderer: function (siteId) {
                    var store = pimcore.globalmanager.get("sites");
                    var pos = store.findExact("id", siteId);
                    if (pos >= 0) {
                        return store.getAt(pos).get("domain");
                    }
                }
            },
            {
                text: t("target"), flex: 200, sortable: false, dataIndex: 'target',
                editor: new Ext.form.TextField({}),
                tdCls: "input_drop_target",
                renderer: function (value) {
                    return Ext.util.Format.htmlEncode(value);
                }
            },
            {text: t("status"), width: 70, sortable: true, dataIndex: 'statusCode', editor: new Ext.form.ComboBox({
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
            {text: t("priority"), width: 60, sortable: true, dataIndex: 'priority',
                editor: new Ext.form.ComboBox({
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
                width: 70,
                listeners: {
                    beforecheckchange: function (column, rowIndex, checked, eOpts) {
                        if(checked) {
                            Ext.MessageBox.show({
                                title: t("warning"),
                                msg: t("redirect_performance_warning"),
                                buttons: Ext.MessageBox.YESNO,
                                fn: function (result) {
                                    if (result === 'yes') {
                                        var record = redirectStore.getAt(rowIndex);
                                        record.set('regex', true);
                                    }
                                }
                            });
                        }
                    }
                }
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
                text: t("expiry") + ' (' + t('optional') + ')',
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
            cls: 'pimcore_main_toolbar',
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
                        pimcore.helpers.download(Routing.generate('pimcore_admin_redirects_csvexport'));
                    }
                },
                {
                    text: t("import_csv"),
                    iconCls: "pimcore_icon_import",
                    handler: function () {
                        pimcore.helpers.uploadDialog(
                            Routing.generate('pimcore_admin_redirects_csvimport'), 'redirects',
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
                                        bodyStyle: "padding: 10px;",
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
                {
                    text: t("redirects_expired_cleanup"),
                    iconCls: "pimcore_icon_cleanup",
                    handler: function () {
                        Ext.MessageBox.show({
                            title: t('redirects_expired_cleanup'),
                            msg: t('redirects_cleanup_warning'),
                            buttons: Ext.Msg.OKCANCEL,
                            icon: Ext.MessageBox.INFO,
                            fn: function (button) {
                                if (button == "ok") {
                                    this.cleanupExpiredRedirects();
                                }
                            }.bind(this)
                        });
                    }.bind(this)
                },
                "->",
                {
                    text: t("search") + " / " + t("test_url"),
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
        this.grid.on('validateedit', function (editor, context) {

            if(context["field"] == 'priority' && context['value'] == 99) {
                Ext.MessageBox.show({
                    title: t("warning"),
                    msg: t("redirect_performance_warning"),
                    buttons: Ext.MessageBox.YESNO,
                    fn: function (result) {
                        if (result === 'yes') {
                            context['record'].set('priority', 99);
                        }
                    }
                });

                return false;
            }

            if(context["field"] == 'type' && context['value'] == 'auto_create') {
                return false;
            }
        });

        return this.grid;
    },

    cleanupExpiredRedirects: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_redirects_cleanup'),
            method: 'DELETE',
            success: function (response) {
                try{
                    var data = Ext.decode(response.responseText);
                    if (data && data.success) {
                        this.store.reload();
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("redirects_cleanup_error"), "error");
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("redirects_cleanup_error"), "error");
                }
            }.bind(this)
        });
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
                    if (data.records.length == 1) {
                        try {
                            var record = data.records[0];
                            var data = record.data;

                            if (in_array(data.type, ["page", "link", "hardlink","image", "text", "audio", "video", "document"])) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }
                        } catch (e) {
                            console.log(e);
                        }
                    }
                    return Ext.dd.DropZone.prototype.dropNotAllowed;

                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                        try {
                            var record = data.records[0];
                            var data = record.data;
                            if (in_array(data.type, ["page", "link", "hardlink","image", "text", "audio", "video", "document"])) {
                                var rec = this.grid.getStore().getAt(myRowIndex);
                                rec.set("target", data.path);
                                this.updateRows();
                                return true;
                            }
                        } catch (e) {
                            console.log(e);
                        }
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
        var typeCombo = this.getRedirectTypeCombo({
            name: 'type',
            fieldLabel: t("type"),
            value: 'path'
        });

        this.wizardForm = new Ext.form.FormPanel({
            bodyStyle: "padding:10px;",
            items: [typeCombo, {
                xtype: "textfield",
                name: "pattern",
                width: 600,
                emptyText: "/some/example/path",
                fieldLabel: t("source")
            }, {
                xtype: "textfield",
                name: "target",
                width: 600,
                emptyText: "/some/example/path",
                fieldLabel: t("target")
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
            type: values['type'],
            priority: 1,
            regex: false,
            active: true,
            source: pattern.replace('+', ' '),
            target: values['target']
        };

        this.grid.store.insert(0, record);
        this.updateRows();

        this.wizardWindow.close();
    },

    getRedirectTypeCombo: function (config) {

        var redirectTypesStore = Ext.create('Ext.data.ArrayStore', {
            fields: ['type', 'name'],
            data : [
                ["entire_uri", t('redirects_type_entire_uri') + ': https://host.com/foo?key=value'],
                ["path_query", t('redirects_type_path_query') + ': /foo?key=value'],
                ["path", t('redirects_type_path') + ': /foo'],
                ["auto_create", t('auto_create')],
            ]
        });

        if(!config) {
            config = {};
        }

        config = Ext.merge({
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
        }, config);

        return new Ext.form.ComboBox(config)
    }
});
