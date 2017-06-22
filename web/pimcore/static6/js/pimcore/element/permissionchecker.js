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

pimcore.registerNS("pimcore.element.permissionchecker");
pimcore.element.permissionchecker = Class.create({


    initialize: function () {

    },

    show: function () {
        if (!this.window) {
            this.elementId = null;
            this.elementType = null;
            this.userId = null;

            this.resultPanel = new Ext.Panel({
                title: t("permissions"),
                layout: 'fit',
                width: "100%"
            });

            var fieldPath = new Ext.form.TextField({
                fieldLabel: t('path'),
                name: "path",
                width: 520,
                fieldCls: "pimcore_droptarget_input"
            });

            fieldPath.on("render", function (el) {
                // add drop zone
                new Ext.dd.DropZone(el.getEl(), {
                    reference: this,
                    ddGroup: "element",
                    getTargetFromEvent: function (e) {
                        return fieldPath.getEl();
                    },

                    onNodeOver: function (target, dd, e, data) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }.bind(this),

                    onNodeDrop: function (target, dd, e, data) {
                        var record = data.records[0];
                        if (record.data.elementType == "asset" || record.data.elementType == "document" || record.data.elementType == "object") {
                            fieldPath.setValue(record.data.path);
                            this.elementType = record.data.elementType;
                            this.elementId = record.data.id;
                            this.analyzeButton.enable();
                            return true;
                        }
                        return false;
                    }.bind(this)
                });
            }.bind(this));

            var user = new Ext.form.TextField({
                fieldLabel: t('user'),
                name: "user",
                width: 520,
                fieldCls: "pimcore_droptarget_input"
            });

            user.on("render", function (el) {
                // add drop zone
                new Ext.dd.DropZone(el.getEl(), {
                    reference: this,
                    ddGroup: "users",
                    getTargetFromEvent: function (e) {
                        return fieldPath.getEl();
                    },

                    onNodeOver: function (target, dd, e, data) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }.bind(this),

                    onNodeDrop: function (target, dd, e, data) {
                        var record = data.records[0];
                        if (record.data.elementType == "user") {
                            user.setValue(record.data.text);
                            this.userId = record.data.id;
                            return true;
                        }
                        return false;
                    }.bind(this)
                });
            }.bind(this));

            this.analyzeButton = new Ext.Button(
                {
                    xtype: 'button',
                    text: t("analyze"),
                    iconCls: "pimcore_icon_search",
                    disabled: true,
                    style: "margin-left: 5px",
                    listeners: {
                        "click": function () {
                            Ext.Ajax.request({
                                url: "/admin/element/analyze-permissions",
                                method: "post",
                                params: {
                                    userId: this.userId,
                                    elementType: this.elementType,
                                    elementId: this.elementId
                                },
                                success: function (response) {
                                    try {
                                        var rdata = Ext.decode(response.responseText);
                                        if (rdata && rdata.success) {
                                            this.showResult(rdata.data);

                                        }
                                    } catch (e) {
                                        console.log(e);
                                    }
                                }.bind(this)
                            });

                        }.bind(this)
                    }
                }
            );


            var form = new Ext.FormPanel({
                deferredRender: false,
                defaults: {autoHeight: true, bodyStyle: 'padding:10px'},
                items: [
                    {
                        layout: 'vbox',
                        border: false,
                        defaultType: 'textfield',
                        items: [
                            {
                                xtype: "fieldcontainer",
                                layout: 'hbox',
                                border: false,
                                items: [fieldPath, {
                                    xtype: "button",
                                    iconCls: "pimcore_icon_delete",
                                    style: "margin-left: 5px",
                                    handler: function () {
                                        fieldPath.setValue("");
                                        this.elementType = null;
                                        this.elementId = null;
                                        this.analyzeButton.disable();
                                        return true;
                                    }.bind(this)
                                },
                                    this.analyzeButton
                                ]
                            },
                            {
                                xtype: "fieldcontainer",
                                layout: 'hbox',
                                border: false,
                                items: [user, {
                                    xtype: "button",
                                    iconCls: "pimcore_icon_delete",
                                    style: "margin-left: 5px",
                                    handler: function () {
                                        user.setValue("");
                                        this.userId = null;
                                        return true;
                                    }.bind(this)
                                }]
                            },
                            this.resultPanel
                        ]
                    }
                ]
            });

            this.window = new Ext.Window({
                modal: false,
                width: 1000,
                height: 800,
                title: t("analyze_permissions"),
                items: [form],
                autoScroll: true,
                buttons: [
                    {
                        text: t("close"),
                        iconCls: "pimcore_icon_delete",
                        listeners: {
                            "click": function () {
                                this.window.close();
                            }.bind(this)
                        }
                    }
                ]
            });
        }

        this.window.show();

        return window;
    },

    showResult: function (data) {
        this.store = new Ext.data.Store({
            proxy: {
                type: 'memory'
            },
            autoDestroy: true,
            sortInfo: {
                field: 'key',
                direction: 'ASC'

            },
            data: data.permissions,
            fields: data.columns
        });

        var detailsStore = new Ext.data.Store({
            proxy: {
                type: 'memory'
            },
            autoDestroy: true,
            sortInfo: {
                field: 'key',
                direction: 'ASC'

            },
            data: data.details,
            fields: ["userId", "name", "description"]
        });

        this.resultPanel.removeAll();

        var columns = [
            {header: t("name"), sortable: false, dataIndex: "userName", editable: false, flex: 150, filter: 'string'}
        ];

        var columnCount = data.columns.length;
        for (var i = 0; i < columnCount; i++) {
            var columnName = data.columns[i];
            if (columnName == "details" || columnName == "lView" || columnName == "lEdit") {
                continue;
            }

            var columnConfig = {
                header: t(columnName),
                sortable: false,
                dataIndex: columnName,
                editable: false,
                flex: 150,
                filter: 'string',
                renderer: function (columnName, value, metaData, record) {
                    if (value === true) {
                        metaData.tdCls += " pimcore_icon_success";
                    } else {
                        metaData.tdCls += " pimcore_icon_delete";
                    }
                }.bind(this, columnName)
            };
            columns.push(columnConfig);
        }

        var subtable = new Ext.ux.grid.SubTable({
            headerWidth: 32,
            columns: [{
                dataIndex: 'a',
                flex: 120
            },
                {
                    flex: 120,
                    dataIndex: 'b',
                    renderer: function (value, metaData, record) {
                        if (value === true) {
                            return '<div class="pimcore_icon_success" style="min-width: 50px;">&nbsp;</div>';
                        } else if (value === false) {
                            return '<div class="pimcore_icon_delete" style="min-width: 50px;">&nbsp;</div>';
                        }
                    }.bind(this)
                },
                {
                    width: 30,
                    dataIndex: 'c',
                    renderer: function (value, metaData, record) {
                        if (record.data.c == "user") {
                            return '<div class="pimcore_icon_user" style="min-width: 50px;">&nbsp;</div>';
                        } else if (record.data.c == "role") {
                            return '<div class="pimcore_icon_roles" style="min-width: 50px;">&nbsp;</div>';
                        }
                    }.bind(this)
                },
                {
                    flex: 120,
                    dataIndex: 'd'
                },
                {
                    flex: 120,
                    dataIndex: 'e'
                },
                {
                    flex: 120,
                    dataIndex: 'f'
                }
            ],
            getAssociatedRecords: function (record) {
                var result = Ext.Array.filter(
                    detailsStore.data.items,

                    function (r) {
                        return r.get('userId') == record.get('userId');
                    });
                return result;
            }.bind(this)
        });


        this.grid = new Ext.grid.GridPanel({
            store: this.store,
            scrollable: true,
            plugins: ['gridfilters', subtable
            ],
            columns: columns
            ,
            viewConfig: {
                forceFit: true
            }
        });

        this.resultPanel.add(this.grid);
        this.window.updateLayout();
    }

});