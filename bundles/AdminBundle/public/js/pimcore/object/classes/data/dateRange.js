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

pimcore.registerNS('pimcore.object.classes.data.dateRange');

pimcore.object.classes.data.dateRange = Class.create(pimcore.object.classes.data.data, {
    type: 'dateRange',

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : false,
        block: true,
        encryptedField: true,
    },

    initialize: function (treeNode, initData) {
        this.type = 'dateRange';
        this.initData(initData);
        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t('date_range');
    },

    getGroup: function () {
        return 'date';
    },

    getIconClass: function () {
        return 'pimcore_icon_dateRange';
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();
        const specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        let specificItems = [
            {
                xtype: 'textfield',
                fieldLabel: t('width'),
                name: 'width',
                value: datax.width,
            },
            {
                xtype: 'displayfield',
                hideLabel: true,
                value: t('width_explanation'),
            },
        ];

        if (!inEncryptedField) {
            const columnTypeField = new Ext.form.ComboBox({
                name: 'columnType',
                mode: 'local',
                autoSelect: true,
                forceSelection: true,
                editable: false,
                fieldLabel: t('column_type'),
                value: datax.columnType !== 'bigint(20)' && datax.columnType !== 'date' ? 'bigint(20)' : datax.columnType,
                store: new Ext.data.ArrayStore({
                    fields: [
                        'id',
                        'label',
                    ],
                    data: [['bigint(20)', 'BIGINT'], ['date', 'DATE']],
                }),
                triggerAction: 'all',
                valueField: 'id',
                displayField: 'label',
            });

            specificItems.push(columnTypeField);
        }

        return specificItems;
    },

    applyData: function ($super) {
        $super();
        this.datax.queryColumnType = this.datax.columnType;
    },

    applySpecialData: function (source) {
        if (!source.datax) {
            return;
        }

        if (!this.datax) {
            this.datax =  {};
        }

        Ext.apply(this.datax, { width: source.datax.width });
    },
});
