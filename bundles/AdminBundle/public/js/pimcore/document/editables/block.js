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

pimcore.registerNS("pimcore.document.editables.block");
/**
 * @private
 */
pimcore.document.editables.block = Class.create(pimcore.document.editable, {

    initialize: function($super, id, name, config, data, inherited) {
        $super(id, name, config, data, inherited);

        this.elements = [];
    },

    refresh: function() {
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
                if(!this.elements[i].key) {
                    this.elements[i].key = this.elements[i].getAttribute("key");
                }

                this.refreshControls(this.elements[i], limitReached);
            }
        }
    },

    refreshControls: function (element, limitReached) {
        var plusButton, minusButton, upButton, downButton, plusDiv, minusDiv, upDiv, downDiv, amountDiv, amountBox;

        // re-initialize amount boxes on every refresh
        amountBox = Ext.get(element).query('.pimcore_block_amount[data-name="' + this.name + '"] .pimcore_block_amount_select', false)[0];
        if (typeof amountBox !== 'undefined') {
            amountBox.remove();
        }

        plusButton = Ext.get(element).query('.pimcore_block_plus[data-name="' + this.name + '"] .pimcore_block_button_plus', false)[0];
        if (typeof plusButton !== 'undefined') {
            plusButton.remove();
        }

        if (!limitReached) {
            amountDiv = Ext.get(element).query('.pimcore_block_amount[data-name="' + this.name + '"]')[0];
            if (amountDiv) {
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
            }

            plusDiv = Ext.get(element).query('.pimcore_block_plus[data-name="' + this.name + '"]')[0];
            if (plusDiv) {
                plusButton = new Ext.Button({
                    cls: "pimcore_block_button_plus",
                    hidden: true,
                    iconCls: "pimcore_icon_plus",
                    listeners: {
                        "click": this.addBlock.bind(this, element, amountBox)
                    }
                });
                plusButton.render(plusDiv);
            }
        }

        // minus button
        minusButton = Ext.get(element).query('.pimcore_block_minus[data-name="' + this.name + '"] .pimcore_block_button_minus')[0];
        if (typeof minusButton === 'undefined') {
            minusDiv = Ext.get(element).query('.pimcore_block_minus[data-name="' + this.name + '"]')[0];
            if (minusDiv) {
                minusButton = new Ext.Button({
                    cls: "pimcore_block_button_minus",
                    iconCls: "pimcore_icon_minus",
                    listeners: {
                        "click": this.removeBlock.bind(this, element)
                    }
                });
                minusButton.render(minusDiv);
            }
        }

        // up button
        upButton = Ext.get(element).query('.pimcore_block_up[data-name="' + this.name + '"] .pimcore_block_button_up')[0];
        if (typeof upButton === 'undefined') {
            upDiv = Ext.get(element).query('.pimcore_block_up[data-name="' + this.name + '"]')[0];
            if (upDiv) {
                upButton = new Ext.Button({
                    cls: "pimcore_block_button_up",
                    iconCls: "pimcore_icon_up",
                    listeners: {
                        "click": this.moveBlockUp.bind(this, element)
                    }
                });
                upButton.render(upDiv);
            }
        }

        // down button
        downButton = Ext.get(element).query('.pimcore_block_down[data-name="' + this.name + '"] .pimcore_block_button_down')[0];
        if (typeof downButton === 'undefined') {
            downDiv = Ext.get(element).query('.pimcore_block_down[data-name="' + this.name + '"]')[0];
            if (downDiv) {
                downButton = new Ext.Button({
                    cls: "pimcore_block_button_down",
                    iconCls: "pimcore_icon_down",
                    listeners: {
                        "click": this.moveBlockDown.bind(this, element)
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
        } else {
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

    addBlock : function (element, amountbox, reload = true) {

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
            if(reload) {
                this.reloadDocument();
            }
        } else {
            let template = this.config['template']['html'];
            let elements;
            for (let p = 0; p < amount; p++) {
                elements = Ext.get(this.id).query('.pimcore_block_entry[data-name="' + this.name + '"][key]');
                nextKey++;
                let blockHtml = template;
                blockHtml = blockHtml.replaceAll(new RegExp('"([^"]+):1000000.' + this.getRealName() + '("|:)', 'g'), '"' + this.getName() + '$2');
                blockHtml = blockHtml.replaceAll(new RegExp('"pimcore_editable_([^"]+)_1000000_' + this.getRealName() + '_', 'g'), '"pimcore_editable_' + this.getName().replaceAll(/(:|\.)/g, '_') + '_');
                blockHtml = blockHtml.replaceAll(':1000000.', ':' + nextKey + '.');
                blockHtml = blockHtml.replaceAll('_1000000_', '_' + nextKey + '_');
                blockHtml = blockHtml.replaceAll('="1000000"', '="' + nextKey + '"');
                blockHtml = blockHtml.replaceAll(', 1000000"', ', ' + nextKey + '"');

                if(!elements.length) {
                    Ext.get(this.id).setHtml(blockHtml);
                } else if (elements[index-1]) {
                    Ext.get(elements[index-1]).insertHtml('afterEnd', blockHtml, true);
                }

                this.config['template']['editables'].forEach(editableDef => {
                    let editable = Ext.clone(editableDef);
                    editable['id'] = editable['id'].replace(new RegExp('pimcore_editable_([^"]+)_1000000_' + this.getRealName() + '_'), 'pimcore_editable_' + this.getName().replaceAll(/(:|\.)/g, '_') + '_');
                    editable['id'] = editable['id'].replaceAll('_1000000_', '_' + nextKey + '_');
                    editable['name'] = editable['name'].replace(new RegExp('^([^"]+):1000000.' + this.getRealName() + ':'), this.getName() + ':');
                    editable['name'] = editable['name'].replaceAll(':1000000.', ':' + nextKey + '.');
                    if (editable['config']['blockStateStack']) {
                        let blockStateStack = JSON.parse(editable['config']['blockStateStack']);
                        for (let i = 0; i < blockStateStack.length; i++) {
                            if (blockStateStack[i].indexes) {
                                for (let j = 0; j < blockStateStack[i].indexes.length; j++) {
                                    if (blockStateStack[i].indexes[j] === 1000000) {
                                        blockStateStack[i].indexes[j] = nextKey;
                                    }
                                }
                            }
                        }
                        editable['config']['blockStateStack'] = JSON.stringify(blockStateStack);
                    }
                    editableManager.addByDefinition(editable);
                });
            }

            this.refresh();
        }
    },

    removeBlock: function (element) {

        var index = this.getElementIndex(element);

        // Add a new default block before removing last block.
        if(is_numeric(this.config['default']) && this.elements.length === this.config['default']) {
            this.addBlock(element, null, false); // do not reload document, will happen later here.
        }
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
