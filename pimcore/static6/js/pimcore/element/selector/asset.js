/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.element.selector.asset");
pimcore.element.selector.asset = Class.create(pimcore.element.selector.abstract, {

    initStore: function () {
        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            proxy : {
                type: 'ajax',
                url: '/admin/search/search/find',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ["id","fullpath","type","subtype","filename"]
        });
    },

    getForm: function () {

        var compositeConfig = {
            xtype: "fieldset",
            layout: "hbox",
            hideLabel: true,
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
                iconCls: "pimcore_icon_menu_help"
            })]
        };

        // check for restrictions
        var possibleRestrictions = ["folder", "image", "text", "audio", "video", "document", "archive", "unknown"];
        var filterStore = [];
        var selectedStore = [];
        for (var i=0; i<possibleRestrictions.length; i++) {
            if(this.parent.restrictions.subtype.asset && in_array(possibleRestrictions[i],
                this.parent.restrictions.subtype.asset )) {
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
            forceSelection: true,
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
                bodyStyle: "padding: 5px;",
                height: 35,
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
                    width: 180,
                    style: {
                        whiteSpace: "normal"
                    }
                }],
                tbarCfg: {
                    autoHeight: true
                },
                width: 200,
                store: this.selectionStore,
                columns: [
                    {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype', renderer:
                        function (value, metaData, record, rowIndex, colIndex, store) {
                            return '<div style="height: 16px;" class="pimcore_icon_asset pimcore_icon_' + value
                                + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                        }
                    },
                    {header: t("filename"), flex: 1, sortable: false, dataIndex: 'filename'}
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
                selModel: Ext.create('Ext.selection.RowModel', {})
            });
        }

        return this.selectionPanel;
    },

    getResultPanel: function () {
        if (!this.resultPanel) {
            var columns = [
                {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_'
                            + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }
                },
                {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
                {header: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath'},
                {header: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: true},
                {header: t("preview"), width: 100, sortable: false, dataIndex: 'subtype',
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        if(record.data.subtype == "image") {
                            return '<div name="' + t(record.data.subtype)
                                + '"><img src="/admin/asset/get-image-thumbnail/id/'
                                + record.data.id
                                + '/width/100/height/100/cover/true/aspectratio/true" /></div>';
                        }
                    }
                }
            ];

            var sm;

            if(this.parent.multiselect) {
                this.selectionColumn = Ext.create('Ext.selection.CheckboxModel', {});
                sm  = this.selectionColumn;
            } else {
                sm = Ext.create('Ext.selection.RowModel', {}); //  Ext.grid.RowSelectionModel({singleSelect:true});
            }

            this.pagingtoolbar = this.getPagingToolbar(t("no_assets_found"));

            this.resultPanel = new Ext.grid.GridPanel({
                region: "center",
                store: this.store,
                columns: columns,
                loadMask: true,
                columnLines: true,
                stripeRows: true,
                viewConfig: {
                    forceFit: true
                },
                plugins: ['gridfilters'],
                selModel: sm,
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
        proxy.setExtraParam("type", "asset");
        proxy.setExtraParam("query", formValues.query);
        proxy.setExtraParam("subtype", formValues.subtype);
        //this.store.load();

        this.pagingtoolbar.moveFirst();
    }
});