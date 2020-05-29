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

pimcore.registerNS("pimcore.object.bulkexport");
pimcore.object.bulkexport = Class.create(pimcore.object.bulkbase, {

    initialize: function () {

    },


    export: function() {

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_class_bulkexport'),
            method: "GET",
            success: function(transport){
                var data = Ext.decode(transport.responseText);

                if (data.success) {
                    //TODO show dialog
                    this.data = data.data;
                    this.getLayout();
                } else {
                    Ext.MessageBox.alert(t("error"), t("error"));
                }

            }.bind(this)
        });


    },


    getLayout: function () {

        if (this.window == null) {
            var store = new Ext.data.Store({
                autoDestroy: true,
                data: this.data,
                // sortInfo:{field: 'name', direction: "ASC"},
                fields: ["icon", "checked", "type", "name", "displayName"],
                groupField: 'type'
            });

            var checkColumn = Ext.create('Ext.grid.column.Check', {
                text: t("export"),
                dataIndex: 'checked',
                width: 50
            });

            this.gridPanel = new Ext.grid.Panel({
                autoScroll: true,
                trackMouseOver: true,
                store: store,
                features: [
                    Ext.create('Ext.grid.feature.Grouping', {
                        groupHeaderTpl: t("type") + " " + '{name}'
                    })
                ],
                autoExpandColumn: "bulk_export_defintion_name",
                columnLines: true,
                stripeRows: true,
                tbar: [
                    {
                        xtype: "button",
                        text: t('select_all'),
                        handler: this.selectAll.bind(this, 1)
                    },
                    '-',
                    {
                        xtype: "button",
                        text: t('deselect_all'),
                        handler: this.selectAll.bind(this, 0)
                    }
                ],
                columns: [
                    checkColumn,
                    {
                        text: t("type"),
                        dataIndex: 'type',
                        editable: false,
                        hidden: true,
                        width: 40,
                        sortable: true
                    },
                    {
                        text: t("type"),
                        dataIndex: 'icon',
                        editable: false,
                        width: 40,
                        renderer: this.getTypeRenderer.bind(this),
                        sortable: true
                    },
                    {
                        text: t('name'),
                        dataIndex: 'displayName',
                        id: "bulk_export_defintion_name",
                        editable: false,
                        flex: 1,
                        sortable: true
                    }

                ],
                viewConfig: {
                    forceFit: true
                }
            });

            this.window = new Ext.Window({
                title: t('bulk_export'),
                width: 800,
                height: 500,
                border: false,
                modal: true,
                layout: "fit",
                iconCls: "pimcore_icon_import",
                items: [this.gridPanel],
                bbar: ["->",
                    {
                        xtype: "button",
                        text: t("close"),
                        iconCls: "pimcore_icon_cancel",
                        handler: function () {
                            this.window.close();
                        }.bind(this)
                    },
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_export",
                        text: t('export'),
                        handler: this.applyData.bind(this)
                    }
                ]

            });
        }

        this.window.show();
        return this.window;
    },

    applyData: function() {
        var store = this.gridPanel.getStore();
        var records = store.getRange();
        this.values = [];

        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];

            if (!currentData.data.checked) {
                continue;
            }
            this.values.push({
                type: currentData.data.type,
                name: currentData.data.name,
            });
        }

        this.sortValues();

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_class_bulkexportprepare'),
            method: "post",
            params: {
                data: JSON.stringify(this.values)
            },
            success: function(transport){
                var data = Ext.decode(transport.responseText);

                if (data.success) {
                    var url = Routing.generate('pimcore_admin_dataobject_class_dobulkexport');
                    pimcore.settings.showCloseConfirmation = false;
                    window.setTimeout(function () {
                        pimcore.settings.showCloseConfirmation = true;
                    },1000);

                    this.window.close();
                    location.href = url;
                }
            }.bind(this)
        });
    }
});
