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
                iconCls: "pimcore_icon_plugin pimcore_icon_overlay_edit",
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

    isLegacyType: function (type) {
        return Ext.Array.contains(['plugin', 'brick'], type);
    },

    getExtensionId: function (record) {
        var extensionId = record.get('extensionId');
        if (extensionId) {
            return extensionId;
        }

        return record.get('id');
    },

    getExtensionType: function (record) {
        if (this.isLegacyType(record.get('type'))) {
            return 'legacy';
        }

        return null;
    },

    getGrid: function () {
        var self = this;

        var modelName = 'pimcore.model.extensions.admin';
        if (!Ext.ClassManager.get(modelName)) {
            Ext.define(modelName, {
                extend: 'Ext.data.Model',
                fields: [
                    "id", "extensionId", "type", "name", "description", "installed", "installable", "uninstallable", "active",
                    "configuration", "updateable", "xmlEditorFile", "version", "priority", "environments"
                ],
                proxy: {
                    type: 'ajax',
                    url: '/admin/extensionmanager/admin/extensions',
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
            cls: 'main-toolbar',
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
                                    url: '/admin/settings/clear-cache',
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
            {header: t("type"), width: 50, sortable: false, dataIndex: 'type', renderer:
            function (value, metaData, record, rowIndex, colIndex, store) {
                return '<div class="pimcore_icon_' + value + '" style="min-height: 16px;" title="' + t("value") +'"></div>';
            }},
            {
                header: "ID", width: 100, sortable: true, dataIndex: 'id', flex: 1,
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    return self.getExtensionId(record);
                }
            },
            {header: t("name"), width: 200, sortable: true, dataIndex: 'name', flex: 2},
            {header: t("version"), width: 80, sortable: false, dataIndex: 'version'},
            {header: t("description"), width: 200, sortable: true, dataIndex: 'description', flex: 4},
            {
                header: t('enable') + " / " + t("disable"),
                xtype: 'actioncolumn',
                width: 100,
                items: [{
                    tooltip: t('enable') + " / " + t("disable"),
                    getClass: function (v, meta, rec) {
                        var klass = "pimcore_action_column ";
                        if(rec.get("active")) {
                            klass += "pimcore_icon_stop ";
                        } else {
                            klass += "pimcore_icon_add ";
                        }
                        return klass;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        var method = rec.get("active") ? "disable" : "enable";

                        this.panel.setLoading(true);

                        Ext.Ajax.request({
                            url: '/admin/extensionmanager/admin/toggle-extension-state',
                            params: {
                                method: method,
                                id: self.getExtensionId(rec),
                                type: rec.get("type"),
                                extensionType: self.getExtensionType(rec)
                            },
                            success: handleSuccess,
                            failure: handleFailure
                        });
                    }.bind(this)
                }]
            },
            {
                header: t('install') + "/" + t("uninstall"),
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

                        var method = false;
                        if (rec.get('installable')) {
                            method = 'install';
                        } else if (rec.get('uninstallable')) {
                            method = 'uninstall';
                        } else {
                            return;
                        }

                        this.panel.setLoading(true);

                        Ext.Ajax.request({
                            url: '/admin/extensionmanager/admin/' + method,
                            params: {
                                id: self.getExtensionId(rec),
                                type: rec.get("type"),
                                extensionType: self.getExtensionType(rec)
                            },
                            success: handleSuccess,
                            failure: handleFailure
                        });
                    }.bind(this)
                }]
            },
            {
                header: t('update'),
                xtype: 'actioncolumn',
                width: 100,
                items: [{
                    tooltip: t('update'),
                    getClass: function (v, meta, rec) {
                        if (rec.get('updateable')) {
                            return 'pimcore_action_column pimcore_icon_add';
                        }

                        return '';
                    },
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);

                        // legacy types can't be updated
                        if (self.isLegacyType(rec.get('type'))) {
                            return;
                        }

                        if (!rec.get('updateable')) {
                            return;
                        }

                        this.panel.setLoading(true);

                        Ext.Ajax.request({
                            url: '/admin/extensionmanager/admin/update',
                            params: {
                                id: self.getExtensionId(rec),
                                type: rec.get("type"),
                                extensionType: self.getExtensionType(rec)
                            },
                            success: handleSuccess,
                            failure: handleFailure
                        });
                    }.bind(this)
                }]
            },
            {
                header: t('configure'),
                xtype: 'actioncolumn',
                width: 70,
                items: [{
                    tooltip: t('configure'),
                    getClass: function (v, meta, rec) {
                        var klass = "pimcore_action_column ";
                        if (rec.get('active') && rec.get('installed')) {
                            if (rec.get("configuration") || rec.get("xmlEditorFile")) {
                                return "pimcore_action_column pimcore_icon_edit";
                            }
                        }

                        return "";
                    },
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                        var id = rec.get("id");
                        var type = rec.get("type");

                        var iframeSrc = rec.get("configuration");
                        if (iframeSrc && iframeSrc.length > 0) {
                            iframeSrc += "?systemLocale=" + pimcore.globalmanager.get("user").language;
                        } else {
                            iframeSrc = null;
                        }

                        var handled = false;
                        var extensionId = self.getExtensionId(rec);

                        if (self.isLegacyType(rec.get('type'))) {
                            // TODO DEPRECATED xml editor is deprecated as of pimcore 5
                            var xmlEditorFile = rec.get("xmlEditorFile");

                            if (xmlEditorFile) {
                                try {
                                    pimcore.globalmanager.get("extension_settings_" + extensionId + "_" + type).activate();
                                }
                                catch (e) {
                                    pimcore.globalmanager.add("extension_settings_" + extensionId + "_" + type, new pimcore.extensionmanager.xmlEditor(extensionId, type, xmlEditorFile));
                                }

                                handled = true;
                            }
                        }

                        if (!handled && iframeSrc) {
                            extensionId = extensionId.replace(/[/\\*]/g, "_");
                            pimcore.helpers.openGenericIframeWindow("extension_settings_" + extensionId + "_" + type, iframeSrc, "pimcore_icon_plugin", extensionId);
                        }
                    }.bind(this)
                }]
            },
            {
                dataIndex: 'xmlEditorFile',
                hidden: true,
                hideable: false
            },
            {
                header: t("priority"),
                width: 80,
                align: 'right',
                sortable: true,
                dataIndex: 'priority',
                editor: new Ext.form.Number({})
            },
            {
                header: t("environments"),
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
    },

    reload: function () {
        this.store.reload();
    }
});
