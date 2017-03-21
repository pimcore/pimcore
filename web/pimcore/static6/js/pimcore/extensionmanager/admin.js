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
                    "configuration", "updateable", "xmlEditorFile", "version"
                ],
                proxy: {
                    type: 'ajax',
                    url: '/admin/extensionmanager/admin/get-extensions',
                    reader: {
                        type: 'json',
                        rootProperty: "extensions"
                    }
                }
            });
        }

        this.store = new Ext.data.Store({
            model: 'pimcore.model.extensions.admin'
        });

        this.store.load();

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
                        
                        Ext.Ajax.request({
                            url: '/admin/extensionmanager/admin/toggle-extension-state',
                            params: {
                                method: method,
                                id: self.getExtensionId(rec),
                                type: rec.get("type"),
                                extensionType: self.getExtensionType(rec)
                            },
                            success: function (transport) {
                                var res = Ext.decode(transport.responseText);

                                if(!empty(res.message)) {
                                    Ext.Msg.alert(" ", res.message);
                                }

                                if(res.reload) {
                                    window.location.reload();
                                } else {
                                    this.reload();
                                }
                            }.bind(this)
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

                        Ext.Ajax.request({
                            url: '/admin/extensionmanager/admin/' + method,
                            params: {
                                id: self.getExtensionId(rec),
                                type: rec.get("type"),
                                extensionType: self.getExtensionType(rec)
                            },
                            success: function (transport) {
                                var res = Ext.decode(transport.responseText);

                                if(!empty(res.message)) {
                                    Ext.Msg.alert(" ", res.message);
                                }

                                if(res.reload) {
                                    window.location.reload();
                                } else {
                                    this.reload();
                                }
                            }.bind(this)
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

                        Ext.Ajax.request({
                            url: '/admin/extensionmanager/admin/update',
                            params: {
                                id: self.getExtensionId(rec),
                                type: rec.get("type"),
                                extensionType: self.getExtensionType(rec)
                            },
                            success: function (transport) {
                                var res = Ext.decode(transport.responseText);

                                if(!empty(res.message)) {
                                    Ext.Msg.alert(" ", res.message);
                                }

                                if(res.reload) {
                                    window.location.reload();
                                } else {
                                    this.reload();
                                }
                            }.bind(this)
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
                        var iframeSrc = rec.get("configuration") + "?systemLocale="
                                                                        + pimcore.globalmanager.get("user").language;

                        var handled = false;
                        if (self.isLegacyType(rec.get('type'))) {
                            // TODO DEPRECATED xml editor is deprecated as of pimcore 5
                            var xmlEditorFile = rec.get("xmlEditorFile");

                            if (xmlEditorFile) {
                                try {
                                    pimcore.globalmanager.get("extension_settings_" + id + "_" + type).activate();
                                }
                                catch (e) {
                                    pimcore.globalmanager.add("extension_settings_" + id + "_" + type, new pimcore.extensionmanager.xmlEditor(id, type, xmlEditorFile));
                                }

                                handled = true;
                            }
                        }

                        if (!handled && iframeSrc) {
                            pimcore.helpers.openGenericIframeWindow("extension_settings_" + id + "_" + type, iframeSrc, "pimcore_icon_plugin", id);
                            handled = true;
                        }
                    }.bind(this)
                }]
            },
            {
                dataIndex: 'xmlEditorFile',
                hidden: true,
                hideable: false
            }
        ];

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [
                {
                    text: t("refresh"),
                    iconCls: "pimcore_icon_reload",
                    handler: this.reload.bind(this)
                }, "-", {
                    text: t("create_new_plugin_skeleton"),
                    iconCls: "pimcore_icon_plugin pimcore_icon_overlay_add",
                    handler: function () {
                        Ext.MessageBox.prompt(t('create_new_plugin_skeleton'), t('enter_the_name_of_the_new_extension') + "(a-zA-Z0-9_)",  function (button, value) {
                            var regresult = value.match(/[a-zA-Z0-9_]+/);

                            if (button == "ok" && value.length > 2) {
                                Ext.Ajax.request({
                                    url: "/admin/extensionmanager/admin/create",
                                    params: {
                                        name: value
                                    },
                                    success: function (response) {
                                        var data = Ext.decode(response.responseText);
                                        if(data && data.success) {
                                            this.reload();
                                        } else {
                                            Ext.Msg.alert(t('create_new_plugin_skeleton'), t('invalid_plugin_name'));
                                        }
                                    }.bind(this)
                                });
                            }
                            else if (button == "cancel") {
                                return;
                            }
                            else {
                                Ext.Msg.alert(t('create_new_plugin_skeleton'), t('invalid_plugin_name'));
                            }
                        }.bind(this));
                    }.bind(this)
                }, {
                    text: t("upload_plugin") + " (ZIP)",
                    iconCls: "pimcore_icon_upload",
                    handler: function () {
                        pimcore.helpers.uploadDialog('/admin/extensionmanager/admin/upload', "zip", function(res) {
                            this.reload();
                        }.bind(this), function () {
                            Ext.MessageBox.alert(t("error"), t("error"));
                        });
                    }.bind(this)
                }, "->" ,
                "<b>" + t("please_dont_forget_to_reload_pimcore_after_modifications") + "!</b>"
            ]
        });

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

    reload: function () {
        this.store.reload();
    }
});
