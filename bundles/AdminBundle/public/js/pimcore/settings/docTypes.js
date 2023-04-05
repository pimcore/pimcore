/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.settings.document.doctypes");
/**
 * @private
 */
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
                text: t("controller"),
                flex: 200,
                sortable: true,
                dataIndex: 'controller',
                editor: new Ext.form.ComboBox({
                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        autoLoad: true,
                        proxy: {
                            type: 'ajax',
                            batchActions: false,
                            url: Routing.generate('pimcore_admin_misc_getavailablecontroller_references'),
                            reader: {
                                type: 'json',
                                rootProperty: 'data'
                            }
                        },
                        fields: ["name"]
                    }),
                    triggerAction: "all",
                    typeAhead: true,
                    queryMode: "local",
                    anyMatch: true,
                    editable: true,
                    forceSelection: false,
                    displayField: 'name',
                    valueField: 'name',
                    matchFieldWidth: false,
                    listConfig: {
                        maxWidth: 400
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
                    store: this.getPredefinedDocumentTypes()
                })
            },
            {
                text: t("priority"),
                flex: 30,
                sortable: true,
                dataIndex: 'priority',
                editor: new Ext.form.NumberField({
                    mode: "local",
                    editable: true,
                    minValue: 0,
                    decimalPrecision: 0,
                    triggerAction: "all"
                })
            },
            {
                xtype: 'checkcolumn',
                text: t("static"),
                dataIndex: 'staticGeneratorEnabled',
                width: 50,
                renderer: function (value, metaData, record) {
                    return (record.get('type') !== "page") ? '' : this.defaultRenderer(value, metaData);
                },
                listeners: {
                    beforecheckchange: function (el, rowIndex, checked, record, eOpts) {
                        if(!record.data.writeable) {
                            pimcore.helpers.showNotification(t("info"), t("config_not_writeable"), "info");
                            return false;
                        }
                        if (this.store.getAt(rowIndex).get("type") !== "page") {
                            record.set('staticGeneratorEnabled', false);
                            return false;
                        }
                    }.bind(this),
                    checkChange: function (column, rowIndex, checked, eOpts) {
                        var record = this.store.getAt(rowIndex);
                        record.set('staticGeneratorEnabled', checked);
                    }.bind(this)
                }
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
                        return Ext.Date.format(date, "Y-m-d H:i:s");
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
                        return Ext.Date.format(date, "Y-m-d H:i:s");
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
                    getClass: function (v, meta, rec) {
                        var klass = "pimcore_action_column ";
                        if (rec.data.writeable) {
                            klass += "pimcore_icon_minus";
                        }
                        return klass;
                    },
                    tooltip: t('delete'),
                    handler: function (grid, rowIndex) {
                        let data = grid.getStore().getAt(rowIndex);
                        pimcore.helpers.deleteConfirm(t('document_type'),
                            Ext.util.Format.htmlEncode(data.data.name),
                            function () {
                                grid.getStore().removeAt(rowIndex);
                        }.bind(this));
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
                            pimcore.globalmanager.get("translationdomainmanager").activate(rec.data.name);
                        }
                        catch (e) {
                            pimcore.globalmanager.add("translationdomainmanager",
                                new pimcore.settings.translation.domain("admin",rec.data.name));
                        }
                    }.bind(this)
                }]
            }
        ];


        this.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 1,
            clicksToMoveEditor: 1,
            listeners: {
                beforeedit: function (editor, context, eOpts) {
                    if (!context.record.data.writeable) {
                        return false;
                    }
                }
            }
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
                this.rowEditing
            ],
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [
                    {
                        text: t('add'),
                        handler: this.onAdd.bind(this),
                        iconCls: "pimcore_icon_add",
                        disabled: !pimcore.settings['document-types-writeable']
                    }
                ]
            },
            viewConfig: {
                forceFit: true,
                getRowClass: function (record, rowIndex) {
                    return record.data.writeable ? '' : 'pimcore_grid_row_disabled';
                }
            }
        });

        const prepareDocumentTypesGrid = new CustomEvent(pimcore.events.prepareDocumentTypesGrid, {
            detail: {
                grid: this.grid,
                object: this
            }
        });

        document.dispatchEvent(prepareDocumentTypesGrid);

        return this.grid;
    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0, {
            name: t('new_document_type'),
            type: "page"
        });
    },

    getPredefinedDocumentTypes: function () {
        let predefinedDocumentTypes = [];
        for (const [key, value] of Object.entries(pimcore.settings.document_types_configuration)) {
            if(value.predefined_document_types) {
                predefinedDocumentTypes.push(key);
            }
        }
        return predefinedDocumentTypes;
    }
});
