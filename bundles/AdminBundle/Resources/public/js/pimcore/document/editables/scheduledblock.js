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

pimcore.registerNS("pimcore.document.editables.scheduledblock");
pimcore.document.editables.scheduledblock = Class.create(pimcore.document.editables.block, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.elements = [];
        this.config = this.parseConfig(config);

        this.elements = Ext.get(id).query('.pimcore_block_entry[data-name="' + name + '"][key]');

        for (var i = 0; i < this.elements.length; i++) {
                this.elements[i].key = this.elements[i].getAttribute("key");
                this.elements[i].date = this.elements[i].getAttribute("date");

                Ext.get(this.elements[i]).setVisibilityMode(Ext.Element.DISPLAY);
                Ext.get(this.elements[i]).setVisible(false);
        }

        Ext.get(id).on('mouseenter', function (event) {
            Ext.get(id).addCls('pimcore_block_entry_over');
        });

        Ext.get(id).on('mouseleave', function (event) {
            Ext.get(id).removeCls('pimcore_block_entry_over');
        });

        this.renderControls();
    },

    /**
     * generates id for current selected date that is stored in globalmanager
     * in order to jup to the correct date when view is reloaded because new
     * timestamp was added
     *
     * @returns {string}
     */
    getTmpStoreId: function() {
        var documentId = window.editWindow.document.id;
        return "pimcore_scheduled_block_tmp_date_" + documentId + "_" + this.name;
    },

    renderControls: function() {

        var controlDiv = Ext.get(this.id).query('.pimcore_scheduled_block_controls')[0];

        var controlItems = [];

        var initialDate = new Date();
        if(top.pimcore.globalmanager.get(this.getTmpStoreId())) {
            initialDate = top.pimcore.globalmanager.get(this.getTmpStoreId());
            top.pimcore.globalmanager.remove(this.getTmpStoreId());
        }

        this.dateField = new Ext.form.DateField({
            cls: "pimcore_block_field_date",
            value: initialDate,
            region: 'west',
            listeners: {
                'change': function() {
                    this.loadTimestampsForDate();
                }.bind(this)
            }
        });
        controlItems.push(this.dateField);

        this.slider = Ext.create('Ext.pimcore.slider.Milestone', {
            width: '100%',
            region: 'center',
            style: 'padding-left: 10px; padding-right: 10px'
        });

        controlItems.push(this.slider);
        var plusButton = new Ext.Button({
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            region: 'east',
            listeners: {
                "click": function() {
                    this.addBlock(this.dateField.getValue());
                }.bind(this)
            }
        });
        controlItems.push(plusButton);

        var jumpMenuEntries = [];
        for (var i = 0; i < this.elements.length; i++) {
            var element = this.elements[i];

            var timestamp = new Date(element.date * 1000);

            jumpMenuEntries.push({
                text: Ext.Date.format(timestamp, 'Y-m-d H:i'),
                iconCls: 'pimcore_icon_time',
                handler: function(element, timestamp) {
                    this.dateField.setValue(timestamp);
                    this.slider.activateThumbByValue(element.date);
                }.bind(this, element, timestamp)
            });
        }

        if(jumpMenuEntries.length > 0) {
            jumpMenuEntries.push({
                xtype: 'menuseparator'
            });
        }

        jumpMenuEntries.push({
            text: t('scheduled_block_delete_all_in_past'),
            iconCls: 'pimcore_icon_delete',
            handler: function(element, timestamp) {
                Ext.MessageBox.confirm("", t("scheduled_block_really_delete_past"), function (buttonValue) {
                    if (buttonValue == "yes") {
                        this.cleanupTimestamps(false);
                    }
                }.bind(this));
            }.bind(this)
        });

        jumpMenuEntries.push({
            text: t('scheduled_block_delete_all'),
            iconCls: 'pimcore_icon_delete',
            handler: function(element, timestamp) {
                Ext.MessageBox.confirm("", t("scheduled_block_really_delete_all"), function (buttonValue) {
                    if (buttonValue == "yes") {
                        this.cleanupTimestamps(false);
                    }
                }.bind(this));
            }.bind(this)
        });

        var jumpButton = new Ext.Button({
            iconCls: "pimcore_icon_time",
            region: 'east',
            menu: jumpMenuEntries
        });
        controlItems.push(jumpButton);


        var controlBar = new Ext.Panel({
            items: controlItems,
            layout: 'border',
            height: 35,
            border: false,
            style: "margin-bottom: 10px"
        });
        controlBar.render(controlDiv);

        this.loadTimestampsForDate();
    },

    cleanupTimestamps: function(allTimestamps) {

        var currentTimestamp = (new Date()).getTime() / 1000;

        if(allTimestamps) {
            for (var i = 0; i < this.elements.length; i++) {
                var element = this.elements[i];
                var index = this.getElementIndex(element);
                this.elements.splice(index, 1);
                Ext.get(element).remove();
            }
        } else {
            var previousElement = null;
            for (var i = 0; i < this.elements.length; i++) {
                var element = this.elements[i];
                if(previousElement) {
                    var index = this.getElementIndex(previousElement);
                    this.elements.splice(index, 1);
                    Ext.get(previousElement).remove();
                }
                if(element.date < currentTimestamp) {
                    previousElement = element;
                }
            }
        }

        this.reloadDocument();
    },

    loadTimestampsForDate: function() {
        var date = this.dateField.getValue();

        this.slider.initDate(date);

        var timestampFrom = date.getTime() / 1000;
        var timestampTo = timestampFrom + 86399; //plus one day

        var firstElementVisible = false;
        var latestPreviousElement = null;
        for (var i = 0; i < this.elements.length; i++) {

            var element = this.elements[i];

            if(element.date >= timestampFrom && element.date <= timestampTo) {

                var timestampMarker = this.slider.addTimestamp(
                    element.date,
                    element.key,
                    function(element, newValue) {
                        element.date = newValue;
                    }.bind(this, element),
                    this.showElement.bind(this, element),
                    this.removeBlock.bind(this, element)
                );

                if(!firstElementVisible) {
                    this.slider.activateThumb(timestampMarker);
                    firstElementVisible = true;
                }
            } else {
                //remember last element in front of current day - for showing if no element is in current day
                if(element.date < timestampFrom) {
                    if(!latestPreviousElement || latestPreviousElement.date < element.date) {
                        latestPreviousElement = element;
                    }
                }
            }
        }

        if(!firstElementVisible) {
            if(latestPreviousElement) {
                this.showElement(latestPreviousElement, latestPreviousElement.key);
            } else {
                this.hideAllElements();
            }
        }

    },

    hideAllElements: function() {
        for(var i = 0; i < this.elements.length; i++) {
            Ext.get(this.elements[i]).setVisible(false);
        }
    },

    showElement: function(element, key) {
        this.hideAllElements();
        Ext.get(element).setVisible(true);
    },

    createInitalControls: function (amountValues) {
        //nothing to do, but needs to be overwritten
    },

    addBlock : function (date) {
        // get next higher key
        var nextKey = 0;
        var currentKey;

        for (var i = 0; i < this.elements.length; i++) {
            currentKey = intval(this.elements[i].key);
            if (currentKey > nextKey) {
                nextKey = currentKey;
            }
        }

        if(!date) {
            date = new Date();
        }

        nextKey++;

        this.elements.push({key: nextKey, date: (date.getTime()) / 1000});

        this.reloadDocument();

        pimcore.globalmanager.add(this.getTmpStoreId(),  date);

    },

    getValue: function () {
        var data = [];
        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i]) {
                if (this.elements[i].key) {
                    data.push({
                        key: this.elements[i].key,
                        date: this.elements[i].date
                    });
                }
            }
        }

        return data;
    },

    getType: function () {
        return "scheduledblock";
    }
});
