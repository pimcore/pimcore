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

pimcore.registerNS("pimcore.document.tags.areablock");
pimcore.document.tags.areablock = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data) {

        this.id = id;
        this.name = name;
        this.elements = [];
        this.options = options;

        var plusButton, minusButton, upButton, downButton, plusDiv, minusDiv, upDiv, downDiv, typemenu, typeDiv, typebuttontext;

        this.elements = Ext.get(id).query("." + name);

        // type mapping
        var typeNameMappings = {};
        for (var i=0; i<this.options.types.length; i++) {
            typeNameMappings[this.options.types[i].type] = {
                name: this.options.types[i].name,
                description: this.options.types[i].description
            };
        }

        if (this.elements.length < 1) {
            this.createInitalControls();
        }
        else {
            for (var i = 0; i < this.elements.length; i++) {
                this.elements[i].key = this.elements[i].getAttribute("key");
                this.elements[i].type = this.elements[i].getAttribute("type");

                // edit button
                try {
                    editDiv = Ext.get(this.elements[i]).query(".pimcore_area_edit_button")[0];
                    editButton = new Ext.Button({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_edit",
                        handler: this.editmodeOpen.bind(this, this.elements[i])
                    });
                    editButton.render(editDiv);
                } catch (e) {}

                // plus button
                plusDiv = Ext.get(this.elements[i]).query(".pimcore_block_plus")[0];
                plusButton = new Ext.Button({
                    cls: "pimcore_block_button_plus",
                    iconCls: "pimcore_icon_plus",
                    menu: [this.getTypeMenu(this, this.elements[i])]
                });
                plusButton.render(plusDiv);

                // minus button
                minusDiv = Ext.get(this.elements[i]).query(".pimcore_block_minus")[0];
                minusButton = new Ext.Button({
                    cls: "pimcore_block_button_minus",
                    iconCls: "pimcore_icon_minus",
                    listeners: {
                        "click": this.removeBlock.bind(this, this.elements[i])
                    }
                });
                minusButton.render(minusDiv);

                // up button
                upDiv = Ext.get(this.elements[i]).query(".pimcore_block_up")[0];
                upButton = new Ext.Button({
                    cls: "pimcore_block_button_up",
                    iconCls: "pimcore_icon_up",
                    listeners: {
                        "click": this.moveBlockUp.bind(this, this.elements[i])
                    }
                });
                upButton.render(upDiv);

                // down button
                downDiv = Ext.get(this.elements[i]).query(".pimcore_block_down")[0];
                downButton = new Ext.Button({
                    cls: "pimcore_block_button_down",
                    iconCls: "pimcore_icon_down",
                    listeners: {
                        "click": this.moveBlockDown.bind(this, this.elements[i])
                    }
                });
                downButton.render(downDiv);

                // type button
                typebuttontext = "<b>"  + this.elements[i].type + "</b>";
                if(typeNameMappings[this.elements[i].type] && typeof typeNameMappings[this.elements[i].type].name != "undefined") {
                    typebuttontext = "<b>" + typeNameMappings[this.elements[i].type].name + "</b> " + typeNameMappings[this.elements[i].type].description
                }

                typeDiv = Ext.get(this.elements[i]).query(".pimcore_block_type")[0];
                typeButton = new Ext.Button({
                    cls: "pimcore_block_button_type",
                    text: typebuttontext,
                    handleMouseEvents: false
                });
                typeButton.render(typeDiv);
                
                
                if(this.elements.length >= options.limit) {
                   Ext.get(id).addClass("pimcore_block_limitreached");
                }
            }
        }
    },
    
    createInitalControls: function () {
        
        var plusEl = document.createElement("div");
        plusEl.setAttribute("class", "pimcore_block_plus");

        var clearEl = document.createElement("div");
        clearEl.setAttribute("class", "pimcore_block_clear");

        Ext.get(this.id).appendChild(plusEl);
        Ext.get(this.id).appendChild(clearEl);

        // plus button
        plusButton = new Ext.Button({
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            menu: [this.getTypeMenu(this, null)]
        });
        plusButton.render(plusEl);
    },
    
    getTypeMenu: function (scope, element) {
        var menu = [];
        var groupMenu;

        if(typeof this.options.group != "undefined") {
            var groups = Object.keys(this.options.group);
            for (var g=0; g<groups.length; g++) {
                if(groups[g].length > 0) {
                    groupMenu = {
                        text: groups[g],
                        iconCls: "pimcore_icon_area",
                        hideOnClick: false,
                        menu: []
                    };

                    for (var i=0; i<this.options.types.length; i++) {
                        if(in_array(this.options.types[i].type,this.options.group[groups[g]])) {
                            groupMenu.menu.push(this.getMenuConfigForBrick(this.options.types[i], scope, element));
                        }
                    }
                    menu.push(groupMenu);
                }
            }
        } else {
            for (var i=0; i<this.options.types.length; i++) {
                menu.push(this.getMenuConfigForBrick(this.options.types[i], scope, element));
            }
        }

        return menu;
    },

    getMenuConfigForBrick: function (brick, scope, element) {
        var tmpEntry = {
            text: "<b>" + brick.name + "</b> | " + brick.description,
            iconCls: "pimcore_icon_area",
            listeners: {
                "click": this.addBlock.bind(scope, element, brick.type)
            }
        };

        return tmpEntry;
    },

    addBlock : function (element, type) {
        
        var index = this.getElementIndex(element) + 1;
        var amount = 1;
        

        // get next heigher key
        var nextKey = 0;
        var currentKey;

        for (var i = 0; i < this.elements.length; i++) {
            currentKey = intval(this.elements[i].key);
            if (currentKey > nextKey) {
                nextKey = currentKey;
            }
        }
                
        var args = [index, 0];

        for (var p = 0; p < amount; p++) {
            nextKey++;
            args.push({
                key: nextKey,
                type: type
            });
        }
        
        this.elements.splice.apply(this.elements, args);
        this.reloadDocument();
    },

    removeBlock: function (element) {

        var index = this.getElementIndex(element);

        this.elements.splice(index, 1);
        Ext.get(element).remove();

        // there is no existing block element anymore
        if (this.elements.length < 1) {
            this.createInitalControls();
        }

        //Even though reload is not necessary after remove, some sites change their appearance
        //according to the amount of block elements they contain and this arose the need for reload anyway
        this.reloadDocument();
    },

    moveBlockDown: function (element) {

        var index = this.getElementIndex(element);

        if (Ext.get(element).next()) {
            var x = this.elements[index];
            var y = this.elements[index + 1];

            this.elements[index + 1] = x;
            this.elements[index] = y;

            this.reloadDocument();

        }
    },

    moveBlockUp: function (element) {

        var index = this.getElementIndex(element);

        if (Ext.get(element).prev()) {
            var x = this.elements[index];
            var y = this.elements[index - 1];

            this.elements[index - 1] = x;
            this.elements[index] = y;

            this.reloadDocument();
        }
    },

    getElementIndex: function (element) {

        try {
            var key = Ext.get(element).dom.key;
            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].key == key) {
                    var index = i;
                    break;
                }
            }
        }
        catch (e) {
            return 0;
        }

        return index;
    },


    editmodeOpen: function (element) {

        var content = Ext.get(element).query(".pimcore_area_editmode")[0];

        this.editmodeWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 330,
            title: "Edit Block",
            closeAction: "hide",
            bodyStyle: "padding: 10px;",
            closable: false,
            autoScroll: true,
            listeners: {
                afterrender: function (content) {
                    Ext.get(content).show();

                    var elements = Ext.get(content).query(".pimcore_editable");
                    for (var i=0; i<elements.length; i++) {
                        var name = elements[i].getAttribute("id").split("pimcore_editable_").join("");
                        for (var e=0; e<editables.length; e++) {
                            if(editables[e].getName() == name) {
                                if(editables[e].element) {
                                    if(typeof editables[e].element.doLayout == "function") {
                                        editables[e].element.doLayout();
                                    }
                                }
                                break;
                            }
                        }
                    }

                }.bind(this, content)
            },
            buttons: [{
                text: t("save"),
                listeners: {
                    "click": this.editmodeSave.bind(this)
                },
                icon: "/pimcore/static/img/icon/tick.png"
            }],
            contentEl: content
        });
        this.editmodeWindow.show();
    },

    editmodeSave: function () {
        this.editmodeWindow.close();

        this.reloadDocument();
    },

    getValue: function () {
        var data = [];
        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i]) {
                if (this.elements[i].key) {
                    data.push({
                        key: this.elements[i].key,
                        type: this.elements[i].type
                    });
                }
            }
        }

        return data;
    },

    getType: function () {
        return "areablock";
    }
});