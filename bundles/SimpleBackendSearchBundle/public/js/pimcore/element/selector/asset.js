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

pimcore.registerNS('pimcore.bundle.search.element.selector.asset');

/**
 * @private
 */
pimcore.bundle.search.element.selector.asset = Class.create(pimcore.bundle.search.element.selector.abstract, {
    initStore: function () {
        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: 50,
            proxy : {
                type: 'ajax',
                url: Routing.generate('pimcore_bundle_search_search_find'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                },
                extraParams: {
                    type: 'asset'
                }
            },
            fields: ["id","fullpath","type","subtype","filename"]
        });
    },

    getTabTitle: function() {
        return "asset_search";
    },

    getForm: function () {
        const compositeConfig = {
            xtype: "toolbar",
            items: [{
                xtype: "textfield",
                name: "query",
                width: 370,
                hideLabel: true,
                enableKeyEvents: true,
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            this.search();
                        }
                    }.bind(this),
                    afterrender: function () {
                        this.focus(true,500);
                    }
                }
            }, new Ext.Button({
                handler: function () {
                    window.open("https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html");
                },
                iconCls: "pimcore_icon_help"
            })]
        };

        // check for restrictions
        let possibleRestrictions = pimcore.globalmanager.get('asset_search_types');
        let filterStore = [];
        let selectedStore = [];
        for (let i=0; i<possibleRestrictions.length; i++) {
            if(this.parent.restrictions.subtype.asset && in_array(possibleRestrictions[i],
                this.parent.restrictions.subtype.asset )) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        // add all to store if empty
        if(filterStore.length < 1) {
            for (let i=0; i<possibleRestrictions.length; i++) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        var selectedValue = selectedStore.join(",");
        if(filterStore.length > 1) {
            filterStore.splice(0,0,[selectedValue, t("all_types")]);
        }

        compositeConfig.items.push({
            xtype: "combo",
            store: filterStore,
            mode: "local",
            name: "subtype",
            triggerAction: "all",
            editable: false,
            value: selectedValue
        });

        // add button
        compositeConfig.items.push({
            xtype: "button",
            text: t("search"),
            iconCls: "pimcore_icon_search",
            handler: this.search.bind(this)
        });

        if(!this.formPanel) {
            this.formPanel = new Ext.form.FormPanel({
                region: "north",
                bodyStyle: "padding: 2px;",
                items: [compositeConfig]
            });
        }

        return this.formPanel;
    },

    getSelectionPanel: function () {
        if(!this.selectionPanel) {

            this.selectionStore = new Ext.data.JsonStore({
                data: [],
                fields: ["id", "type", "filename", "fullpath", "subtype"]
            });

            this.selectionPanel = new Ext.grid.GridPanel({
                region: "east",
                title: t("your_selection"),
                tbar: [{
                    xtype: "tbtext",
                    text: t("double_click_to_add_item_to_selection"),
                    autoHeight: true,
                    style: {
                        whiteSpace: "normal"
                    }
                }],
                tbarCfg: {
                    autoHeight: true
                },
                width: 300,
                store: this.selectionStore,
                columns: [
                    {text: t("type"), width: 40, sortable: true, dataIndex: 'subtype', renderer:
                            function (value, metaData, record, rowIndex, colIndex, store) {
                                return '<div style="height: 16px;" class="pimcore_icon_asset pimcore_icon_' + value
                                    + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                            }
                    },
                    {text: t("filename"), flex: 1, sortable: true, dataIndex: 'filename'}
                ],
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowcontextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {
                        var menu = new Ext.menu.Menu();

                        menu.add(new Ext.menu.Item({
                            text: t('remove'),
                            iconCls: "pimcore_icon_delete",
                            handler: function (index, item) {

                                if(this.parent.multiselect) {
                                    var resultPanelStore = this.resultPanel.getStore();
                                    var elementId = this.selectionStore.getAt(index).id;
                                    var record = resultPanelStore.findRecord("id", elementId);

                                    if(record) {
                                        record.set('asset-selected', false);
                                    }

                                    resultPanelStore.reload();

                                }

                                this.selectionStore.removeAt(index);
                                item.parentMenu.destroy();
                            }.bind(this, rowIndex)
                        }));

                        e.stopEvent();
                        menu.showAt(e.getXY());
                    }.bind(this)
                },
                selModel: Ext.create('Ext.selection.RowModel', {}),
                bbar: ["->", {
                    text: t("select"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.parent.commitData(this.getData());
                    }.bind(this)
                }]
            });
        }

        return this.selectionPanel;
    },

    getResultPanel: function () {
        if (!this.resultPanel) {
            const columns = [
                {text: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_'
                            + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }
                },
                {text: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
                {text: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath', renderer: Ext.util.Format.htmlEncode},
                {text: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: true, renderer: Ext.util.Format.htmlEncode},
                {text: t("preview"), width: 150, sortable: false, dataIndex: 'subtype',
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        const routes = {
                            image: "pimcore_admin_asset_getimagethumbnail",
                            video: "pimcore_admin_asset_getvideothumbnail",
                            document: "pimcore_admin_asset_getdocumentthumbnail"
                        };

                        if (record.data.subtype in routes) {
                            const route = routes[record.data.subtype];

                            const params = {
                                id: record.data.id,
                                width: 100,
                                height: 100,
                                cover: true,
                                aspectratio: true
                            };

                            const uri = Routing.generate(route, params);

                            return '<div name="' + t(record.data.subtype)
                                + '"><img src="' + uri + '" /></div>';
                        }
                    }
                }
            ];

            if (this.parent.multiselect) {
                columns.unshift(
                    {
                        xtype: 'checkcolumn',
                        fieldLabel: '',
                        name: 'asset-select-checkbox',
                        text: t("select"),
                        dataIndex : 'asset-selected',
                        sortable: false,
                        renderer: function (value, metaData, record, rowIndex) {
                            const currentElementId = this.resultPanel.getStore().getAt(rowIndex).id;
                            const rec = this.selectionStore.getData().find("id", currentElementId);

                            const checkbox = new Ext.grid.column.Check();

                            if (typeof value ==='undefined' && rec !== null){
                                this.resultPanel.getStore().getAt(rowIndex).set('asset-selected', true);
                                return checkbox.renderer(true);
                            }

                            if (value && rec === null) {
                                return checkbox.renderer(true);
                            }

                            return checkbox.renderer(false);
                        }.bind(this)
                    }
                );
            }

            this.pagingtoolbar = this.getPagingToolbar();

            this.resultPanel = new Ext.grid.GridPanel({
                region: "center",
                store: this.store,
                columns: columns,
                loadMask: true,
                columnLines: true,
                stripeRows: true,
                viewConfig: {
                    forceFit: true,
                    markDirty: false,
                    listeners: {
                        refresh: function (dataview) {
                            Ext.each(dataview.panel.columns, function (column) {
                                if (column.autoSizeColumn === true) {
                                    column.autoSize();
                                }
                            })
                        }
                    }
                },
                plugins: ['gridfilters'],
                selModel: this.getGridSelModel(),
                bbar: this.pagingtoolbar,
                listeners: {
                    cellclick: {
                        fn: function(view, cellEl, colIdx, store, rowEl, rowIdx, event) {

                            var data = view.getStore().getAt(rowIdx);

                            if (this.parent.multiselect && colIdx == 0) {
                                if (data.get('asset-selected')) {
                                    this.addToSelection(data.data);
                                } else {
                                    this.removeFromSelection(data.data);
                                }
                            }
                        }.bind(this)
                    },
                    rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {

                        var data = grid.getStore().getAt(rowIndex);

                        if(this.parent.multiselect) {
                            this.addToSelection(data.data);

                            if (!record.get('asset-selected')) {
                                record.set('asset-selected', true);
                            }

                        } else {
                            // select and close
                            this.parent.commitData(this.getData());
                        }
                    }.bind(this)
                }
            });
        }


        if(this.parent.multiselect) {
            this.resultPanel.on("rowcontextmenu", this.onRowContextmenu.bind(this));
        }

        return this.resultPanel;
    },

    getGrid: function () {
        return this.resultPanel;
    },

    search: function () {
        let formValues = this.formPanel.getForm().getFieldValues();

        let proxy = this.store.getProxy();
        let query = Ext.util.Format.htmlEncode(formValues.query);
        proxy.setExtraParam("query", query);
        proxy.setExtraParam("type", 'asset');
        proxy.setExtraParam("subtype", formValues.subtype);

        if (this.parent.config && this.parent.config.context) {
            proxy.setExtraParam("context", Ext.encode(this.parent.config.context));
        }

        this.pagingtoolbar.moveFirst();
        this.updateTabTitle(query);
    }
});
