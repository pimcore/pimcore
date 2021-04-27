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
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.editables.block");
pimcore.document.editables.block = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {

        this.id = id;
        this.name = name;
        this.elements = [];
        this.config = this.parseConfig(config);
    },

    refresh: function() {
        var plusButton, minusButton, upButton, downButton, plusDiv, minusDiv, upDiv, downDiv, amountDiv, amountBox;
        this.elements = Ext.get(this.id).query('.pimcore_block_entry[data-name="' + this.name + '"][key]');

        var limitReached = false;
        if(this.config['limit'] && this.elements.length >= this.config.limit) {
            limitReached = true;
        }

        if (this.elements.length < 1) {
            this.createInitalControls();
        }
        else {
            Ext.get(this.id).removeCls("pimcore_block_buttons");

            for (var i = 0; i < this.elements.length; i++) {

                if(this.elements[i].key) {
                    continue;
                }

                this.elements[i].key = this.elements[i].getAttribute("key");

                if(!limitReached) {
                    // amount selection
                    amountDiv = Ext.get(this.elements[i]).query('.pimcore_block_amount[data-name="' + this.name + '"]')[0];
                    amountBox = new Ext.form.ComboBox({
                        cls: "pimcore_block_amount_select",
                        store: this.getAmountValues(),
                        value: 1,
                        mode: "local",
                        editable: false,
                        triggerAction: "all",
                        width: 45
                    });
                    amountBox.render(amountDiv);

                    // plus button
                    plusDiv = Ext.get(this.elements[i]).query('.pimcore_block_plus[data-name="' + this.name + '"]')[0];
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
                minusDiv = Ext.get(this.elements[i]).query('.pimcore_block_minus[data-name="' + this.name + '"]')[0];
                minusButton = new Ext.Button({
                    cls: "pimcore_block_button_minus",
                    iconCls: "pimcore_icon_minus",
                    listeners: {
                        "click": this.removeBlock.bind(this, this.elements[i])
                    }
                });
                minusButton.render(minusDiv);

                // up button
                upDiv = Ext.get(this.elements[i]).query('.pimcore_block_up[data-name="' + this.name + '"]')[0];
                upButton = new Ext.Button({
                    cls: "pimcore_block_button_up",
                    iconCls: "pimcore_icon_up",
                    listeners: {
                        "click": this.moveBlockUp.bind(this, this.elements[i])
                    }
                });
                upButton.render(upDiv);

                // up button
                downDiv = Ext.get(this.elements[i]).query('.pimcore_block_down[data-name="' + this.name + '"]')[0];
                downButton = new Ext.Button({
                    cls: "pimcore_block_button_down",
                    iconCls: "pimcore_icon_down",
                    listeners: {
                        "click": this.moveBlockDown.bind(this, this.elements[i])
                    }
                });
                downButton.render(downDiv);
            }
        }
    },

    render: function () {
        this.refresh();

        Ext.get(this.id).on('mouseenter', function (event) {
            Ext.get(this.id).addCls('pimcore_block_entry_over');
        });

        Ext.get(this.id).on('mouseleave', function (event) {
            Ext.get(this.id).removeCls('pimcore_block_entry_over');
        });
    },

    setInherited: function ($super, inherited) {
        var elements = Ext.get(this.id).query('.pimcore_block_buttons[data-name="' + this.name + '"]');
        if(elements.length > 0) {
            for(var i=0; i<elements.length; i++) {
                $super(inherited, Ext.get(elements[i]));
            }
        }
    },

    getAmountValues: function () {
        var amountValues = [];

        if(typeof this.config.limit != "undefined") {
            var maxAddValues = intval(this.config.limit) - this.elements.length;
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
        amountEl.setAttribute("class", "pimcore_block_amount");
        amountEl.setAttribute("data-name", this.name);

        var plusEl = document.createElement("div");
        plusEl.setAttribute("class", "pimcore_block_plus");
        plusEl.setAttribute("data-name", this.name);

        var clearEl = document.createElement("div");
        clearEl.setAttribute("class", "pimcore_block_clear");
        clearEl.setAttribute("data-name", this.name);

        Ext.get(this.id).appendChild(amountEl);
        Ext.get(this.id).appendChild(plusEl);
        Ext.get(this.id).appendChild(clearEl);

        // amount selection
        var amountBox = new Ext.form.ComboBox({
            cls: "pimcore_block_amount_select",
            store: this.getAmountValues(),
            mode: "local",
            triggerAction: "all",
            editable: false,
            value: 1,
            width: 55
        });
        amountBox.render(amountEl);

        // plus button
        var plusButton = new Ext.Button({
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            listeners: {
                "click": this.addBlock.bind(this, null, amountBox)
            }
        });
        plusButton.render(plusEl);

        Ext.get(this.id).addCls("pimcore_block_limitnotreached pimcore_block_buttons");
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

        if(this.config['reload'] === true) {
            var args = [index, 0];
            var firstNewKey = nextKey+1;

            for (var p = 0; p < amount; p++) {
                nextKey++;
                args.push({key: nextKey});
            }

            this.elements.splice.apply(this.elements, args);

            editWindow.lastScrollposition = '#' + this.id + ' .pimcore_block_entry[data-name="' + this.name + '"][key="' + firstNewKey + '"]';
            this.reloadDocument();
        } else {
            let template = this.config['template']['html'];
            for (let p = 0; p < amount; p++) {
                nextKey++;
                let blockHtml = template.replaceAll(':1000000.', ':' + nextKey + '.');
                blockHtml = blockHtml.replaceAll('_1000000_', '_' + nextKey + '_');
                blockHtml = blockHtml.replaceAll('="1000000"', '="' + nextKey + '"');
                blockHtml = blockHtml.replaceAll(', 1000000"', ', ' + nextKey + '"');

                if(!this.elements.length) {
                    Ext.get(this.id).setHtml(blockHtml);
                } else if (this.elements[index-1]) {
                    Ext.get(this.elements[index-1]).insertHtml('afterEnd', blockHtml, true);
                }

                this.config['template']['editables'].forEach(editableDef => {
                    let editable = Ext.clone(editableDef);
                    editable['id'] = editable['id'].replace('_1000000_', '_' + nextKey + '_');
                    editable['name'] = editable['name'].replace(':1000000.', ':' + nextKey + '.');
                    editableManager.addByDefinition(editable);
                });

                this.elements = Ext.get(this.id).query('.pimcore_block_entry[data-name="' + this.name + '"][key]');
            }

            this.refresh();
        }
    },

    removeBlock: function (element) {

        var index = this.getElementIndex(element);

        this.elements.splice(index, 1);
        Ext.get(element).remove();

        if(this.config['reload'] === true) {
            this.reloadDocument();
        } else {
            this.refresh();
        }
    },

    moveBlockDown: function (element) {
        var index = this.getElementIndex(element);
        if (index < (this.elements.length-1)) {
            if(this.config['reload'] === true) {
                var x = this.elements[index];
                var y = this.elements[index + 1];

                this.elements[index + 1] = x;
                this.elements[index] = y;

                this.reloadDocument();
            } else {
                Ext.get(element).insertAfter(this.elements[index+1]);
                this.refresh();
            }
        }
    },

    moveBlockUp: function (element) {
        var index = this.getElementIndex(element);
        if (index > 0) {
            if(this.config['reload'] === true) {
                var x = this.elements[index];
                var y = this.elements[index - 1];

                this.elements[index - 1] = x;
                this.elements[index] = y;

                this.reloadDocument();
            } else {
                Ext.get(element).insertBefore(this.elements[index-1]);
                this.refresh();
            }
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
