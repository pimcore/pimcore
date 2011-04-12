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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
            fields: ["id","type", "name", "description", "icon", "version", "installed", "active", "configuration"]
        });
        this.store.load();

        var typesColumns = [
            {header: t("type"), width: 30, sortable: false, dataIndex: 'type', renderer: function (value, metaData, record, rowIndex, colIndex, store) {

                var icon = "";
                if(value == "plugin") {
                    icon = "cog.png";
                } else if (value = "brick") {
                    icon = "bricks.png";
                }
                return '<img src="/pimcore/static/img/icon/' + icon + '" alt="'+ t("value") +'" title="'+ t("value") +'" />';
            }},
            {header: "ID", width: 100, sortable: true, dataIndex: 'id'},
            {header: t("name"), width: 200, sortable: true, dataIndex: 'name'},
            {header: t("description"), id: "extension_description", width: 200, sortable: true, dataIndex: 'description'},
            {header: t("version"), width: 50, sortable: true, dataIndex: 'version'},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('enable') + " / " + t("disable"),
                    getClass: function (v, meta, rec) {
                        var class = "pimcore_action_column ";
                        if(rec.get("active")) {
                            class += "pimcore_icon_decline ";
                        } else {
                            class += "pimcore_icon_accept ";
                        }
                        return class;
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
                            success: this.reload.bind(this)
                        });
                    }.bind(this)
                }]
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('install') + "/" + t("uninstall"),
                    getClass: function (v, meta, rec) {
                        var class = "pimcore_action_column ";
                        if(rec.get("installed") == null) {
                            return "";
                        } else if(rec.get("installed")) {
                            class += "pimcore_icon_delete ";
                        } else {
                            class += "pimcore_icon_add ";
                        }
                        return class;
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
                            success: this.reload.bind(this)
                        });
                    }.bind(this)
                }]
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('update'),
                    icon: "/pimcore/static/img/icon/disconnect.png",
                    handler: function (grid, rowIndex) {

                        //grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('configure'),
                    icon: "/pimcore/static/img/icon/bullet_edit.png",
                    handler: function (grid, rowIndex) {

                        //grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {

                        //grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
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