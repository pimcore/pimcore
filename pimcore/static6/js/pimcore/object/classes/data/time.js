/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.classes.data.time");
pimcore.object.classes.data.time = Class.create(pimcore.object.classes.data.data, {

    type: "time",
    dateFormat: "H:i",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : true,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "time";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    formatTime: function(value) {
        return Ext.Date.format(value, this.dateFormat);
    },

    getLayout: function ($super) {

        $super();
        this.specificPanel.removeAll();
        var self = this;

        if (!this.isInCustomLayoutEditor()) {

            var minmaxSet;
            var onMinMaxValueChange = function() {

                var minValueSelector = minmaxSet.getComponent('minTime'),
                    maxValueSelector = minmaxSet.getComponent('maxTime'),
                    minValue = (minValueSelector.getValue()) ? self.formatTime(minValueSelector.getValue()) : null,
                    maxValue = (maxValueSelector.getValue()) ? self.formatTime(maxValueSelector.getValue()) : null;

                minValueSelector.setMaxValue(maxValue);
                maxValueSelector.setMinValue(minValue);
            };

            minmaxSet = new Ext.form.FieldSet({
                xtype: 'fieldset',
                style: 'margin-top:10px',
                title: t('min_max_times'),
                items: [{
                    xtype: 'timefield',
                    itemId: 'minTime',
                    fieldLabel: t('min_value'),
                    format: self.dateFormat,
                    editable: false,
                    width: 200,
                    value: self.datax.minValue,
                    componentCls: "object_field",
                    name: 'minValue',
                    listeners: {
                        change: onMinMaxValueChange
                    }
                },{
                    xtype: 'timefield',
                    itemId: 'maxTime',
                    fieldLabel: t('max_value'),
                    format: self.dateFormat,
                    editable: false,
                    width: 200,
                    value: self.datax.maxValue,
                    componentCls: "object_field",
                    name: 'maxValue',
                    listeners: {
                        change: onMinMaxValueChange
                    }
                },{
                    xtype: 'button',
                    text: t('reset'),
                    handler: function() {
                        minmaxSet.getComponent('minTime').reset();
                        minmaxSet.getComponent('maxTime').reset();
                    }
                }]
            });

            this.specificPanel.add(minmaxSet);
            //init the values
            onMinMaxValueChange();
        }



        return this.layout;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    minValue: source.datax.minValue,
                    maxValue: source.datax.maxValue
                });
        }
    },

    getTypeName: function () {
        return t("time");
    },

    getGroup: function () {
            return "date";
    },

    getIconClass: function () {
        return "pimcore_icon_time";
    }

});
