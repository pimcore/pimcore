/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.object.quantityValue.unitsettings");
pimcore.object.quantityValue.unitsettings = Class.create({

    dataUrl: '/admin/quantity-value/unit-proxy?',

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

        var languages = this.languages;

        var readerFields = [
            {name: 'id', allowBlank: false},
            {name: 'abbreviation', allowBlank: false},
            {name: 'longname', allowBlank: true},
            {name: 'group', allowBlank: true},
            {name: 'baseunit', allowBlank: true},
            {name: 'factor', allowBlank: true},
            {name: 'conversionOffset', allowBlank: true},
            {name: 'reference', allowBlank: true}
        ];


        var configuredFilters = [
            {type: "string", dataIndex: "abbreviation"},
            {type: "string", dataIndex: "longname"},
            {type: "string", dataIndex: "group"},
            {type: "string", dataIndex: "baseunit"},
            {type: "numeric", dataIndex: "factor"},
            {type: "string", dataIndex: "reference"}
        ];

        var typesColumns = [
            {flex: 1, dataIndex: 'id', header: t("id"), hidden: true, editor: new Ext.form.TextField({}), filter: 'string'},
            {flex: 1, dataIndex: 'abbreviation', header: t("abbreviation"), editor: new Ext.form.TextField({}), filter: 'string'},
            {flex: 2, dataIndex: 'longname', header: t("longname"), editor: new Ext.form.TextField({}), filter: 'string'},
            {flex: 1, dataIndex: 'group', header: t("group"), editor: new Ext.form.TextField({}), filter: 'string', hidden: true},
            {flex: 1, dataIndex: 'baseunit', header: t("baseunit"), editor: new Ext.form.TextField({}), hidden: true},
            {flex: 1, dataIndex: 'factor', header: t("conversionFactor"), editor: new Ext.form.NumberField({decimalPrecision: 10}), filter: 'numeric', hidden: true},
            {flex: 1, dataIndex: 'conversionOffset', header: t("conversionOffset"), editor: new Ext.form.NumberField({decimalPrecision: 10}), filter: 'numeric', hidden: true},
            {flex: 1, dataIndex: 'reference', header: t("reference"), editor: new Ext.form.TextField({}), hidden: true, filter: 'string'}
        ];

        typesColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [{
                tooltip: t('delete'),
                icon: "/pimcore/static/img/icon/cross.png",
                handler: function (grid, rowIndex) {
                    grid.getStore().removeAt(rowIndex);
                }.bind(this)
            }]
        });

        var itemsPerPage = 20;
        this.store = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: this.dataUrl,
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
                    create  : this.dataUrl + "xaction=create",
                    read    : this.dataUrl + "xaction=read",
                    update  : this.dataUrl + "xaction=update",
                    destroy : this.dataUrl + "xaction=destroy"
                },
                extraParams: {
                    limit: itemsPerPage
                }
            },
            remoteSort: true,
            autoSync: true,
            listeners: {
                update: function() {
                    pimcore.helpers.quantityValue.getClassDefinitionStore().reload();
                    if (pimcore.helpers.quantityValue.store) {
                        // remote call could be avoided by updating the store directly
                        pimcore.helpers.quantityValue.store.reload();
                    }
                }
            }
        });
        this.store.load();

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 80,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            plugins: ['gridfilters', this.cellEditing],
            columnLines: true,
            stripeRows: true,
            columns : typesColumns,
            bbar: this.pagingtoolbar,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            tbar: [
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
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },

    onAdd: function (btn, ev) {
        var u = {};
        //id = -1;
        this.cellEditing.completeEdit();
        this.grid.store.insert(0, u);
        this.cellEditing.startEditByPosition({
            row: 0,
            column: 0
        });
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

