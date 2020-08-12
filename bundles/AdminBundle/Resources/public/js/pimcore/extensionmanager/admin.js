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

pimcore.registerNS("pimcore.extensionmanager.admin");
pimcore.extensionmanager.admin = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_extensionmanager_admin");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_extensionmanager_admin",
                title: t("manage_extensions"),
                iconCls: "pimcore_icon_plugin",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_extensionmanager_admin");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extensionmanager_admin");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getExtensionId: function (record) {
        var extensionId = record.get('extensionId');
        if (extensionId) {
            return extensionId;
        }

        return record.get('id');
    },

    getGrid: function () {
        var self = this;

        var modelName = 'pimcore.model.extensions.admin';
        if (!Ext.ClassManager.get(modelName)) {
            Ext.define(modelName, {
                extend: 'Ext.data.Model',
                fields: [
                    "id", "extensionId", "type", "name", "description", "installed", "installable", "uninstallable", "active",
                    "configuration", "canChangeState", "version", "priority", "environments"
                ],
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_extensionmanager_extensionmanager_getextensions'),
                    reader: {
                        type: 'json',
                        rootProperty: 'extensions'
                    },
                    writer: {
                        type: 'json',
                        rootProperty: 'extensions',
                        allowSingle: false
                    },
                    actionMethods: {
                        read: 'GET',
                        update: 'PUT'
                    }
                }
            });
        }

        this.store = new Ext.data.Store({
            model: 'pimcore.model.extensions.admin',
            autoSync: true,
            listeners: {
                beforesync: function () {
                    self.panel.setLoading(true);
                },

                update: function () {
                    self.panel.setLoading(false);
                }
            }
        });

        this.store.load();

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t("refresh"),
                    iconCls: "pimcore_icon_reload",
                    handler: this.reload.bind(this)
                },
                '->',
                '<b id="ext-manager-reload-info" style="visibility: hidden">' + t("please_dont_forget_to_reload_pimcore_after_modifications") + '!</b>',
                {
                    text: t("clear_cache_and_reload"),
                    iconCls: "pimcore_icon_clear_cache",
                    handler: function() {
                        Ext.Msg.confirm(t('warning'), t('system_performance_stability_warning'), function (btn) {
                            if (btn === 'yes') {
                                self.panel.setLoading(true);

                                Ext.Ajax.request({
                                    url: Routing.generate('pimcore_admin_settings_clearcache'),
                                    method: 'DELETE',
                                    params: {
                                        only_symfony_cache: true
                                    },
                                    success: function () {
                                        window.location.reload();
                                    },
                                    failure: function () {
                                        self.panel.setLoading(false);
                                    }
                                });
                            }
                        });
                    }.bind(this)
                }
            ]
        });

        var handleSuccess = function (transport) {
            var res = Ext.decode(transport.responseText);

            var message = '';
            var showAsToast = true;

            if (res.reload) {
                message += t("please_dont_forget_to_reload_pimcore_after_modifications") + "!";

                // show reload message
                Ext.get('ext-manager-reload-info').show();
                toolbar.updateLayout();
            }

            if (res.message) {
                showAsToast = false;

                if (message) {
                    message = '<p style="text-align: center">' + message + '</p>';
                    message += '<br /><hr />';
                }

                message += '<pre style="font-size:11px;word-wrap: break-word;margin-bottom: 0">';

                if (Ext.isArray(res.message)) {
                    Ext.Array.each(res.message, function(line) {
                        if (message.length > 0) {
                            message += "\n"
                        }

                        message += strip_tags(line);
                    });
                } else {
                    if (message.length > 0) {
                        message += "\n"
                    }

                    message += strip_tags(res.message);
                }

                message += '</pre>';
            }

            self.panel.setLoading(false);
            this.reload();

            if (!empty(message)) {
                if (showAsToast) {
                    pimcore.helpers.showNotification(t("success"), message, "success");
                } else {
                    self.showMessageWindow(t("success"), message, "success");
                }
            }
        }.bind(this);

        var handleFailure = function() {
            this.panel.setLoading(false);
        }.bind(this);

        var typesColumns = [
            {text: t("type"), width: 50, sortable: false, dataIndex: 'type', renderer:
            function (value, metaData, record, rowIndex, colIndex, store) {
                return '<div class="pimcore_icon_' + value + '" style="min-height: 16px;" title="' + t("value") +'"></div>';
            }},
            {
                text: "ID", width: 100, sortable: true, dataIndex: 'id', flex: 1,
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    return self.getExtensionId(record);
                }
            },
            {text: t("name"), width: 200, sortable: true, dataIndex: 'name', flex: 2},
            {text: t("version"), width: 80, sortable: false, dataIndex: 'version'},
            {text: t("description"), width: 200, sortable: true, dataIndex: 'description', flex: 4},
            {
                text: t('enable') + " / " + t("disable"),
                menuText: t('enable') + " / " + t("disable"),
                xtype: 'actioncolumn',
                width: 100,
                items: [{
                    tooltip: t('enable') + " / " + t("disable"),
                    getClass: function (v, meta, rec) {
                        var klass = "pimcore_action_column ";

                        if ('bundle' === rec.get('type') && !rec.get('canChangeState')) {
                            klass += "pimcore_icon_lock ";
                        } else if(rec.get("active")) {
                            klass += "pimcore_icon_stop ";
                        } else {
                            klass += "pimcore_icon_add ";
                        }
                        return klass;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        var method = rec.get("active") ? "disable" : "enable";

                        // abort if state changes are not allowed
                        if ('bundle' === rec.get('type') && !rec.get('canChangeState')) {
                            self.showMessageWindow(t("error"), t("extension_manager_state_change_not_allowed"), "error");

                            return;
                        }

                        this.panel.setLoading(true);

                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_extensionmanager_extensionmanager_toggleextensionstate'),
                            method: 'PUT',
                            params: {
                                method: method,
                                id: self.getExtensionId(rec),
                                type: rec.get("type")
                            },
                            success: handleSuccess,
                            failure: handleFailure
                        });
                    }.bind(this)
                }]
            },
            {
                text: t('install') + "/" + t("uninstall"),
                menuText: t('install') + "/" + t("uninstall"),
                xtype: 'actioncolumn',
                width: 100,
                items: [{
                    tooltip: t('install') + "/" + t("uninstall"),
                    getClass: function (v, meta, rec) {
                        if (rec.get('installable')) {
                            return 'pimcore_action_column pimcore_icon_add';
                        } else if (rec.get('uninstallable')) {
                            return 'pimcore_action_column pimcore_icon_stop';
                        } else {
                            return '';
                        }
                    },
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);

                        var route = false;
                        if (rec.get('installable')) {
                            route = 'pimcore_admin_extensionmanager_extensionmanager_install';
                        } else if (rec.get('uninstallable')) {
                            route = 'pimcore_admin_extensionmanager_extensionmanager_uninstall';
                        } else {
                            return;
                        }

                        this.panel.setLoading(true);

                        Ext.Ajax.request({
                            url: Routing.generate(route),
                            method: 'POST',
                            params: {
                                id: self.getExtensionId(rec),
                                type: rec.get("type")
                            },
                            success: handleSuccess,
                            failure: handleFailure
                        });
                    }.bind(this)
                }]
            },
            {
                text: t('configure'),
                menuText: t('configure'),
                xtype: 'actioncolumn',
                width: 70,
                items: [{
                    tooltip: t('configure'),
                    getClass: function (v, meta, rec) {
                        if (rec.get('active') && rec.get('installed')) {
                            if (rec.get("configuration")) {
                                return "pimcore_action_column pimcore_icon_edit";
                            }
                        }

                        return "";
                    },
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                        var type = rec.get("type");

                        var iframeSrc = rec.get("configuration");
                        if (iframeSrc && iframeSrc.length > 0) {
                            iframeSrc += "?systemLocale=" + pimcore.globalmanager.get("user").language;
                        } else {
                            iframeSrc = null;
                        }

                        var extensionId = self.getExtensionId(rec);
                        if (iframeSrc) {
                            extensionId = extensionId.replace(/[/\\*]/g, "_");
                            pimcore.helpers.openGenericIframeWindow("extension_settings_" + extensionId + "_" + type, iframeSrc, "pimcore_icon_plugin", extensionId);
                        }
                    }.bind(this)
                }]
            },
            {
                text: t("priority"),
                menuText: t("priority"),
                width: 80,
                align: 'right',
                sortable: true,
                dataIndex: 'priority',
                editor: new Ext.form.Number({})
            },
            {
                text: t("environments"),
                menuText: t("environments"),
                width: 100,
                sortable: false,
                dataIndex: 'environments',
                editor: new Ext.form.TextField({})
            }
        ];

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : {
                items: typesColumns,
                defaults: {
                    flex: 0
                }
            },
            plugins: [
                Ext.create('Ext.grid.plugin.CellEditing', {
                    clicksToEdit: 1,
                    listeners: {
                        beforeedit: function (editor, context, eOpts) {
                            // only allow editing for bundles
                            if (context.record.data.type !== 'bundle') {
                                return false;
                            }

                            // abort if state changes are not allowed
                            if (!context.record.data.canChangeState) {
                                return false;
                            }
                        }
                    }
                })
            ],
            trackMouseOver: true,
            columnLines: true,
            stripeRows: true,
            tbar: toolbar,
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },

    showMessageWindow: function (title, message, type) {
        var win = new Ext.Window({
            modal: true,
            iconCls: 'pimcore_icon_' + type,
            title: title,
            width: 700,
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
    },

    reload: function () {
        this.store.reload();
    }
});
