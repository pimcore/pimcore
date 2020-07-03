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

pimcore.registerNS("pimcore.settings.translation.xliff");
pimcore.settings.translation.xliff = Class.create({

    initialize: function () {

        this.getTabPanel();

    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_xliff");
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
            tabPanel.setActiveItem("pimcore_xliff");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("xliff");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getExportPanel: function () {

        let fields = [
            "rowId",
            "id",
            "path",
            "type",
            "children",
            "relations"
        ];

        let modelName = 'pimcore.model.xliff.store';
        if (!Ext.ClassManager.isCreated(modelName)) {
            Ext.define(modelName, {
                extend: 'Ext.data.Model',
                idProperty: "rowId",
                fields: fields
            });
        }

        this.exportStore = new Ext.data.ArrayStore({
            model: modelName
        });

        this.component = Ext.create('Ext.grid.Panel', {
            store: this.exportStore,
            autoHeight: true,
            border: true,
            style: "margin-bottom: 10px",
            selModel: Ext.create('Ext.selection.RowModel', {}),
            columns: {
                defaults: {
                    sortable: false
                },
                items: [
                    {text: 'ID', dataIndex: 'id', width: 50},
                    {text: t("type"), dataIndex: 'type', width: 100},
                    {text: t("path"), dataIndex: 'path', flex: 200},
                    Ext.create('Ext.grid.column.Check', {
                        text: t("children"),
                        dataIndex: "children",
                        width: 70
                    }),
                    Ext.create('Ext.grid.column.Check', {
                        text: t("relations"),
                        dataIndex: "relations",
                        width: 100
                    }),
                    {
                        xtype: 'actioncolumn',
                        menuText: t('remove'),
                        width: 30,
                        items: [{
                            tooltip: t('remove'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }]
                    }
                ]
            },
            tbar: {
                items: [
                    {
                        xtype: "tbspacer",
                        width: 24,
                        height: 24,
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
                    }
                    ,
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_search"
                        ,
                        handler: function () {
                            pimcore.helpers.itemselector(true, function (items) {
                                if (items.length > 0) {
                                    for (var i = 0; i < items.length; i++) {
                                        let rowId = items[i].type + '-' + items[i].id;
                                        this.exportStore.add({
                                            rowId: rowId,
                                            id: items[i].id,
                                            path: items[i].fullpath,
                                            type: items[i].type,
                                            children: true,
                                            relations: false
                                        });
                                    }
                                }
                            }.bind(this), {
                                type: ["object", "document"]
                            });
                        }.bind(this)
                    }
                ]
            }
        });

        //this.component.on("rowcontextmenu", this.onRowContextmenu);

        this.component.on("afterrender", function () {

            var dropTargetEl = this.component.getEl();
            var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                ddGroup    : 'element',
                getTargetFromEvent: function(e) {
                    return this.component.getEl().dom;
                    //return e.getTarget(this.grid.getView().rowSelector);
                }.bind(this),
                onNodeOver: function (overHtmlNode, ddSource, e, data) {
                    if (data.records.length == 1) {
                        var record = data.records[0];

                        var type = record.data.elementType;

                        if (type == "document" || type == "object") {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }
                    }
                    return Ext.dd.DropZone.prototype.dropNotAllowed;

                }.bind(this),
                onNodeDrop : function(target, dd, e, data) {
                    if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                        data = data.records[0].data;

                        var type = data.elementType;
                        if (type == "document" || type == "object") {
                            let rowId = type + '-' + data.id;
                            this.exportStore.add({
                                rowId: rowId,
                                id: data.id,
                                path: data.path,
                                type: data.elementType,
                                children: true,
                                relations: false
                            });
                            return true;
                        }
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
                html: '<div>' + t("xliff_export_notice") + '</div>',
                style: "margin-bottom: 10px"
            }, {
                title: t("important_notice") + " (" + t("documents") + ")",
                bodyStyle: 'padding-top:10px;',
                html: '<div>' + t("xliff_export_documents") + '</div>',
                style: "margin-bottom: 10px",
                iconCls: "pimcore_icon_document"
            }, {
                title: t("important_notice") + " (" + t("data_objects") + ")",
                bodyStyle: 'padding-top:10px;',
                html: '<div>' + t("xliff_export_objects") + '</div>',
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
            url: Routing.generate('pimcore_admin_translation_contentexportjobs'),
            method: 'POST',
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
                    width:200,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    items: [this.exportProgressbar],
                    listeners: pimcore.helpers.getProgressWindowListeners()
                });

                this.exportProgressWin.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function (id) {
                        if(this.exportProgressWin) {
                            this.exportProgressWin.close();
                        }

                        this.exportProgressbar = null;
                        this.exportProgressWin = null;

                        pimcore.helpers.download(Routing.generate('pimcore_admin_translation_xliffexportdownload', {id: id}));
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
                iconCls: "pimcore_icon_file pimcore_icon_overlay_add",
                handler: function () {
                    pimcore.helpers.uploadDialog(Routing.generate('pimcore_admin_translation_xliffimportupload'), "file", function(res) {

                        var res = Ext.decode(res["response"]["responseText"]);

                        this.importProgressbar = new Ext.ProgressBar({
                            text: t('initializing')
                        });

                        this.importProgressWin = new Ext.Window({
                            title: t("import"),
                            layout:'fit',
                            width:200,
                            bodyStyle: "padding: 10px;",
                            closable:false,
                            plain: true,
                            items: [this.importProgressbar],
                            listeners: pimcore.helpers.getProgressWindowListeners()
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
