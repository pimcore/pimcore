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

pimcore.registerNS("pimcore.object.helpers.import.previewTab");
pimcore.object.helpers.import.previewTab = Class.create({

    initialize: function (config, callback) {

        this.config = config;
        this.callback = callback;

    },

    getPanel: function() {

        var data = this.config;

        var dataStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: data,
            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                    rootProperty: 'dataPreview'
                }
            },
            fields: data.dataFields
        });

        var dataGridCols = [];
        dataGridCols.push({
                header: t("preview"),
                xtype: 'actioncolumn',
                width: 80,
                tooltip: t('preview'),
                items: [
                    {
                        getClass: function (v, meta, rec, rowIndex) {
                            if (!this.hasHeadline.getValue() || rowIndex > 0) {
                                return 'pimcore_icon_search';
                            }
                            console.log("getClass");
                            // case 1:
                            //     return 'pimcore_icon_revert pimcore_action_column';
                            // case -1:
                            //     return 'pimcore_icon_hourglass pimcore_action_column';
                            // default:
                            //     return 'pimcore_icon_arrow_right pimcore_action_column';

                            // }
                        }.bind(this),

                        handler: function (dataStore, grid, rowIndex, colIndex) {
                            if (!this.hasHeadline.getValue() || rowIndex > 0) {
                                var rec = dataStore.getAt(rowIndex);
                                this.callback.preview(rowIndex);
                            }
                        }.bind(this, dataStore)
                    }
                ]
            }
        );


        for (var i = 0; i < data.dataFields.length; i++) {
            dataGridCols.push({header: t("field") + " " + i, sortable: false, dataIndex: data.dataFields[i], flex: 1});
        }


        var dataGrid = new Ext.grid.Panel({
            store: dataStore,
            columns: dataGridCols,
            viewConfig: {
                forceFit: false
            },
            autoScroll: true
        });

        var headRecord = dataStore.getAt(0);
        this.hasHeadline = new Ext.form.field.Checkbox(
            {
                xtype: "checkbox",
                name: "hasHeadRow",
                fieldLabel: t("importFileHasHeadRow"),
                listeners: {
                    change: function (headRecord, dataGrid, checkbox, checked) {
                        var i;
                        var settingsForm = this.callback.resolverSettingsPanel.setSkipHeaderRow(checked);

                        // if (checked) {
                        //     dataGrid.store.remove(headRecord);
                        //     this.importJobTotal = data.rows - 1;
                        //     this.settingsForm.getForm().findField('skipHeadRow').setValue(true);
                        //     for (i = 0; i < headRecord.fields.items.length; i++) {
                        //         var value = headRecord.get("field_" + i);
                        //         var view = dataGrid.getView();
                        //         var header = view.getHeaderAtIndex(i);
                        //         if (header) {
                        //             header.setText(value);
                        //         }
                        //     }
                        // } else {
                        //     dataGrid.store.insert(0, headRecord);
                        //     this.importJobTotal = data.rows;
                        //     this.settingsForm.getForm().findField('skipHeadRow').setValue(false);
                        //     for (i = 0; i < headRecord.fields.items.length; i++) {
                        //         var view = dataGrid.getView();
                        //         var header = view.getHeaderAtIndex(i);
                        //         if (header) {
                        //             header.setText("field_" + i);
                        //         }
                        //     }
                        // }
                        dataGrid.getView().refresh();
                    }.bind(this, headRecord, dataGrid)
                }
            });

        var formPanel = new Ext.form.FormPanel({
            items: [

                this.hasHeadline
            ],
            defaults: {
                labelWidth: 200
            },
            //autoHeight:true,
            bodyStyle: "padding: 10px;"
        });

        var previewPanel = new Ext.panel.Panel({
            title: t("preview"),
            iconCls: 'pimcore_icon_preview',
            items: [formPanel, dataGrid]
        });

        return previewPanel;
    }

});
