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

pimcore.registerNS("pimcore.element.selector.abstract");
pimcore.element.selector.abstract = Class.create({

    
    initialize: function (parent) {
        this.parent = parent;
        
        this.initStore();
        
        if(this.parent.multiselect) {
            this.searchPanel = new Ext.Panel({
                layout: "border",
                items: [this.getForm(), this.getSelectionPanel(), this.getResultPanel()],
                buttons: [{
                    text: t("select"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.parent.commitData(this.getData());
                    }.bind(this)
                }]
            });
        } else {
            this.searchPanel = new Ext.Panel({
                layout: "border",
                items: [this.getForm(), this.getResultPanel()],
                buttons: [{
                    text: t("select"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.parent.commitData(this.getData());
                    }.bind(this)
                }]
            });
        }
        
        this.parent.setSearch(this.searchPanel);
    },
    
    addToSelection: function (data) {
        
        // check for dublicates
        var existingItem = this.selectionStore.find("id", data.id);
        
        if(existingItem < 0) {
            var r = new this.selectionStore.recordType(data); 
            this.selectionStore.add(r);
        }
    },
    
    getData: function () {
        if(this.parent.multiselect) {
            this.tmpData = [];
            
            if(this.selectionStore.getCount() > 0) {
                this.selectionStore.each(function (rec) {
                    this.tmpData.push(rec.data);
                }.bind(this));
                
                return this.tmpData;
            } else {
                // is the store is empty and a item is selected take this
                var selected = this.getGrid().getSelectionModel().getSelected();
                if(selected) {
                    this.tmpData.push(selected.data);
                }
            }
            
            return this.tmpData;
        } else {
            var selected = this.getGrid().getSelectionModel().getSelected();
            if(selected) {
                return selected.data;
            }
            return null;
        }
    },

    getPagingToolbar: function(label) {
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: label
        });

        // add per-page selection
        pagingToolbar.add("-");

        pagingToolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));

        pagingToolbar.add(new Ext.form.ComboBox({
            store: [
                [50, "50"],
                [100, "100"],
                [200, "200"],
                [999999, t("all")]
            ],
            mode: "local",
            width: 50,
            value: 50,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        return pagingToolbar;
    },

    onRowContextmenu: function (grid, rowIndex, event) {

        $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( {
            backgroundColor: '#fff' }, 400);

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        var selectedRows = grid.getSelectionModel().getSelections();


        menu.add(new Ext.menu.Item({
            text: t('add_selected'),
            iconCls: "pimcore_icon_add",
            handler: function (data) {
                var selectedRows = grid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; i++) {
                    this.addToSelection(selectedRows[i].data);
                }

            }.bind(this, data)
        }));

        event.stopEvent();
        menu.showAt(event.getXY());
    }

});
