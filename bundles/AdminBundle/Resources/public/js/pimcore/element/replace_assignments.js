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

pimcore.registerNS("pimcore.element.replace_assignments");
pimcore.element.replace_assignments = Class.create({

    initialize: function () {
        this.getPanel();
    },

    getPanel: function () {

        if (!this.panel) {

            this.store = new Ext.data.Store({
                autoDestroy: true,
                remoteSort: true,
                pageSize: pimcore.helpers.grid.getDefaultPageSize(),
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_element_findusages'),
                    reader: {
                        type: 'json',
                        rootProperty: 'data',
                        totalProperty: 'total'
                    }
                },
                listeners: {
                    beforeload: function (store, operation, eOpts) {
                        let params = this.panel.getComponent("form").getForm().getFieldValues();
                        for (let key of Object.keys(params)) {
                            store.proxy.setExtraParam(key, params[key]);
                        }
                    }.bind(this),
                    load: function (store, records, success, operation) {
                        var responseText = operation.getResponse().responseText;
                        var response = Ext.decode(responseText);
                        this.requiredByNote.setHidden(!response.hasHidden);
                        this.panel.updateLayout();
                    }.bind(this)
                },
                fields: ['id', 'type', 'path']
            });

            var selectionColumn = Ext.create('Ext.selection.CheckboxModel', {});

            this.requiredByNote = new Ext.Panel({
                html: t('hidden_dependencies'),
                bodyCls: 'dependency-warning',
                hidden: true
            });

            this.panel = new Ext.Panel({
                    title: t("search_replace_assignments"),
                    layout: "border",
                    closable: true
                    ,
                    items: [
                        {
                            itemId: "form",
                            xtype: "form",
                            region: "north",
                            bodyStyle: "padding: 10px;",
                            items: [
                                {
                                    xtype: 'fieldcontainer',
                                    layout: 'hbox',
                                    defaults: {
                                        labelWidth: 250
                                    },
                                    items: [
                                        {
                                            xtype: "hidden",
                                            name: "type",
                                            itemId: "type"
                                        },
                                        {
                                            xtype: "hidden",
                                            name: "id",
                                            itemId: "id"
                                        },
                                        {
                                            xtype: "textfield",
                                            fieldLabel: t("search"),
                                            fieldCls: "input_drop_target",
                                            name: "path",
                                            itemId: "path",
                                            width: 650,
                                            renderer: Ext.util.Format.htmlEncode,
                                            listeners: {
                                                "render": function (el) {
                                                    new Ext.dd.DropZone(el.getEl(), {
                                                        reference: this,
                                                        ddGroup: "element",
                                                        getTargetFromEvent: function (e) {
                                                            return this.getEl();
                                                        }.bind(el),

                                                        onNodeOver: function (target, dd, e, data) {
                                                            if (data.records.length === 1) {
                                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                                            }
                                                        },

                                                        onNodeDrop: function (target, dd, e, data) {
                                                            if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                                return false;
                                                            }

                                                            data = data.records[0].data;
                                                            this.setValue(data.path);

                                                            var type = data.elementType;
                                                            var id = data.id;

                                                            var form = this.findParentByType("form");
                                                            form.queryById("type").setValue(type);
                                                            form.queryById("id").setValue(id);
                                                            return true;
                                                        }.bind(el)
                                                    });
                                                }
                                            }
                                        },
                                        {
                                            xtype: "button",
                                            iconCls: "pimcore_icon_search",
                                            style: "margin-left: 5px;",
                                            handler: function () {
                                                pimcore.helpers.itemselector(false, function (selection) {
                                                    var form = this.panel.getComponent("form");
                                                    form.queryById("type").setValue(selection.type);
                                                    form.queryById("id").setValue(selection.id);
                                                    form.queryById("path").setValue(selection.fullpath);
                                                }.bind(this));
                                            }.bind(this)
                                        },
                                        {
                                            xtype: "button",
                                            text: t("search"),
                                            iconCls: "pimcore_icon_apply",
                                            style: "margin-left: 20px;",
                                            handler: this.search.bind(this)
                                        }, {
                                            xtype: "hidden",
                                            name: "targetType",
                                            itemId: "targetType"
                                        }, {
                                            xtype: "hidden",
                                            name: "targetId",
                                            itemId: "targetId"
                                        }
                                    ]
                                },
                                {
                                    xtype: 'fieldcontainer',
                                    layout: 'hbox',
                                    defaults: {
                                        labelWidth: 250
                                    },
                                    items: [
                                        {
                                            xtype: "textfield",
                                            fieldLabel: t("replace") + " (" + t("optional") + ")",
                                            fieldCls: "input_drop_target",
                                            name: "targetPath",
                                            itemId: "targetPath",
                                            width: 650,
                                            listeners: {
                                                "render": function (el) {
                                                    new Ext.dd.DropZone(el.getEl(), {
                                                        reference: this,
                                                        ddGroup: "element",
                                                        getTargetFromEvent: function (e) {
                                                            return this.getEl();
                                                        }.bind(el),

                                                        onNodeOver: function (target, dd, e, data) {
                                                            if (data.records.length === 1) {
                                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                                            }
                                                        },

                                                        onNodeDrop: function (target, dd, e, data) {
                                                            if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                                return false;
                                                            }

                                                            data = data.records[0].data;

                                                            this.setValue(data.path);

                                                            var type = data.elementType;
                                                            var id = data.id;

                                                            var form = this.findParentByType("form");
                                                            form.queryById("targetType").setValue(type);
                                                            form.queryById("targetId").setValue(id);
                                                            return true;
                                                        }.bind(el)
                                                    });
                                                }
                                            }
                                        }
                                        ,
                                        {
                                            xtype: "button",
                                            iconCls: "pimcore_icon_search",
                                            style: "margin-left: 5px;",
                                            handler: function () {
                                                pimcore.helpers.itemselector(false, function (selection) {
                                                    var form = this.panel.getComponent("form");
                                                    form.queryById("targetType").setValue(selection.type);
                                                    form.queryById("targetId").setValue(selection.id);
                                                    form.queryById("targetPath").setValue(selection.fullpath);
                                                }.bind(this));
                                            }.bind(this)
                                        }
                                    ]
                                },
                                {
                                    xtype: 'fieldcontainer',
                                    layout: 'hbox',
                                    items: [
                                        this.requiredByNote
                                    ]
                                }
                            ]
                        }
                        ,
                        {
                            title: t("results"),
                            region: "center",
                            itemId: "result",
                            xtype: "grid",
                            store: this.store,
                            selModel: selectionColumn,
                            columns: [
                                //selectionColumn,
                                {text: "ID", sortable: true, dataIndex: 'id', width: 60},
                                {text: t("type"), sortable: true, dataIndex: 'type', width: 100},
                                {text: t("path"), sortable: true, dataIndex: 'path', id: "path", flex: 1}
                            ],
                            columnLines: true,
                            autoExpandColumn: "path",
                            stripeRows: true,
                            autoScroll: true,
                            buttons: [
                                {
                                    text: t("replace_assignments_in_selected_elements"),
                                    iconCls: "pimcore_icon_apply",
                                    handler: this.updateSelected.bind(this)
                                },
                                {
                                    text: t("replace_assignments_in_all_elements"),
                                    iconCls: "pimcore_icon_apply",
                                    handler: this.updateAll.bind(this)
                                }
                            ],
                            bbar: pimcore.helpers.grid.buildDefaultPagingToolbar(this.store)
                        }
                    ]
                }
            );

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveTab(this.panel);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    search: function () {

        var params = this.panel.getComponent("form").getForm().getFieldValues();
        this.store.load({
            params: params
        });
    },

    updateSelected: function () {

        var params = this.panel.getComponent("form").getForm().getFieldValues();
        params["sourceType"] = params["type"];
        params["sourceId"] = params["id"];

        // get selected elements
        var jobs = [];
        var selectedRows = this.panel.getComponent("result").getSelection();

        for (var i = 0; i < selectedRows.length; i++) {
            jobs.push({
                url: Routing.generate('pimcore_admin_element_replaceassignments'),
                method: 'POST',
                params: array_merge(params, {
                    id: selectedRows[i].get("id"),
                    type: selectedRows[i].get("type")
                })
            });
        }

        this.processUpdateJobs(jobs, params["targetId"]);
    },

    updateAll: function () {

        var params = this.panel.getComponent("form").getForm().getFieldValues();
        params["sourceType"] = params["type"];
        params["sourceId"] = params["id"];

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_element_getreplaceassignmentsbatchjobs'),
            params: params,
            success: function (params, response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success && rdata.jobs) {
                    var jobs = [];

                    for (var i = 0; i < rdata.jobs.length; i++) {
                        jobs.push({
                            url: "/admin/element/replace-assignments",
                            method: 'POST',
                            params: array_merge(params, {
                                id: rdata.jobs[i]["id"],
                                type: rdata.jobs[i]["type"]
                            })
                        });
                    }

                    this.processUpdateJobs(jobs, params["targetId"]);
                } else {
                    Ext.MessageBox.alert(t("error"), t("search_replace_assignments_error"));
                }

            }.bind(this, params)
        });
    },

    processUpdateJobs: function (jobs, targetId) {

        if (jobs.length && targetId) {
            this.progressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.progressBarWin = new Ext.Window({
                title: t("replace"),
                layout: 'fit',
                width: 200,
                bodyStyle: "padding: 10px;",
                closable: false,
                plain: true,
                items: [this.progressBar],
                listeners: pimcore.helpers.getProgressWindowListeners()
            });

            this.progressBarWin.show();


            var pj = new pimcore.tool.paralleljobs({
                success: function () {

                    if (this.progressBarWin) {
                        this.progressBarWin.close();
                    }

                    this.progressBar = null;
                    this.progressBarWin = null;

                    if (typeof callback == "function") {
                        callback();
                    }
                }.bind(this),
                update: function (currentStep, steps, percent) {
                    if (this.progressBar) {
                        var status = currentStep / steps;
                        this.progressBar.updateProgress(status, percent + "%");
                    }
                }.bind(this),
                failure: function (message) {
                    this.progressBarWin.close();
                    pimcore.helpers.showNotification(t("error"), "", "error", t(message));
                }.bind(this),
                jobs: [jobs]
            });
        } else {
            Ext.MessageBox.alert(t("error"), t("search_replace_assignments_error"));
        }
    }

});
