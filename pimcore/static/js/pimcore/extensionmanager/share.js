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

pimcore.registerNS("pimcore.extensionmanager.share");
pimcore.extensionmanager.share = Class.create({

    initialize: function () {

        pimcore.settings.liveconnect.login(this.getTabPanel.bind(this), function () {
            pimcore.globalmanager.remove("extensionmanager_share");
        });
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extensionmanager_share");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_extensionmanager_share",
                title: t("share_extension"),
                iconCls: "pimcore_icon_extensionmanager_share",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_extensionmanager_share");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extensionmanager_share");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getGrid: function () {

        this.store = new Ext.data.JsonStore({
            id: 'redirects_store',
            url: '/admin/extensionmanager/share/get-extensions',
            restful: false,
            root: "extensions",
            fields: ["id","type", "name", "description", "exists"]
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
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('share') + " / " + t("push_update"),
                    getClass: function (v, meta, rec) {
                        var class = "pimcore_action_column ";
                        if(rec.get("exists")) {
                            class += "pimcore_icon_update ";
                        } else {
                            class += "pimcore_icon_extensionmanager_share ";
                        }
                        return class;
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);

                        if(rec.get("exists")) {
                            this.openUpdateWindow(rec);
                        } else {
                            this.openShareWindow(rec);
                        }
                        
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

        if(!this.checkLiveConnect()) {
            return;
        }

        this.store.reload();
    },

    checkLiveConnect: function () {
        if(!pimcore.settings.liveconnect.isConnected()) {
            pimcore.settings.liveconnect.login();

            return false;
        }

        return true;
    },

    openUpdateWindow: function (rec) {

        if(!this.checkLiveConnect()) {
            return;
        }

        this.updateshareWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 200,
            items: [],
            listeners: {
                close: this.reload.bind(this)
            }
        });

        this.updateshareWindow.show();

        this.uploadPrepare(rec);
    },

    openShareWindow: function (rec) {

        if(!this.checkLiveConnect()) {
            return;
        }

        this.updateshareWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 200,
            items: [{
                bodyStyle: "padding:10px;",
                html: t("extensions_upload_agree_text"),
                buttons: [{
                    text: t("extension_upload_proceed"),
                    iconCls: "pimcore_icon_apply",
                    handler: this.uploadPrepare.bind(this, rec)
                }]
            }],
            listeners: {
                close: this.reload.bind(this)
            }
        });

        this.updateshareWindow.show();
    },

    uploadPrepare: function (rec) {

        this.currentExtension = rec;

        this.updateshareWindow.removeAll();
        this.updateshareWindow.add({
            bodyStyle: "padding:10px;",
            html: t("collecting_update_information")
        });

        this.updateshareWindow.doLayout();

        Ext.Ajax.request({
            url: "/admin/extensionmanager/share/get-update-information",
            params: {
                id: rec.get("id"),
                type: rec.get("type")
            },
            success: this.uploadStart.bind(this)
        });
    },

    uploadStart: function (transport) {

        this.updateshareWindow.removeAll();

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing'),
            style: "margin: 10px;"
        });
        this.updateshareWindow.add({
            style: "margin: 30px 10px 0 10px",
            bodyStyle: "padding: 10px;",
            html: t("please_wait")
        });
        this.updateshareWindow.add(this.progressBar);

        this.updateshareWindow.doLayout();


        var updateInfo = Ext.decode(transport.responseText);
        this.steps = updateInfo.steps;
        this.stepAmount = this.steps.length;

        window.setTimeout(this.processStep.bind(this), 500);
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
                url: "/admin/extensionmanager/share/" + nextJob.action,
                params: nextJob.params,
                success: function (job, response) {
                    var r = Ext.decode(response.responseText);

                    try {
                        if (r.success) {
                            this.lastResponse = r;
                            window.setTimeout(this.processStep.bind(this), 500);
                        }
                        else {
                            this.error(job);
                        }
                    }
                    catch (e) {
                        this.error(job);
                    }
                }.bind(this, nextJob)
            });
        }
        else {

            this.updateshareWindow.removeAll();
            this.updateshareWindow.add({
                bodyStyle: "padding: 20px;",
                html: "Your extension was successfully submitted to the extension repository. <br />Please click on the following link to complete the upload and add some additional information to your extension.<br /><br /><b><a href='http://www.pimcore.org/extensions/edit?token=" + pimcore.settings.liveconnect.getToken() + "&id=" + this.currentExtension.get("id") + "&finish=1' target='_blank'>Click here to proceed</a></b>"
                /*buttons: [{
                    text: t("close"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.updateshareWindow.close();
                    }.bind(this)
                }]*/
            });
            this.updateshareWindow.doLayout();

            this.currentExtension = null;
        }
    },

    error: function (job) {
        this.updateshareWindow.close();
        Ext.MessageBox.alert(t('error'), "Error");
    }
});