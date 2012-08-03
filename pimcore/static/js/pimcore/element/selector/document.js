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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.element.selector.document");
pimcore.element.selector.document = Class.create(pimcore.element.selector.abstract, {
    
    initStore: function () {
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: "data",
            remoteSort: true,
            proxy : new Ext.data.HttpProxy({
                method: 'POST',
                url: "/admin/search/search/find"
            }),
            fields: ["id","fullpath","type","subtype","published","title","description","keywords","name","filename"]
        });
    },
    
    getForm: function () {
        
        var compositeConfig = {
            xtype: "compositefield",
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
                    }.bind(this)
                }
            }, new Ext.Button({
                handler: function () {
                    window.open("http://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html");
                },
                iconCls: "pimcore_icon_menu_help"
            })]
        };
        
        // check for restrictions
        var possibleRestrictions = ["page","snippet","folder","link","hardlink","email"]; //ckogler
        var filterStore = [];
        var selectedStore = [];
        for (var i=0; i<possibleRestrictions.length; i++) {
           if(this.parent.restrictions.subtype.document && in_array(possibleRestrictions[i], this.parent.restrictions.subtype.document )) {
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
            iconCls: "pimcore_icon_search",
            text: t("search"),
            handler: this.search.bind(this)
        });
        
        if(!this.formPanel) {
            this.formPanel = new Ext.form.FormPanel({
                layout: "pimcoreform",
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
                    {header: t("type"), width: 25, sortable: true, dataIndex: 'subtype', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) center center no-repeat; height: 16px;" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }},
                    {header: t("filename"), width: 40, sortable: true, dataIndex: 'filename'}
                ],
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowcontextmenu: function (grid, rowIndex, event) {
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
                
                        event.stopEvent();
                        menu.showAt(event.getXY());
                    }.bind(this)
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true})
            });
        }
        
        return this.selectionPanel;
    },
    
    getResultPanel: function () {
        if (!this.resultPanel) {
        
            this.pagingtoolbar = new Ext.PagingToolbar({
                pageSize: 50,
                store: this.store,
                displayInfo: true,
                displayMsg: '{0} - {1} / {2}',
                emptyMsg: t("no_documents_found")
            });

            this.resultPanel = new Ext.grid.GridPanel({
                region: "center",
                store: this.store,
                columns: [
                    {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) center center no-repeat; height: 16px;" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }},
                    {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
                    {header: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
                    {header: t("path"), width: 200, sortable: true, dataIndex: 'fullpath'},
                    {header: t("title"), width: 200, sortable: false, dataIndex: 'title', hidden: false},
                    {header: t("description"), width: 200, sortable: false, dataIndex: 'description', hidden: true},
                    {header: t("keywords"), width: 200, sortable: false, dataIndex: 'keywords', hidden: true},
                    {header: t("filename"), width: 200, sortable: false, dataIndex: 'filename', hidden: true}
                ],
                viewConfig: {
                    forceFit: true
                },
                loadMask: true,
                columnLines: true,
                stripeRows: true,
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                bbar: this.pagingtoolbar,
                listeners: {
                    rowdblclick: function (grid, rowIndex, ev) {
                        
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
        
        return this.resultPanel;
    },
    
    getGrid: function () {
        return this.resultPanel;
    },
    
    search: function () {
        var formValues = this.formPanel.getForm().getFieldValues();
        
        this.store.baseparams = {};
        this.store.setBaseParam("type", "document");
        this.store.setBaseParam("query", formValues.query);
        this.store.setBaseParam("subtype", formValues.subtype);
        //this.store.load();

        this.pagingtoolbar.moveFirst();
    }
});