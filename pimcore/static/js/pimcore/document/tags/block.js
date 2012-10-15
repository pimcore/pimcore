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

pimcore.registerNS("pimcore.document.tags.block");
pimcore.document.tags.block = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        if (!options) {
            options = {};
        }

        this.id = id;
        this.name = name;
        this.elements = [];
        this.options = options;

        var plusButton, minusButton, upButton, downButton, plusDiv, minusDiv, upDiv, downDiv, amountDiv, amountBox;
        this.elements = Ext.get(id).query("div." + name + "[key]");


        var limitReached = false;
        if(typeof options.limit != "undefined" && this.elements.length >= options.limit) {
            limitReached = true;
        }

        if (this.elements.length < 1) {
            this.createInitalControls();
        }
        else {
            for (var i = 0; i < this.elements.length; i++) {
                this.elements[i].key = this.elements[i].getAttribute("key");


                if(!limitReached) {
                    // amount selection
                    amountDiv = Ext.get(this.elements[i]).query(".pimcore_block_amount_" + this.name)[0];
                    amountBox = new Ext.form.ComboBox({
                        cls: "pimcore_block_amount_select",
                        store: this.getAmountValues(),
                        value: 1,
                        mode: "local",
                        triggerAction: "all",
                        width: 40,
                        listeners: {
                            /*"focus": function (el) {
                                Ext.get(el).addClass("pimcore_tag_block_force_show_buttons");
                            }.bind(this, this.elements[i]),
                            "blur": function (el) {
                                if(this.options["autoHideButtons"] !== false) {
                                    Ext.get(el).removeClass("pimcore_tag_block_force_show_buttons");
                                }
                            }.bind(this, this.elements[i])*/
                        }
                    });
                    amountBox.render(amountDiv);

                    // plus button
                    plusDiv = Ext.get(this.elements[i]).query(".pimcore_block_plus_" + this.name)[0];
                    plusButton = new Ext.Button({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus",
                        listeners: {
                            "click": this.addBlock.bind(this, this.elements[i], amountBox)
                        }
                    });
                    plusButton.render(plusDiv);
                }

                // minus button
                minusDiv = Ext.get(this.elements[i]).query(".pimcore_block_minus_" + this.name)[0];
                minusButton = new Ext.Button({
                    cls: "pimcore_block_button_minus",
                    iconCls: "pimcore_icon_minus",
                    listeners: {
                        "click": this.removeBlock.bind(this, this.elements[i])
                    }
                });
                minusButton.render(minusDiv);

                // up button
                upDiv = Ext.get(this.elements[i]).query(".pimcore_block_up_" + this.name)[0];
                upButton = new Ext.Button({
                    cls: "pimcore_block_button_up",
                    iconCls: "pimcore_icon_up",
                    listeners: {
                        "click": this.moveBlockUp.bind(this, this.elements[i])
                    }
                });
                upButton.render(upDiv);

                // up button
                downDiv = Ext.get(this.elements[i]).query(".pimcore_block_down_" + this.name)[0];
                downButton = new Ext.Button({
                    cls: "pimcore_block_button_down",
                    iconCls: "pimcore_icon_down",
                    listeners: {
                        "click": this.moveBlockDown.bind(this, this.elements[i])
                    }
                });
                downButton.render(downDiv);

                /*if(options["autoHideButtons"] !== false) {
                    Ext.get(this.elements[i]).on("mouseenter", function () {
                        Ext.get(this.query(".pimcore_block_buttons")[0]).show();
                    });
                    Ext.get(this.elements[i]).on("mouseleave", function () {
                        Ext.get(this.query(".pimcore_block_buttons")[0]).hide();
                    });
                }
                */
            }
        }
    },

    setInherited: function ($super, inherited) {
        var elements = Ext.get(this.id).query(".pimcore_block_buttons_" + this.name);
        if(elements.length > 0) {
            for(var i=0; i<elements.length; i++) {
                $super(inherited, Ext.get(elements[i]));
            }
        }
    },

    getAmountValues: function () {
        var amountValues = [];

        if(typeof this.options.limit != "undefined") {
            var maxAddValues = intval(this.options.limit) - this.elements.length;
            if(maxAddValues > 10) {
                maxAddValues = 10;
            }
            for (var a=1; a<=maxAddValues; a++) {
                amountValues.push(a);
            }
        }

        if(amountValues.length < 1) {
            amountValues = [1,2,3,4,5,6,7,8,9,10];
        }

        return amountValues;
    },

    createInitalControls: function (amountValues) {
        var amountEl = document.createElement("div");
        amountEl.setAttribute("class", "pimcore_block_amount pimcore_block_amount_" + this.name);

        var plusEl = document.createElement("div");
        plusEl.setAttribute("class", "pimcore_block_plus pimcore_block_plus_" + this.name);

        var clearEl = document.createElement("div");
        clearEl.setAttribute("class", "pimcore_block_clear");

        Ext.get(this.id).appendChild(amountEl);
        Ext.get(this.id).appendChild(plusEl);
        Ext.get(this.id).appendChild(clearEl);

        // amount selection
        amountBox = new Ext.form.ComboBox({
            cls: "pimcore_block_amount_select",
            store: this.getAmountValues(),
            mode: "local",
            triggerAction: "all",
            value: 1,
            width: 40
        });
        amountBox.render(amountEl);

        // plus button
        plusButton = new Ext.Button({
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            listeners: {
                "click": this.addBlock.bind(this, null, amountBox)
            }
        });
        plusButton.render(plusEl);
        
        Ext.get(this.id).addClass("pimcore_block_limitnotreached");
    },

    addBlock : function (element, amountbox) {

        var index = this.getElementIndex(element) + 1;
        var amount = 1;

        // get amount of new blocks
        try {
            amount = amountbox.getValue();
        }
        catch (e) {
        }

        if (intval(amount) < 1) {
            amount = 1;
        }

        // get next higher key
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
            args.push({key: nextKey});
        }

       this.elements.splice.apply(this.elements, args);

        //this.elements.splice(index, 0, {key: nextKey});

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

        // this is necessary because of the limit which is only applied when initializing
        //Even though reload is not necessary after remove, some sites change their appearance
        //according to the amount of block elements they contain and this arose the need for reload anyway
        this.reloadDocument();
    },

    moveBlockDown: function (element) {

        var index = this.getElementIndex(element);

        if (index < (this.elements.length-1)) {
            var x = this.elements[index];
            var y = this.elements[index + 1];

            this.elements[index + 1] = x;
            this.elements[index] = y;

            this.reloadDocument();

        }
    },

    moveBlockUp: function (element) {

        var index = this.getElementIndex(element);

        if (index > 0) {
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

    getValue: function () {
        var data = [];
        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i]) {
                if (this.elements[i].key) {
                    data.push(this.elements[i].key);
                }
            }
        }

        return data;
    },

    getType: function () {
        return "block";
    }
});