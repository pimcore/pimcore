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

pimcore.registerNS("pimcore.settings.translation.xliff");
pimcore.settings.translation.xliff = Class.create({

    initialize: function () {

        this.getTabPanel();

    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_xliff");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_xliff",
                title: "XLIFF " + t("export") + "/" + t("import"),
                iconCls: "pimcore_icon_translations",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getExportPanel(), this.getImportPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_xliff");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("xliff");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getExportPanel: function () {

        this.exportStore = new Ext.data.ArrayStore({
            fields: [
                "id",
                "path",
                "type",
                "children"
            ]
        });

        this.component = new Ext.grid.GridPanel({
            store: this.exportStore,
            autoHeight: true,
            style: "margin-bottom: 10px",
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: [
                    {header: 'ID', dataIndex: 'id', width: 50},
                    {id: "path", header: t("path"), dataIndex: 'path', width: 200},
                    {header: t("type"), dataIndex: 'type', width: 100},
                    new Ext.grid.CheckColumn({
                        header: t("children"),
                        dataIndex: "children",
                        width: 50
                    }),
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('remove'),
                            icon: "/pimcore/static/img/icon/cross.png",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }]
                    }
                ]
            }),
            autoExpandColumn: 'path',
            tbar: [
                {
                    xtype: "tbspacer",
                    width: 20,
                    height: 16,
                    cls: "pimcore_icon_droptarget"
                },
                t("elements_to_export"),
                "->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: function () {
                        this.exportStore.removeAll();
                    }.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: function () {
                        pimcore.helpers.itemselector(true, function (items) {
                            if (items.length > 0) {
                                for (var i = 0; i < items.length; i++) {
                                    this.exportStore.add(new this.exportStore.recordType({
                                        id: items[i].id,
                                        path: items[i].fullpath,
                                        type: items[i].type,
                                        children: true
                                    }, this.exportStore.getCount() + 1));
                                }
                            }
                        }.bind(this), {
                            type: ["object", "document"]
                        });
                    }.bind(this)
                }
            ]
        });

        this.component.on("rowcontextmenu", this.onRowContextmenu);

        this.component.on("afterrender", function () {

            var dropTargetEl = this.component.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'element',
                getTargetFromEvent: function(e) {
                    return this.component.getEl().dom;
                    //return e.getTarget(this.grid.getView().rowSelector);
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {

                    var type = data.node.attributes.elementType;

                    if (type == "document" || type == "object") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                    else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }


                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {

                    var type = data.node.attributes.elementType;
                    if (type == "document" || type == "object") {
                        this.exportStore.add(new this.exportStore.recordType({
                            id: data.node.attributes.id,
                            path: data.node.attributes.path,
                            type: data.node.attributes.elementType,
                            children: true
                        }, this.exportStore.getCount() + 1));
                        return true;
                    }
                    return false;
                }.bind(this)
            });
        }.bind(this));

        var languagestore = [];
        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {
            languagestore.push([pimcore.settings.websiteLanguages[i],pimcore.settings.websiteLanguages[i]]);
        }

        this.exportSourceLanguageSelector = new Ext.form.ComboBox({
            fieldLabel: t("source"),
            name: "source",
            store: languagestore,
            editable: false,
            triggerAction: 'all',
            mode: "local",
            listWidth: 200
        });

        this.exportTargetLanguageSelector = new Ext.form.ComboBox({
            fieldLabel: t("target"),
            name: "target",
            store: languagestore,
            editable: false,
            triggerAction: 'all',
            mode: "local",
            listWidth: 200
        });

        this.exportPanel = new Ext.Panel({
            title: t("export"),
            autoScroll: true,
            region: "center",
            bodyStyle: "padding: 10px",
            items: [{
                html: '<div style="font: 12px tahoma,arial,helvetica; padding: 10px;">' + t("xliff_export_notice") + '</div>',
                style: "margin-bottom: 10px"
            }, {
                title: t("important_notice") + " (" + t("documents") + ")",
                html: '<div style="font: 12px tahoma,arial,helvetica; padding: 10px;">' + t("xliff_export_documents") + '</div>',
                style: "margin-bottom: 10px",
                iconCls: "pimcore_icon_document"
            }, {
                title: t("important_notice") + " (" + t("objects") + ")",
                html: '<div style="font: 12px tahoma,arial,helvetica; padding: 10px;">' + t("xliff_export_objects") + '</div>',
                style: "margin-bottom: 10px",
                iconCls: "pimcore_icon_object"
            }, this.component, {
                xtype: "form",
                title: t("language"),
                bodyStyle: "padding: 10px",
                items: [this.exportSourceLanguageSelector, this.exportTargetLanguageSelector],
                style: "margin-bottom: 10px"
            }],
            buttons: [{
                text: t("export"),
                iconCls: "pimcore_icon_export",
                handler: this.startExport.bind(this)
            }]
        });

        return this.exportPanel;
    },

    startExport: function () {
        var tmData = [];

        var data = this.exportStore.queryBy(function(record, id) {
            return true;
        });

        // skip if no items are selected to export
        if(data.items.length < 1) {
            return;
        }

        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }

        Ext.Ajax.request({
            url: "/admin/translation/content-export-jobs",
            params: {
                source: this.exportSourceLanguageSelector.getValue(),
                target: this.exportTargetLanguageSelector.getValue(),
                data: Ext.encode(tmData),
                type: "xliff"
            },
            success: function(response) {
                var res = Ext.decode(response.responseText);

                this.exportProgressbar = new Ext.ProgressBar({
                    text: t('initializing')
                });

                this.exportProgressWin = new Ext.Window({
                    title: t("export"),
                    layout:'fit',
                    width:500,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    modal: true,
                    items: [this.exportProgressbar]
                });

                this.exportProgressWin.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function (id) {
                        if(this.exportProgressWin) {
                            this.exportProgressWin.close();
                        }

                        this.exportProgressbar = null;
                        this.exportProgressWin = null;

                        pimcore.helpers.download('/admin/translation/xliff-export-download/?id='+ id);
                    }.bind(this, res.id),
                    update: function (currentStep, steps, percent) {
                        if(this.exportProgressbar) {
                            var status = currentStep / steps;
                            this.exportProgressbar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        this.exportProgressWin.close();
                        pimcore.helpers.showNotification(t("error"), t("error"),
                            "error", t(message));
                    }.bind(this),
                    jobs: res.jobs
                });
            }.bind(this)
        });
    },

    getImportPanel: function () {
        this.importPanel = new Ext.Panel({
            title: t("import"),
            region: "east",
            width: 300,
            html: '<div style="font: 12px tahoma,arial,helvetica; padding: 10px;">' + t("xliff_import_notice") + '</div>',
            buttons: [{
                text: t("select_a_file") + " (.xlf / .xliff)",
                iconCls: "pimcore_icon_newfile",
                handler: function () {
                    pimcore.helpers.uploadDialog('/admin/translation/xliff-import-upload', "file", function(res) {

                        var res = Ext.decode(res["response"]["responseText"]);

                        this.importProgressbar = new Ext.ProgressBar({
                            text: t('initializing')
                        });

                        this.importProgressWin = new Ext.Window({
                            title: t("import"),
                            layout:'fit',
                            width:500,
                            bodyStyle: "padding: 10px;",
                            closable:false,
                            plain: true,
                            modal: true,
                            items: [this.importProgressbar]
                        });

                        this.importProgressWin.show();


                        var pj = new pimcore.tool.paralleljobs({
                            success: function (id) {
                                if(this.importProgressWin) {
                                    this.importProgressWin.close();
                                }

                                this.importProgressbar = null;
                                this.importProgressWin = null;
                            }.bind(this, res.id),
                            update: function (currentStep, steps, percent) {
                                if(this.importProgressbar) {
                                    var status = currentStep / steps;
                                    this.importProgressbar.updateProgress(status, percent + "%");
                                }
                            }.bind(this),
                            failure: function (message) {
                                this.importProgressWin.close();
                                pimcore.helpers.showNotification(t("error"), t("error"),
                                    "error", t(message));
                            }.bind(this),
                            jobs: res.jobs
                        });

                    }.bind(this), function () {
                        Ext.MessageBox.alert(t("error"), t("error"));
                    });
                }.bind(this)
            }]
        });

        return this.importPanel;
    }
});