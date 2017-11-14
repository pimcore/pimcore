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

pimcore.registerNS("pimcore.object.helpers.import.reportTab");
pimcore.object.helpers.import.reportTab = Class.create({

    initialize: function (config, callback) {
        this.config = config;
        this.callback = callback;
    },

    getPanel: function () {
        if (!this.reportPanel) {
            var data = this.config;

            this.dataStore = new Ext.data.JsonStore({
                autoDestroy: true,
                data: data,
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json'
                    }
                },
                fields: [
                    "rowId", "message"
                ]
            });

            var dataGridCols = [];
            dataGridCols.push({header: t("row"), sortable: true, dataIndex: "rowId", width: 80, filter: 'numeric'});
            dataGridCols.push({
                    header: t("preview"),
                    xtype: 'actioncolumn',
                    width: 80,
                    tooltip: t('preview'),
                    items: [
                        {
                            getClass: function (v, meta, rec, rowIndex) {
                                return 'pimcore_icon_search';
                            }.bind(this),

                            handler: function (grid, rowIndex, colIndex) {
                                var rec = this.dataStore.getAt(rowIndex);
                                this.callback.preview(rowIndex);
                            }.bind(this)
                        }
                    ]
                }
            );


            dataGridCols.push({
                header: t("log_message"),
                sortable: true,
                dataIndex: "message",
                flex: 80,
                filter: 'string'
            });

            var dataGrid = new Ext.grid.Panel({
                store: this.dataStore,
                columns: dataGridCols,
                viewConfig: {
                    forceFit: false
                },
                autoScroll: true
            });

            this.reportPanel = new Ext.panel.Panel({
                disabled: true,
                title: t("import_report"),
                iconCls: 'pimcore_icon_import_report',
                items: [dataGrid]
            });
        }

        return this.reportPanel;
    },


    clearData: function () {
        this.dataStore.removeAll();
    },

    logData: function (rowId, message) {
        this.dataStore.add({
                rowId: rowId,
                message: message
            }
        );

    }


});
