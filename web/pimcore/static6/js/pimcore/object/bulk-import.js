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

pimcore.registerNS("pimcore.object.bulkimport");
pimcore.object.bulkimport = Class.create({


    uploadUrl: '/admin/class/bulk-import',

    initialize: function () {
    },

    getUploadUrl: function(){
        return this.uploadUrl;
    },

    upload: function() {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function(response) {

            response = response.response;
            var data = Ext.decode(response.responseText);
            //TODO reload classes panel
            this.data = data.data;
            this.filename = data.filename;
            this.getLayout();


        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    getLayout: function () {

        if (this.window == null) {
            var store = new Ext.data.Store({
                autoDestroy: true,
                data: this.data,
                sortInfo:{field: 'name', direction: "ASC"},
                fields: [
                    {name: "icon", allowBlank: true},
                    {name: "checked", allowBlank: true},
                    {name: "type", allowBlank: true},
                    {name: "name", allowBlank: true},
                    {name: "displayName", allowBlank: true}
                ],
                groupField: 'type'
            });

            var checkColumn = Ext.create('Ext.grid.column.Check', {
                header: t("import"),
                dataIndex: 'checked',
                width: 30
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
                autoExpandColumn: "bulk_import_defintion_name",
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
                        header: t("type"),
                        dataIndex: 'type',
                        editable: false,
                        hidden: true,
                        width: 40,
                        sortable: true
                    },
                    {
                        header: t("type"),
                        dataIndex: 'icon',
                        editable: false,
                        width: 40,
                        renderer: this.getTypeRenderer.bind(this),
                        sortable: true
                    },
                    {
                        header: t('name'),
                        dataIndex: 'displayName',
                        id: "bulk_import_defintion_name",
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
                title: t('bulk_import'),
                width: 800,
                height: 500,
                border: false,
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
                        iconCls: "pimcore_icon_apply",
                        text: t('apply'),
                        handler: this.applyData.bind(this)
                    }
                ]

            });
        }

        this.window.show();
        return this.window;
    },

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        return '<div class="pimcore_icon_' + value + '" style="min-height: 16px;" name="' + record.data.name + '">&nbsp;</div>';
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
                checked: currentData.data.checked,
                type: currentData.data.type,
                name: currentData.data.name,
                displayName: currentData.data.displayName
            });
        }

        this.values.sort(function(data1, data2){
            var value1 = this.getPrio(data1);
            var value2 = this.getPrio(data2);

            if (value1 > value2) {
                return 1;
            } else if (value1 < value2) {
                return -1;
            } else {
                return 0;
            }
        }.bind(this));

        this.commitData(0);

    },

    commitData: function(idx) {
        if (idx < this.values.length) {
            if (idx == 0) {
                this.batchProgressBar = new Ext.ProgressBar({
                    text: t('generating'),
                    style: "margin: 10px;",
                    width: 500
                });

                this.batchProgressWin = new Ext.Window({
                    items: [this.batchProgressBar],
                    modal: true,
                    bodyStyle: "background: #fff;",
                    closable: false
                });
                this.batchProgressWin.show();

                this.batchProgressBar.wait({
                    interval: 500,
                    //bar will move fast!
                    duration: 5000000,
                    increment: 15,
                    scope: this,
                    fn: function () {
                    }
                });
            }

            this.batchProgressBar.updateText(t('saving') + ' ' + t(this.values[idx].type) + " " + t("definition") + " " + ts(this.values[idx].displayName) + " (" + (idx + 1) + "/" + this.values.length + ")");

            Ext.Ajax.request({
                url: "/admin/class/bulk-commit",
                method: "post",
                params: {
                    data: JSON.stringify(this.values[idx]),
                    filename: this.filename
                },
                success: function(transport){
                    var data = Ext.decode(transport.responseText);

                    if (data.success) {
                        idx++;
                        if (idx < this.values.length) {
                            this.commitData(idx);
                            return;
                        } else {
                            pimcore.helpers.showNotification(t("success"), t("definitions_saved"));
                        }
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("definition_save_error") + " " + this.values[idx].displayName);
                    }

                    this.batchProgressWin.close();

                }.bind(this),
                failure: function(transport) {
                    this.batchProgressWin.close();
                    var response = Ext.decode(transport.responseText);
                    pimcore.helpers.showNotification(t("error"), t("definition_save_error") + " " + this.values[idx].displayName);
                }.bind(this)
            });
        }
    },

    getPrio: function(data) {
        switch (data.type) {
            case "fieldcollection":
                return 0;
            case "class":
                return 1;
            case "customlayout":
                return 2;
            case "objectbrick":
                return 3;
        }
        return 0;
    },

    selectAll: function(value) {
        var store = this.gridPanel.getStore();
        var records = store.getRange();
        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];
            currentData.set("checked", value);
        }
    }
});