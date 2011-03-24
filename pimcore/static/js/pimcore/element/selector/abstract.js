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
            this.selectionStore.insert(0, r);
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
    }
});
