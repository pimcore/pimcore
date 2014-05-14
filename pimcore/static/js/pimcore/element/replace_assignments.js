
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

            this.store = new Ext.data.JsonStore({
                autoDestroy: true,
                url: "/admin/element/find-usages",
                root: 'data',
                fields: ['id', 'type', 'path']
            });

            var selectionColumn = new Ext.grid.CheckboxSelectionModel();

            this.panel = new Ext.Panel({
                title: t("search_replace_assignments"),
                layout: "border",
                closable:true,
                items: [{
                    itemId: "form",
                    xtype: "form",
                    layout: "pimcoreform",
                    region: "north",
                    height: 70,
                    bodyStyle: "padding: 10px;",
                    labelWidth: 250,
                    items: [{
                        xtype: "hidden",
                        name: "type",
                        itemId: "type"
                    },{
                        xtype: "hidden",
                        name: "id",
                        itemId: "id"
                    },{
                        xtype: "textfield",
                        fieldLabel: t("search"),
                        cls: "input_drop_target",
                        name: "path",
                        itemId: "path",
                        itemCls: "pimcore_form_elements_float_left",
                        width: 400,
                        listeners: {
                            "render": function (el) {
                                new Ext.dd.DropZone(el.getEl(), {
                                    reference: this,
                                    ddGroup: "element",
                                    getTargetFromEvent: function(e) {
                                        return this.getEl();
                                    }.bind(el),

                                    onNodeOver : function(target, dd, e, data) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    },

                                    onNodeDrop : function (target, dd, e, data) {
                                        this.setValue(data.node.attributes.path);

                                        var type = data.node.attributes.elementType;
                                        var id = data.node.attributes.id;

                                        var form = this.findParentByType("form");
                                        form.getComponent("type").setValue(type);
                                        form.getComponent("id").setValue(id);
                                        return true;
                                    }.bind(el)
                                });
                            }
                        }
                    }, {
                        xtype: "button",
                        cls: "pimcore_form_elements_float_left",
                        iconCls: "pimcore_icon_search",
                        handler: function () {
                            pimcore.helpers.itemselector(false, function (selection) {
                                var form = this.panel.getComponent("form");
                                form.getComponent("type").setValue(selection.type);
                                form.getComponent("id").setValue(selection.id);
                                form.getComponent("path").setValue(selection.fullpath);
                            }.bind(this));
                        }.bind(this)
                    }, {
                        xtype: "button",
                        text: t("search"),
                        iconCls: "pimcore_icon_apply",
                        style: "padding-left: 20px;",
                        handler: this.search.bind(this)
                    }, {
                        xtype: "hidden",
                        name: "targetType",
                        itemId: "targetType"
                    },{
                        xtype: "hidden",
                        name: "targetId",
                        itemId: "targetId"
                    }, {
                        xtype: "textfield",
                        fieldLabel: t("replace") + " (" + t("optional") + ")",
                        cls: "input_drop_target",
                        name: "targetPath",
                        itemId: "targetPath",
                        itemCls: "pimcore_form_elements_clear_both pimcore_form_elements_float_left",
                        width: 400,
                        listeners: {
                            "render": function (el) {
                                new Ext.dd.DropZone(el.getEl(), {
                                    reference: this,
                                    ddGroup: "element",
                                    getTargetFromEvent: function(e) {
                                        return this.getEl();
                                    }.bind(el),

                                    onNodeOver : function(target, dd, e, data) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    },

                                    onNodeDrop : function (target, dd, e, data) {
                                        this.setValue(data.node.attributes.path);

                                        var type = data.node.attributes.elementType;
                                        var id = data.node.attributes.id;

                                        var form = this.findParentByType("form");
                                        form.getComponent("targetType").setValue(type);
                                        form.getComponent("targetId").setValue(id);
                                        return true;
                                    }.bind(el)
                                });
                            }
                        }
                    }, {
                        xtype: "button",
                        cls: "pimcore_form_elements_float_left",
                        iconCls: "pimcore_icon_search",
                        handler: function () {
                            pimcore.helpers.itemselector(false, function (selection) {
                                var form = this.panel.getComponent("form");
                                form.getComponent("targetType").setValue(selection.type);
                                form.getComponent("targetId").setValue(selection.id);
                                form.getComponent("targetPath").setValue(selection.fullpath);
                            }.bind(this));
                        }.bind(this)
                    }]
                }, {
                    title: t("results"),
                    region: "center",
                    itemId: "result",
                    xtype: "grid",
                    store: this.store,
                    sm: selectionColumn,
                    region: "center",
                    columns: [
                        selectionColumn,
                        {header: "ID", sortable: true, dataIndex: 'id', width: 60},
                        {header: t("type"), sortable: true, dataIndex: 'type', width: 60},
                        {header: t("path"), sortable: true, dataIndex: 'path', id:"path"}
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
                }]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate(this.panel);

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
        var selectedRows = this.panel.getComponent("result").getSelectionModel().getSelections();
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
                title: t("delete"),
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
