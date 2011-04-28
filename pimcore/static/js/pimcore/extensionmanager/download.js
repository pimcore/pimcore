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

pimcore.registerNS("pimcore.extensionmanager.download");
pimcore.extensionmanager.download = Class.create({

    initialize: function () {

        pimcore.settings.liveconnect.login(this.getTabPanel.bind(this), function () {
            pimcore.globalmanager.remove("extensionmanager_download");
        });
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extensionmanager_download");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_extensionmanager_download",
                title: t("download_extension"),
                iconCls: "pimcore_icon_extensionmanager_download",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_extensionmanager_download");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("extensionmanager_download");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getGrid: function () {

        this.store = new Ext.data.JsonStore({
            id: 'redirects_store',
            url: '/admin/extensionmanager/download/get-extensions',
            restful: false,
            root: "extensions",
            fields: ["id","type", "name", "description", "detailurl"]
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
                header: t('details'),
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('details'),
                    getClass: function (v, meta, rec) {
                        return "pimcore_action_column pimcore_icon_layout_region";
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        window.open(rec.get("detailurl"));

                    }.bind(this)
                }]
            },
            {
                header: t('download'),
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('download'),
                    getClass: function (v, meta, rec) {
                        return "pimcore_action_column pimcore_icon_download";
                    },
                    handler: function (grid, rowIndex) {

                        var rec = grid.getStore().getAt(rowIndex);
                        this.openDownloadWindow(rec);


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
            tbar: [{
                text: t("refresh"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            }],
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

    openDownloadWindow: function (rec) {

        if(!this.checkLiveConnect()) {
            return;
        }

        this.downloadWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 200,
            items: [],
            listeners: {
                close: this.reload.bind(this)
            }
        });

        this.downloadWindow.show();

        this.downloadPrepare(rec);
    },

    downloadPrepare: function (rec) {

        this.downloadWindow.removeAll();
        this.downloadWindow.add({
            bodyStyle: "padding:10px;",
            html: t("collecting_download_information")
        });

        this.downloadWindow.doLayout();

        Ext.Ajax.request({
            url: "/admin/extensionmanager/download/get-download-information",
            params: {
                id: rec.get("id"),
                type: rec.get("type")
            },
            success: this.downloadStart.bind(this)
        });
    },

    downloadStart: function (transport) {

        this.downloadWindow.removeAll();

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing'),
            style: "margin: 10px;"
        });
        this.downloadWindow.add({
            style: "margin: 30px 10px 0 10px",
            bodyStyle: "padding: 10px;",
            html: t("please_wait")
        });
        this.downloadWindow.add(this.progressBar);

        this.downloadWindow.doLayout();


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
                url: "/admin/extensionmanager/download/" + nextJob.action,
                params: nextJob.params,
                success: function (job, response) {
                    var r = Ext.decode(response.responseText);

                    try {
                        if (r.success) {
                            this.lastResponse = r;
                            window.setTimeout(this.processStep.bind(this), 500);
                        }
                        else {
                            this.error(job, response);
                        }
                    }
                    catch (e) {
                        this.error(job, response);
                    }
                }.bind(this, nextJob)
            });
        }
        else {

            this.downloadWindow.removeAll();
            this.downloadWindow.add({
                bodyStyle: "padding: 20px;",
                html: "Download was successful!<br />Now you can enable/install your extension in the Extension-Manager",
                buttons: [{
                    text: t("close"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.downloadWindow.close();
                    }.bind(this)
                }]
            });
            this.downloadWindow.doLayout();
        }
    },

    error: function (job, response) {
        this.downloadWindow.close();
        Ext.MessageBox.alert(t('error'), "Error: <br />" + response.responseText);
    }
});