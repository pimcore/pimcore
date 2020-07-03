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

pimcore.registerNS("pimcore.settings.document.doctypes");
pimcore.settings.document.doctypes = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_document_types");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_document_types",
                title: t("document_types"),
                iconCls: "pimcore_icon_doctypes",
                border: false,
                layout: "fit",
                closable: true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_document_types");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("document_types");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        this.store = pimcore.globalmanager.get("document_types_store");

        var typesColumns = [
            {
                text: t("name"),
                flex: 100,
                sortable: true,
                dataIndex: 'name',
                editor: new Ext.form.TextField({})
            },
            {
                text: t("group"),
                flex: 100,
                sortable: true,
                dataIndex: 'group',
                editor: new Ext.form.TextField({})
            },
            {
                text: t('bundle') + "(" + t('optional') + ")",
                flex: 50,
                sortable: true,
                dataIndex: 'module',
                editor: new Ext.form.ComboBox({
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: Routing.generate('pimcore_admin_misc_getavailablemodules'),
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    triggerAction: "all",
                    displayField: 'name'
                })
            },
            {
                text: t("controller"),
                flex: 50,
                sortable: true,
                dataIndex: 'controller',
                editor: new Ext.form.ComboBox({
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: Routing.generate('pimcore_admin_misc_getavailablecontrollers'),
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    queryMode: 'local',
                    triggerAction: "all",
                    displayField: 'name',
                    valueField: 'name',
                    matchFieldWidth: false,
                    listConfig: {
                        maxWidth: 400
                    },
                    listeners: {
                        "focus": function (el) {
                            var currentRecord = this.grid.getSelection();
                            el.getStore().reload({
                                params: {
                                    moduleName: currentRecord[0].data.module
                                },
                                callback: function () {
                                    el.expand();
                                }
                            });
                        }.bind(this)
                    }
                })
            },
            {
                text: t("action"),
                flex: 50,
                sortable: true,
                dataIndex: 'action',
                editor: new Ext.form.ComboBox({
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: Routing.generate('pimcore_admin_misc_getavailableactions'),
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    queryMode: 'local',
                    triggerAction: "all",
                    displayField: 'name',
                    valueField: 'name',
                    matchFieldWidth: false,
                    listConfig: {
                        maxWidth: 400
                    },
                    listeners: {
                        "focus": function (el) {
                            var currentRecord = this.grid.getSelection();
                            el.getStore().reload({
                                params: {
                                    controllerName: currentRecord[0].data.controller,
                                    moduleName: currentRecord[0].data.module
                                },
                                callback: function () {
                                    el.expand();
                                }
                            });
                        }.bind(this)
                    }
                })
            },
            {
                text: t("template"),
                flex: 50,
                sortable: true,
                dataIndex: 'template',
                editor: new Ext.form.ComboBox({
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        proxy: {
                            type: 'ajax',
                            url: Routing.generate('pimcore_admin_misc_getavailabletemplates'),
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["path"]
                    }),
                    queryMode: 'local',
                    triggerAction: "all",
                    displayField: 'path',
                    valueField: 'path',
                    matchFieldWidth: false,
                    listConfig: {
                        maxWidth: 400
                    },
                    listeners: {
                        "focus": function (el) {
                            el.getStore().reload({
                                callback: function () {
                                    el.expand();
                                }
                            });
                        }.bind(this)
                    }
                })
            },
            {
                text: t("type"),
                flex: 50,
                sortable: true,
                dataIndex: 'type',
                editor: new Ext.form.ComboBox({
                    triggerAction: 'all',
                    editable: false,
                    store: ["page", "snippet", "email", "newsletter", "printpage", "printcontainer"]
                })
            },
            {
                text: t("priority"),
                flex: 50,
                sortable: true,
                dataIndex: 'priority',
                editor: new Ext.form.ComboBox({
                    store: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                    mode: "local",
                    editable: false,
                    triggerAction: "all"
                })
            },
            {
                text: t("creationDate"),
                sortable: true,
                dataIndex: 'creationDate',
                editable: false,
                width: 130,
                hidden: true,
                renderer: function (d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {
                text: t("modificationDate"),
                sortable: true,
                dataIndex: 'modificationDate',
                editable: false,
                width: 130,
                hidden: true,
                renderer: function (d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {
                xtype: 'actioncolumn',
                menuText: t('delete'),
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            }, {
                xtype: 'actioncolumn',
                menuText: t('translate'),
                width: 30,
                items: [{
                    tooltip: t('translate'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/collaboration.svg",
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                        try {
                            pimcore.globalmanager.get("translationadminmanager").activate(rec.data.name);
                        }
                        catch (e) {
                            pimcore.globalmanager.add("translationadminmanager",
                                new pimcore.settings.translation.admin(rec.data.name));
                        }
                    }.bind(this)
                }]
            }
        ];


        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            bodyCls: "pimcore_editable_grid",
            store: this.store,
            columns: {
                items: typesColumns,
                defaults: {
                    renderer: Ext.util.Format.htmlEncode
                },
            },
            columnLines: true,
            trackMouseOver: true,
            stripeRows: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [
                    {
                        text: t('add'),
                        handler: this.onAdd.bind(this),
                        iconCls: "pimcore_icon_add"
                    }
                ]
            },
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0, {
            name: t('new_document_type'),
            type: "page"
        });
    }
});
