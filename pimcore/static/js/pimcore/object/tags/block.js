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

pimcore.registerNS("pimcore.object.tags.block");
pimcore.object.tags.block = Class.create(pimcore.object.tags.abstract, {

    type: "block",

    initialize: function (data, layoutConf) {

        this.data = [];
        this.currentElements = [];
        
        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;

    },

    getLayoutEdit: function () {
        
        var panelConf = {
            bodyStyle: "padding:10px;"
        };
        if(this.layoutConf.title) {
            panelConf.title = this.layoutConf.title;
        }
        
        
        this.layout = new Ext.Panel(panelConf);
        
        if(this.data.length < 1) {
            this.layout.add(this.getControls());
        } else {
            for (var i=0; i<this.data.length; i++) {
                this.addBlockElement(i,this.data[i]);
            }
        }
        
        return this.layout;
    },
    
    getControls: function (blockElement) {
        
        var amountBox = new Ext.form.ComboBox({
            cls: "pimcore_block_amount_select",
            store: [1,2,3,4,5,6,7,8,9,10],
            value: 1,
            mode: "local",
            triggerAction: "all",
            width: 40
        });
        
        var items = [amountBox];
        
        items.push({
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            listeners: {
                "click": this.addBlock.bind(this, blockElement, amountBox)
            }
        });
        
        if(blockElement) {
            items.push({
                cls: "pimcore_block_button_minus",
                iconCls: "pimcore_icon_minus",
                listeners: {
                    "click": this.removeBlock.bind(this, blockElement)
                }
            });
            
            items.push({
                cls: "pimcore_block_button_up",
                iconCls: "pimcore_icon_up",
                listeners: {
                    "click": this.moveBlockUp.bind(this, blockElement)
                }
            });
            
            items.push({
                cls: "pimcore_block_button_down",
                iconCls: "pimcore_icon_down",
                listeners: {
                    "click": this.moveBlockDown.bind(this, blockElement)
                }
            });
        }
        
        var toolbar = new Ext.Toolbar({
            items: items
        });
        
        return toolbar;
    },
    
    detectBlockIndex: function (blockElement) {
        // detect index
        for(var s=0; s<this.layout.items.items.length; s++) {
            if(this.layout.items.items[s].key == blockElement.key) {
                index = s;
                break;
            }
        }
        
        return index;
    },
    
    addBlock: function (blockElement, amountBox) {
        
        var index = 0;
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        var amount = amountBox.getValue();
        
        for (var i=0; i<amount; i++) {
            this.addBlockElement(index+1);
        }
    },
    
    removeBlock: function (blockElement) {
        
        var key = blockElement.key;
        this.currentElements[key] = "deleted";
        
        this.layout.remove(blockElement);
        
        // check for remaining elements
        if(this.layout.items.items.length < 1) {
            this.layout.removeAll();
            this.layout.add(this.getControls());
            this.layout.doLayout();
            this.currentElements = [];
        }
    },
    
    moveBlockUp: function (blockElement) {
        
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        var newIndex = index-1;
        if(newIndex < 0) {
            newIndex = 0;
        }
        
        // move this node temorary to an other so ext recognizes a change
        this.layout.remove(blockElement, false);
        this.object.edit.layout.add(blockElement);
        this.object.edit.layout.doLayout();
        this.layout.doLayout();
        
        // move the element to the right position
        this.object.edit.layout.remove(blockElement,false);
        this.layout.insert(newIndex, blockElement);
        this.layout.doLayout();
    },
    
    moveBlockDown: function (blockElement) {
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }
        
        // move this node temorary to an other so ext recognizes a change
        this.layout.remove(blockElement, false);
        this.object.edit.layout.add(blockElement);
        this.object.edit.layout.doLayout();
        this.layout.doLayout();
        
        // move the element to the right position
        this.object.edit.layout.remove(blockElement,false);
        this.layout.insert(index+1, blockElement);
        this.layout.doLayout();
    },
    
    addBlockElement: function (index, blockData) {
        
        // remove the initial toolbar if there is no element
        if(this.currentElements.length < 1) {
            this.layout.removeAll();
        }

        
        var blockElement = new Ext.Panel({
            bodyStyle: "padding:10px;",
            style: "margin: 0 0 10px 0;",
            layout: "pimcoreform"
        });
        
        blockElement.add(this.getControls(blockElement));
        
        // add elements
        var l;
        var fields = [];
        for (var i=0; i<this.layoutConf.childs.length; i++) {
            
            l = this.layoutConf.childs[i];
            
            
            // if invisible return false
            if (l.invisible) {
                continue;
            }

            if (pimcore.object.tags[l.fieldtype]) {
                var dLayout;
                var data;
                
                try {
                    if (blockData[l.name] != "function") {
                        data = blockData[l.name];
                    }
                } catch (e) {
                    data = null;
                    console.log(e);
                }

                var field = new pimcore.object.tags[l.fieldtype](data, l);
                field.setObject(this.object);
                fields.push(field);

                // WYSIWYG is a frame that must be masked
                if (l.fieldtype == "wysiwyg") {
                    this.object.edit.fieldsToMask.push(field);
                }
                
                
                if (l.noteditable) {
                    dLayout = field.getLayoutShow();
                }
                else {
                    dLayout = field.getLayoutEdit();
                }

                // set styling
                if (l.style || l.tooltip) {
                    try {
                        dLayout.on("render", function (field) {
                            
                            try {
                                var el = this.getEl();                                
                                if(!el.hasClass("object_field")) {
                                    el = el.parent(".object_field");
                                }
                            } catch (e) {
                                console.log(e);
                                return;
                            }
                            
                            // if element does not exist, abort
                            if(!el) {
                                console.log(field.name + " style and tooltip aborted, nor matching element found");
                                return;
                            }
                            
                            // apply custom css styles
                            if(field.style) {
                                try {
                                    el.applyStyles(field.style);
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                            
                            
                            // apply tooltips
                            if(field.tooltip) {
                                try {
                                    new Ext.ToolTip({
                                        target: el,
                                        title: field.title,
                                        html: nl2br(ts(field.tooltip)),
                                        trackMouse:true,
                                        showDelay: 200
                                    });
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                        }.bind(dLayout, l));
                    }
                    catch (e) {
                        console.log(l.name + " event render not supported (tag type: " + l.fieldtype + ")");
                        console.log(e);
                    }
                }

                if(dLayout) {
                    blockElement.add(dLayout);
                }
            }
        }
        
        blockElement.fields = fields;
        blockElement.key = this.currentElements.length;
        this.layout.insert(index, blockElement);
        this.layout.doLayout();
        
        
        this.currentElements.push({
            fields: fields,
            container: blockElement
        });
    },
    
    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
    },

    getValue: function () {

        var data = [];
        var elementData = {};
        var elementFields;
        
        for(var s=0; s<this.layout.items.items.length; s++) {
            elementData = {};
            if(this.currentElements[this.layout.items.items[s].key]) {
                elementFields = this.currentElements[this.layout.items.items[s].key].fields;
                for (var u=0; u<elementFields.length; u++) {
                    elementData[elementFields[u].getName()] = elementFields[u].getValue();
                }
                data.push(elementData);
            }
        }
        
        return data;
    },

    getName: function () {
        return this.layoutConf.name;
    }
});