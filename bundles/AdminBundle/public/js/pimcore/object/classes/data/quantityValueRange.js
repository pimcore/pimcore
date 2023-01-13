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

pimcore.registerNS('pimcore.object.classes.data.quantityValueRange');
/**
 * @private
 */
pimcore.object.classes.data.quantityValueRange = Class.create(pimcore.object.classes.data.data, {
    type: 'quantityValueRange',

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: false,
        block: true,
    },

    initialize: function (treeNode, initData) {
        this.type = 'quantityValueRange';
        this.initData(initData);
        this.treeNode = treeNode;
        this.store = pimcore.helpers.quantityValue.getClassDefinitionStore();
    },

    getTypeName: function () {
        return t('quantityValueRange_field');
    },

    getGroup: function () {
        return 'numeric';
    },

    getIconClass: function () {
        return 'pimcore_icon_quantityValueRange';
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: 'textfield',
                name: 'width',
                fieldLabel: t('width'),
                value: this.datax.width,
            },
            {
                xtype: 'textfield',
                fieldLabel: t('unit_width'),
                name: 'unitWidth',
                value: this.datax.unitWidth,
            },
            {
                xtype: 'displayfield',
                hideLabel: true,
                value: t('width_explanation'),
            },
            {
                xtype: 'numberfield',
                fieldLabel: t('decimal_precision'),
                name: 'decimalPrecision',
                maxValue: 65,
                value: this.datax.decimalPrecision,
            },
            {
                xtype: 'combobox',
                name: 'defaultUnit',
                fieldLabel: t('default_unit'),
                triggerAction: 'all',
                editable: true,
                typeAhead: true,
                selectOnFocus: true,
                store: this.store,
                value: this.datax.defaultUnit,
                displayField: 'abbreviation',
                valueField: 'id',
                width: 275,
            },
            {
                xtype: 'multiselect',
                name: 'validUnits',
                fieldLabel: t('valid_quantityValue_units'),
                queryDelay: 0,
                triggerAction: 'all',
                resizable: false,
                width: 600,
                typeAhead: true,
                value: this.datax.validUnits,
                store: this.store,
                displayField: 'abbreviation',
                valueField: 'id',
            },
            {
                xtype: 'checkbox',
                name: 'autoConvert',
                fieldLabel: t('auto_convert'),
                checked: this.datax.autoConvert,
            },
        ]);

        return this.layout;
    },

    applySpecialData: function(source) {
        if (!source.datax) {
            return;
        }

        if (!this.datax) {
            this.datax =  {};
        }

        Ext.apply(this.datax, {
            width: source.datax.width,
            unitWidth: source.datax.unitWidth,
            decimalPrecision: source.datax.decimalPrecision,
            defaultUnit: source.datax.defaultUnit,
            validUnits : source.datax.validUnits,
            autoConvert: source.datax.autoConvert,
        });
    },
});
