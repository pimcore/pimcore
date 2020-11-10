/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.quantityValue.unitsettings");
pimcore.object.quantityValue.unitsettings = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

   activate: function (filter) {
        if(filter){
            this.store.baseParams.filter = filter;
            this.store.load();
            this.filterField.setValue(filter);
        }
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("quantityValue_units");
    },

    getHint: function(){
        return "";
    },

    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "quantityValue_units",
                iconCls: "pimcore_icon_quantityValue",
                title: t("quantityValue_units"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("quantityValue_units");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("quantityValue_units");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getRowEditor: function () {

        var baseUnitStore = Ext.create('Ext.data.JsonStore', {
            fields: [{
                name: 'id',
                type: 'string'
            }, 'abbreviation', 'longname', 'group', 'baseunit', 'factor', 'conversionOffset', 'reference', 'converter'],
            proxy: {
                type: 'ajax',
                async: true,
                batchActions: false,
                url: Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }

            },
            // disable client pagination, default: 25
            pageSize: 0,
            listeners: {
                load: function (store, records) {
                    var storeData = records;
                    storeData.unshift({'id': -1, 'abbreviation' : "(" + t("empty") + ")"});
                    store.loadData(storeData);
                }
            }
        });
        baseUnitStore.load();

        var baseUnitEditor = {
            xtype: 'combobox',
            triggerAction: "all",
            autoSelect: true,
            editable: true,
            selectOnFocus: true,
            forceSelection: true,
            valueField: 'id',
            displayField: 'abbreviation',
            queryMode: 'local',
            store: baseUnitStore
        };

        var typesColumns = [
            {flex: 1, dataIndex: 'id', text: t("id"), filter: 'string'},
            {flex: 1, dataIndex: 'abbreviation', text: t("abbreviation"), editor: new Ext.form.TextField({}), filter: 'string'},
            {flex: 2, dataIndex: 'longname', text: t("longname"), editor: new Ext.form.TextField({}), filter: 'string'},
            {flex: 1, dataIndex: 'group', text: t("group"), editor: new Ext.form.TextField({}), filter: 'string', hidden: true},
            {flex: 1, dataIndex: 'baseunit', text: t("baseunit"), editor: baseUnitEditor, renderer: function(value){
                if(!value) {
                    return '('+t('empty')+')';
                }

                var baseUnit = baseUnitStore.getById(value);
                if(!baseUnit) {
                    return '('+t('empty')+')';
                }
                return baseUnit.get('abbreviation');
            }},
            {flex: 1, dataIndex: 'factor', text: t("conversionFactor"), editor: new Ext.form.NumberField({decimalPrecision: 10}), filter: 'numeric'},
            {flex: 1, dataIndex: 'conversionOffset', text: t("conversionOffset"), editor: new Ext.form.NumberField({decimalPrecision: 10}), filter: 'numeric'},
            {flex: 1, dataIndex: 'reference', text: t("reference"), editor: new Ext.form.TextField({}), hidden: true, filter: 'string'},
            {flex: 1, dataIndex: 'converter', text: t("converter_service"), editor: new Ext.form.TextField({}), filter: 'string'}
        ];

        typesColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            menuText: t('delete'),
            width: 30,
            items: [{
                tooltip: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (grid, rowIndex) {
                    grid.getStore().removeAt(rowIndex);
                }.bind(this)
            }]
        });

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        this.store = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget'),
                reader: {
                    type: 'json',
                    rootProperty: 'data',
                    totalProperty: 'total',
                    successProperty: 'success'
                },
                writer: {
                    type: 'json',
                    writeAllFields: true,
                    rootProperty: 'data',
                    encode: 'true'
                },
                api: {
                    create  : Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget', {xaction: 'create'}),
                    read    : Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget', {xaction: 'read'}),
                    update  : Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget', {xaction: 'update'}),
                    destroy : Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget', {xaction: 'destroy'})
                },
                pageSize: itemsPerPage
            },
            remoteSort: true,
            remoteFilter: true,
            autoSync: true,
            listeners: {
                update: function() {
                    pimcore.helpers.quantityValue.getClassDefinitionStore().reload();
                    baseUnitStore.reload();
                    if (pimcore.helpers.quantityValue.store) {
                        // remote call could be avoided by updating the store directly
                        pimcore.helpers.quantityValue.store.reload();
                    }
                }
            }
        });
        this.store.load();

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            plugins: ['pimcore.gridfilters', this.cellEditing],
            columnLines: true,
            stripeRows: true,
            columns : typesColumns,
            bbar: this.pagingtoolbar,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [
                    {
                        text: t('add'),
                        handler: this.onAdd.bind(this),
                        iconCls: "pimcore_icon_add"
                    },
                    '-',
                    {
                        text: t('delete'),
                        handler: this.onDelete.bind(this),
                        iconCls: "pimcore_icon_delete"
                    },
                    '-',
                    {
                        text: t('reload'),
                        handler: function () {
                            this.store.reload();
                        }.bind(this),
                        iconCls: "pimcore_icon_reload"
                    },'-',{
                        text: this.getHint(),
                        xtype: "tbtext",
                        style: "margin: 0 10px 0 0;"
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
        Ext.MessageBox.prompt(' ', t('unique_identifier'),
            function (button, value, object) {
                var regresult = value.match(/[a-zA-Z0-9_\-]+/);
                if (button == "ok") {
                    if (value.length >= 1 && regresult == value) {

                        // this is rather a workaround, Ext doesn't sync if the id field is already filled.
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_dataobject_quantityvalue_unitproxyget', {xaction: 'create'}),
                            method: 'POST',
                            params: {
                                data: Ext.encode({
                                    id: value
                                })
                            },
                            success: function () {
                                var u = {
                                    id: value
                                };
                                this.cellEditing.completeEdit();
                                let recs = this.grid.store.insert(0, [u]);

                                this.cellEditing.startEditByPosition({
                                    row: 0,
                                    column: 0
                                });

                            }.bind(this)
                        });

                    } else {
                        Ext.Msg.alert(' ', t('failed_to_create_new_item'));
                    }
                }
            }.bind(this)
        );

    },

    onDelete: function () {
        var selections = this.grid.getSelectionModel().getSelected();
        if (!selections || selections.length < 1) {
            return false;
        }
        var rec = selections.getAt(0);
        this.grid.store.remove(rec);
    }
});
