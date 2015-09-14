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
                closable:true,
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
        var documentDocumentTypeStore = pimcore.globalmanager.get("document_documenttype_store");

        var typesColumns = [
            {header: t("name"), flex: 100, sortable: true, dataIndex: 'name',
                                                                        editor: new Ext.form.TextField({})},
            {header: t("module_optional"), flex: 50, sortable: true, dataIndex: 'module',
                                                                        editor: new Ext.form.TextField({})},
            {header: t("controller"), flex: 50, sortable: true, dataIndex: 'controller',
                                                                        editor: new Ext.form.TextField({})},
            {header: t("action"), flex: 50, sortable: true, dataIndex: 'action',
                                                                        editor: new Ext.form.TextField({})},
            {header: t("template"), flex: 50, sortable: true, dataIndex: 'template',
                                                                        editor: new Ext.form.TextField({})},
            {header: t("type"), flex: 50, sortable: true, dataIndex: 'type',
                                                                        editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: documentDocumentTypeStore
            })},
            {header: t("priority"), flex: 50, sortable: true, dataIndex: 'priority', editor: new Ext.form.ComboBox({
                store: [1,2,3,4,5,6,7,8,9,10],
                mode: "local",
                triggerAction: "all"
            })},
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false, width: 130,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false, width: 130,
                hidden: true,
                renderer: function(d) {
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
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static6/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },{
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('translate'),
                    icon: "/pimcore/static6/img/icon/translation.png",
                    handler: function(grid, rowIndex){
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
            store: this.store,
            columns : typesColumns,
            columnLines: true,
            trackMouseOver: true,
            stripeRows: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ],
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