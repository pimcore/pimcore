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

pimcore.registerNS("pimcore.extensionmanager.admin");
pimcore.extensionmanager.admin = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extensionmanager_admin");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_extensionmanager_admin",
                title: t("manage_extensions"),
                iconCls: "pimcore_icon_extensionmanager_admin",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_extensionmanager_admin");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extensionmanager_admin");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getGrid: function () {
        
        this.store = new Ext.data.JsonStore({
            id: 'redirects_store',
            url: '/admin/extensionmanager/admin/get-extensions',
            restful: false,
            root: "extensions",
            fields: ["id","type", "name", "description", "installed", "active", "configuration","updateable",
                                                                        "xmlEditorFile","version"]
        });
        this.store.load();

        var typesColumns = [
            {header: t("type"), width: 30, sortable: false, dataIndex: 'type', renderer:
                                        function (value, metaData, record, rowIndex, colIndex, store) {

                var icon = "";
                if(value == "plugin") {
                    icon = "cog.png";
                } else if (value == "brick") {
                    icon = "bricks.png";
                }
                return '<img src="/pimcore/static/img/icon/' + icon + '" alt="'+ t("value") +'" title="'
                                                             + t("value") +'" />';
            }},
            {header: "ID", width: 100, sortable: true, dataIndex: 'id'},
            {header: t("name"), width: 200, sortable: true, dataIndex: 'name'},
            {header: t("version"), width: 40, sortable: false, dataIndex: 'version'},
            {header: t("description"), id: "extension_description", width: 200, sortable: true,
                                                                dataIndex: 'description'},
            {
                header: t('enable') + " / " + t("disable"),
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('enable') + " / " + t("disable"),
                    getClass: function (v, meta, rec) {
                        var klass = "pimcore_action_column ";
                        if(rec.get("active")) {
                            klass += "pimcore_icon_disable ";
                        } else {
                            klass += "pimcore_icon_add ";
                        }
                        return klass;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        var method = rec.get("active") ? "disable" : "enable";
                        
                        Ext.Ajax.request({
                            url: "/admin/extensionmanager/admin/toggle-extension-state",
                            params: {
                                method: method,
                                id: rec.get("id"),
                                type: rec.get("type")
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
                width: 30,
                items: [{
                    tooltip: t('install') + "/" + t("uninstall"),
                    getClass: function (v, meta, rec) {
                        var klass = "";

                        // bricks don't have an install state
                        if(rec.get("type") == "brick") {
                            return klass;
                        }

                        if(rec.get("installed") == null) {
                            return "";
                        } else if(rec.get("installed")) {
                            klass += "pimcore_action_column pimcore_icon_disable ";
                        } else {
                            klass += "pimcore_action_column pimcore_icon_add ";
                        }
                        return klass;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);

                        if(rec.get("type") != "plugin") {
                            return;
                        }

                        var method = rec.get("installed") ? "uninstall" : "install";

                        Ext.Ajax.request({
                            url: "/admin/extensionmanager/admin/" + method,
                            params: {
                                id: rec.get("id"),
                                type: rec.get("type")
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
                width: 30,
                items: [{
                    tooltip: t('configure'),
                    getClass: function (v, meta, rec) {
                        var klass = "pimcore_action_column ";
                        if(rec.get("configuration") || rec.get("xmlEditorFile") && rec.get("active")
                                                                                    && rec.get("installed")) {
                            klass += "pimcore_icon_edit ";
                        } else {
                            return "";
                        }
                        return klass;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        var id = rec.get("id");
                        var type = rec.get("type");
                        var iframeSrc = rec.get("configuration") + "?systemLocale="
                                                                        + pimcore.globalmanager.get("user").language;
                        var xmlEditorFile =  rec.get("xmlEditorFile");

                        try {
                            pimcore.globalmanager.get("extension_settings_" + id + "_" + type).activate();
                        }
                        catch (e) {
                            if(xmlEditorFile){
                                pimcore.globalmanager.add("extension_settings_" + id + "_" + type,
                                                new pimcore.extensionmanager.xmlEditor(id, type, xmlEditorFile));
                            }else{
                                pimcore.globalmanager.add("extension_settings_" + id + "_" + type,
                                                new pimcore.extensionmanager.settings(id, type, iframeSrc));
                            }
                        }
                    }.bind(this)
                }]
            },
            {
                header: t('delete'),
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    getClass: function (v, meta, rec) {
                        var klass = "";

                        if(rec.get("active") != true && rec.get("type") == "brick") {
                            klass += "pimcore_action_column pimcore_icon_delete ";
                        }

                        if(rec.get("active") != true && rec.get("installed") != true && rec.get("type") == "plugin") {
                            klass += "pimcore_action_column pimcore_icon_delete ";
                        }

                        return klass;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        Ext.Ajax.request({
                            url: "/admin/extensionmanager/admin/delete",
                            params: {
                                id: rec.get("id"),
                                type: rec.get("type")
                            },
                            success: function (transport) {
                                this.reload();
                            }.bind(this)
                        });
                    }.bind(this)
                }]
            },
            {
                dataIndex: 'xmlEditorFile',
                hidden: true,
                hideable: false
            }
        ];

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
			columns : typesColumns,
            autoExpandColumn: "extension_description",
            trackMouseOver: true,
            columnLines: true,
            stripeRows: true,
            tbar: [{
                text: t("refresh"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            },"-",{
                text: t("create_new_plugin_skeleton"),
                iconCls: "pimcore_icon_plugin_add",
                handler: function () {
                    Ext.MessageBox.prompt(t('create_new_plugin_skeleton'), t('enter_the_name_of_the_new_plugin'),  function (button, value) {
                        if(button == "ok") {
                            Ext.Ajax.request({
                                url: "/admin/extensionmanager/admin/create",
                                params: {
                                    name: value
                                },
                                success: function () {
                                    this.reload();
                                }.bind(this)
                            });
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
            }, "->" , "<b>" + t("please_dont_forget_to_reload_pimcore_after_modifications") + "!</b>"],
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