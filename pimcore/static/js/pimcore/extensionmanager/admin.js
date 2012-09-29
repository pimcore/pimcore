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
            fields: ["id","type", "name", "description", "installed", "active", "configuration","updateable","xmlEditorFile"]
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
                            success: this.reload.bind(this)
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
                        if(rec.get("configuration") || rec.get("xmlEditorFile") && rec.get("active") && rec.get("installed")) {
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
                        var iframeSrc = rec.get("configuration") + "?systemLocale=" + pimcore.globalmanager.get("user").language;
                        var xmlEditorFile =  rec.get("xmlEditorFile");

                        try {
                            pimcore.globalmanager.get("extension_settings_" + id + "_" + type).activate();
                        }
                        catch (e) {
                            if(xmlEditorFile){
                                pimcore.globalmanager.add("extension_settings_" + id + "_" + type, new pimcore.extensionmanager.xmlEditor(id, type, xmlEditorFile));
                            }else{
                                pimcore.globalmanager.add("extension_settings_" + id + "_" + type, new pimcore.extensionmanager.settings(id, type, iframeSrc));
                            }
                        }
                    }.bind(this)
                }]
            },
            {
                header: t('update'),
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('update'),
                    getClass: function (v, meta, rec) {

                        if(rec.get("updateable")) {
                            return"pimcore_action_column pimcore_icon_update";
                        }

                        return "";
                    },
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                        this.openUpdateWindow(rec);
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
            }, "->" , "<b>" + t("please_dont_forget_to_reload_pimcore_after_modifications") + "!</b>"],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },

    reload: function () {
        this.store.reload();
    },

    checkLiveConnect: function (callback) {
        if(!pimcore.settings.liveconnect.isConnected()) {
            pimcore.settings.liveconnect.login(callback);

            return false;
        }

        return true;
    },

    openUpdateWindow: function (rec) {
        if(!this.checkLiveConnect(this.openUpdateWindow.bind(this, rec))) {
            return;
        }

        this.updateRecord = rec;

        this.updateWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 200,
            items: [{
                html: t("collecting_update_information"),
                bodyStyle: "padding: 10px;"
            }]
        });

        this.updateWindow.show();

        Ext.Ajax.request({
            url: "/admin/extensionmanager/update/get-update-information",
            params: {
                id: this.updateRecord.get("id"),
                type: this.updateRecord.get("type")
            },
            success: this.showUpdateSummary.bind(this)
        });

    },

    showUpdateSummary: function (transport) {

        var data = Ext.decode(transport.responseText);
        this.stepAmount = data.steps.length;
        this.steps = data.steps;

        this.updateWindow.removeAll();

        if(this.stepAmount > 0) {
            var content = t("update_information");
            content += "<br /><br />";
            content += t("number_of_files") + ": " + data.fileAmount;

            this.updateWindow.add({
                bodyStyle: "padding: 20px;",
                html: content,
                buttons: [{
                    text: t("next"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {

                        this.updateWindow.removeAll();

                        this.progressBar = new Ext.ProgressBar({
                            text: t('initializing'),
                            style: "margin: 10px;"
                        });
                        this.updateWindow.add({
                            style: "margin: 30px 10px 0 10px",
                            bodyStyle: "padding: 10px;",
                            html: t("please_wait")
                        });
                        this.updateWindow.add(this.progressBar);

                        this.updateWindow.doLayout();

                        this.updateMessages = [];


                        window.setTimeout(this.processStep.bind(this), 100);
                    }.bind(this)
                }]
            });
        } else {
            this.updateWindow.add({
                bodyStyle: "padding: 20px;",
                html: t("extension_is_up_to_date"),
                buttons: [{
                    text: t("close"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.updateWindow.close();
                    }.bind(this)
                }]
            });
        }

        this.updateWindow.doLayout();
    },

    processStep: function (definedJob) {

        var status = (1 - (this.steps.length / this.stepAmount));
        var percent = Math.ceil(status * 100);

        this.progressBar.updateProgress(status, percent + "%");

        if (this.steps.length > 0) {

            var nextJob;
            if (typeof definedJob == "object") {
                nextJob = definedJob;
            }
            else {
                nextJob = this.steps.shift();
            }

            Ext.Ajax.request({
                url: "/admin/extensionmanager/" + nextJob.controller + "/" + nextJob.action,
                params: nextJob.params,
                success: function (job, response) {
                    try {
                        var r = Ext.decode(response.responseText);

                        if (r.success) {
                            if(typeof r.message == "string") {
                                this.updateMessages.push(r.message);
                            }
                            window.setTimeout(this.processStep.bind(this), 100);
                            return;
                        }
                    }
                    catch (e) {
                        console.log(e);
                    }
                    this.error(job, response);

                }.bind(this, nextJob)
            });
        }
        else {

            this.updateWindow.removeAll();
            this.updateWindow.add({
                bodyStyle: "padding: 20px;",
                html: this.updateMessages.join("<br /><br />"),
                buttons: [{
                    text: t("close"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.updateWindow.close();
                    }.bind(this)
                }]
            });
            this.updateWindow.doLayout();
        }
    },

    error: function (job, response) {
        this.updateWindow.close();
        Ext.MessageBox.alert(t('error'), "Error: <br />" + response.responseText);
    }

});