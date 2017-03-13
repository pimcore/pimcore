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

pimcore.registerNS("pimcore.element.selector.document");
pimcore.element.selector.document = Class.create(pimcore.element.selector.abstract, {

    initStore: function () {
        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: 50,
            proxy : {
                type: 'ajax',
                url: '/admin/search/search/find',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ["id","fullpath","type","subtype","published","title","description","name","filename"]
        });
    },

    getTabTitle: function() {
        return "document_search";
    },

    getForm: function () {

        var compositeConfig = {
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
                    window.open("http://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html");
                },
                iconCls: "pimcore_icon_help"
            })]
        };

        // check for restrictions
        var possibleRestrictions = ["page","snippet","folder","link","hardlink","email"]; //ckogler
        var filterStore = [];
        var selectedStore = [];
        for (var i=0; i<possibleRestrictions.length; i++) {
            if(this.parent.restrictions.subtype.document && in_array(possibleRestrictions[i],
                this.parent.restrictions.subtype.document )) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        // add all to store if empty
        if(filterStore.length < 1) {
            for (var i=0; i<possibleRestrictions.length; i++) {
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
            iconCls: "pimcore_icon_search",
            text: t("search"),
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
                    {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            return '<div class="pimcore_icon_' + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                        }
                    },
                    {header: t("filename"), flex: 1, sortable: true, dataIndex: 'filename'}
                ],
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowcontextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {
                        var menu = new Ext.menu.Menu();
                        var data = grid.getStore().getAt(rowIndex);

                        menu.add(new Ext.menu.Item({
                            text: t('remove'),
                            iconCls: "pimcore_icon_delete",
                            handler: function (index, item) {
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
            var columns = [
                {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div class="pimcore_icon_' + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }
                },
                {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
                {header: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
                {header: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath'},
                {header: t("title"), flex: 200, sortable: false, dataIndex: 'title', hidden: false},
                {header: t("description"), width: 200, sortable: false, dataIndex: 'description', hidden: true},
                {header: t("filename"), width: 200, sortable: false, dataIndex: 'filename', hidden: true}
            ];

            this.pagingtoolbar = this.getPagingToolbar(t("no_documents_found"));

            this.resultPanel = new Ext.grid.GridPanel({
                region: "center",
                store: this.store,
                columns: columns,
                viewConfig: {
                    forceFit: true
                },
                loadMask: true,
                columnLines: true,
                stripeRows: true,
                selModel: Ext.create('Ext.selection.RowModel', {}),
                bbar: this.pagingtoolbar,
                listeners: {
                    rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {

                        var data = grid.getStore().getAt(rowIndex);

                        if(this.parent.multiselect) {
                            this.addToSelection(data.data);
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
        var formValues = this.formPanel.getForm().getFieldValues();

        var proxy = this.store.getProxy();
        proxy.setExtraParam("type", "document");
        proxy.setExtraParam("query", formValues.query);
        proxy.setExtraParam("subtype", formValues.subtype);

        if (this.parent.config && this.parent.config.context) {
            proxy.setExtraParam("context", Ext.encode(this.parent.config.context));
        }

        this.pagingtoolbar.moveFirst();
    }
});
