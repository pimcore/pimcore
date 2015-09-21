
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

pimcore.registerNS("pimcore.element.replace_assignments");
pimcore.element.replace_assignments = Class.create({

    initialize: function () {
        this.getPanel();
    },

    getPanel: function () {

        if (!this.panel) {

            this.store = new Ext.data.Store({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/element/find-usages",
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                fields: ['id', 'type', 'path']
            });

            var selectionColumn = Ext.create('Ext.selection.CheckboxModel', {});

            this.panel = new Ext.Panel({
                title: t("search_replace_assignments"),
                layout: "border",
                closable:true
                    ,
                items: [
                    {
                        itemId: "form",
                        xtype: "form",
                        region: "north",
                        height: 110,
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
                                        listeners: {
                                            "render": function (el) {
                                                new Ext.dd.DropZone(el.getEl(), {
                                                    reference: this,
                                                    ddGroup: "element",
                                                    getTargetFromEvent: function (e) {
                                                        return this.getEl();
                                                    }.bind(el),

                                                    onNodeOver: function (target, dd, e, data) {
                                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                                    },

                                                    onNodeDrop: function (target, dd, e, data) {
                                                        var record = data.records[0];

                                                        this.setValue(record.data.path);

                                                        var type = record.data.elementType;
                                                        var id = record.data.id;

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
                                    },{
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
                                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                                    },

                                                    onNodeDrop: function (target, dd, e, data) {
                                                        var record = data.records[0];
                                                        this.setValue(record.data.path);

                                                        var type = record.data.elementType;
                                                        var id = record.data.id;

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
                        {header: "ID", sortable: true, dataIndex: 'id', width: 60},
                        {header: t("type"), sortable: true, dataIndex: 'type', width: 60},
                        {header: t("path"), sortable: true, dataIndex: 'path', id:"path", flex: 1}
                    ],
                    columnLines: true,
                    autoExpandColumn: "path",
                    stripeRows: true,
                    autoScroll: true,
                    buttons: [{
                        text: t("replace_assignments_in_selected_elements"),
                        iconCls: "pimcore_icon_apply",
                        handler: this.update.bind(this)
                    }]
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

    update: function () {

        var params = this.panel.getComponent("form").getForm().getFieldValues();
        params["sourceType"] = params["type"];
        params["sourceId"] = params["id"];


        // get selected elements
        var jobs = [];
        var selectedRows = this.panel.getComponent("result").getSelection();
        for (var i=0; i<selectedRows.length; i++) {
            jobs.push({
                url: "/admin/element/replace-assignments",
                params: array_merge(params, {
                    id: selectedRows[i].get("id"),
                    type: selectedRows[i].get("type")
                })
            });
        }

        if(jobs.length && params["targetId"]) {
            this.progressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.progressBarWin = new Ext.Window({
                title: t("replacing"),
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.progressBar]
            });

            this.progressBarWin.show();


            var pj = new pimcore.tool.paralleljobs({
                success: function () {

                    if(this.progressBarWin) {
                        this.progressBarWin.close();
                    }

                    this.progressBar = null;
                    this.progressBarWin = null;

                    if(typeof callback == "function") {
                        callback();
                    }
                }.bind(this),
                update: function (currentStep, steps, percent) {
                    if(this.progressBar) {
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
