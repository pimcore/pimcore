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

pimcore.registerNS("pimcore.settings.translation.translationmerger");
pimcore.settings.translation.translationmerger = Class.create({


    initialize: function (translationType, mergeResult, callback) {
        this.translationType = translationType;
        var delta = mergeResult.delta;
        delta = base64_decode(delta);
        delta = Ext.decode(delta);

        if (delta.length == 0) {
            pimcore.helpers.showNotification(t("info"), t("nothing_to_merge"), "info");
            return;
        }

        this.delta = delta;
        this.languages = mergeResult.languages;
        this.callback = callback;

        this.store = new Ext.data.Store({
            proxy: {
                type: 'memory'
            },
            autoDestroy: true,
            sortInfo: {
                field: 'key',
                direction: 'ASC'

            },
            data: this.delta,
            fields: ['lg', 'lgname', 'icon', 'key', 'text', 'current', 'csv', 'dirty']
        });

        this.getTabPanel();
    },


    getTabPanel: function () {

        if (!this.panel) {


            var toolbar = Ext.create('Ext.Toolbar', {
                cls: 'main-toolbar',
                items: [
                    {
                        text: t('apply_all'),
                        handler: this.applyAll.bind(this),
                        iconCls: "pimcore_icon_arrow_right"
                    },
                    {
                        text: t('revert_all'),
                        handler: this.revertAll.bind(this),
                        iconCls: "pimcore_icon_revert"
                    }
                ]
            });


            this.layout = new Ext.grid.GridPanel({
                store: this.store,
                plugins: ['gridfilters'],
                columns: [
                    {header: t("language"), sortable: true, dataIndex: 'lgname', editable: false},
                    {
                        header: "&nbsp;", sortable: true, dataIndex: 'icon', editable: false, width: 40,
                        renderer: function (data) {
                            return '<img src="' + data + '" width="100%" height="auto" alt="" />';
                        }
                    },
                    {header: t("key"), sortable: true, dataIndex: 'key', editable: false, flex: 150, filter: 'string'},
                    {
                        header: t("translation_merger_csv"),
                        sortable: true,
                        dataIndex: 'csv',
                        editable: false,
                        flex: 200,
                        filter: 'string'
                    },
                    {
                        header: t("action"),
                        xtype: 'actioncolumn',
                        width: 80,
                        tooltip: t('action'),
                        items: [
                            {
                                getClass: function (v, meta, rec) {
                                    switch (rec.get('dirty')) {
                                        case 1:
                                            return 'pimcore_icon_revert pimcore_action_column';
                                        case -1:
                                            return 'pimcore_icon_hourglass pimcore_action_column';
                                        default:
                                            return 'pimcore_icon_arrow_right pimcore_action_column';

                                    }
                                },

                                handler: function (grid, rowIndex, colIndex) {
                                    var rec = this.store.getAt(rowIndex);
                                    var state = rec.get("dirty");
                                    var current = rec.get("current");
                                    var newState;

                                    if (state == 1) {
                                        newState = 0;
                                    } else {
                                        newState = 1;
                                    }
                                    if (state == 1) {
                                        rec.set("dirty", -1);
                                        rec.set("current", rec.get("text"));
                                    } else if (state != -1) {
                                        rec.set("dirty", -1);
                                        var valueFromCsv = rec.get("csv");
                                        rec.set("current", valueFromCsv);
                                    }


                                    if (rec.get("dirty") == -1) {
                                        var newData = Ext.encode([rec.data]);
                                        Ext.Ajax.request({
                                            url: "/admin/translation/merge-item",
                                            method: "post",
                                            params: {
                                                data: newData,
                                                translationType: this.translationType
                                            },
                                            success: function (response) {
                                                var result = Ext.decode(response.responseText);
                                                if (result.success) {
                                                    rec.set("dirty", newState);
                                                } else {
                                                    rec.set("dirty", state);
                                                    rec.set("current", current);
                                                }
                                            }.bind(this)
                                        });
                                    }


                                }.bind(this)
                            }
                        ]
                    },

                    {
                        header: t("translation_merger_current"),
                        sortable: true,
                        dataIndex: 'current',
                        editable: false,
                        flex: 200,
                        filter: 'string'
                    }
                ],
                viewConfig: {
                    forceFit: true,
                    markDirty: false
                },
                cls: "translationmerger"
            });

            this.panel = new Ext.Panel({
                title: t("translation_merger_" + this.translationType),
                iconCls: "pimcore_icon_translations",
                border: false,
                layout: "fit",
                closable: true,
                tbar: toolbar

            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);

            tabPanel.setActiveItem(this.panel.getId());

            this.panel.add(this.layout);
            pimcore.layout.refresh();

            this.layout.updateLayout();

        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem(this.panel.getId());
    },

    batchUpdate: function (newState) {
        var count = this.store.count();
        var newData = [];
        var newText = newState == 1 ? "csv" : "text";        // "csv" or "text"

        for (i = 0; i < count; i++) {
            var rec = this.store.getAt(i);
            var dirty = rec.get("dirty");
            if (rec.get("dirty") == -1) {
                continue;
            }

            if (typeof dirty == "undefined") {
                dirty = 0;
            }
            if (dirty != newState) {
                rec.set("dirty", -1);
                rec.set("current", rec.get(newText));
                newData.push(rec.getData());
            }
        }

        if (newData.length > 0) {
            var encodedData = Ext.encode(newData);
            Ext.Ajax.request({
                url: "/admin/translation/merge-item",
                method: "post",
                params: {
                    data: encodedData,
                    translationType: this.translationType
                },
                success: function (response) {
                    var result = Ext.decode(response.responseText);
                    if (result.success) {
                        for (i = 0; i < newData.length; i++) {
                            var recordData = newData[i];
                            var rec = this.store.getById(recordData.id);
                            rec.set("dirty", newState);
                        }

                        pimcore.helpers.showNotification(t("success"), t("batch_applied"), "success");
                    }
                }.bind(this)
            });
        }
    },

    applyAll: function () {
        this.batchUpdate(1);
    },

    revertAll: function () {
        this.batchUpdate(0);
    }
});