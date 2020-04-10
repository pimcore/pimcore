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

pimcore.registerNS("pimcore.object.classes.data.checkbox");
pimcore.object.classes.data.checkbox = Class.create(pimcore.object.classes.data.data, {

    type: "checkbox",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : true,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "checkbox";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("checkbox");
    },

    getIconClass: function () {
        return "pimcore_icon_checkbox";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {

        var defaultValueData = [["empty", t("null")], [0, t("false")], [1, t("true")]];

        var defaultField = new Ext.form.ComboBox({
            mode: 'local',
            autoSelect: true,
            forceSelection: true,
            editable: false,
            fieldLabel: t("default_value"),
            name: "defaultValue",
            value: datax.defaultValue === null ? "empty" : datax.defaultValue,
            store: new Ext.data.ArrayStore({
                fields: [
                    'id',
                    'label'
                ],
                data: defaultValueData
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label',
            disabled: this.isInCustomLayoutEditor()
        });

        return [
            defaultField,
            {
                xtype: 'textfield',
                width: 600,
                fieldLabel: t("default_value_generator"),
                labelWidth: 140,
                name: 'defaultValueGenerator',
                value: datax.defaultValueGenerator
            },
            {
            xtype: "displayfield",
            hideLabel:true,
            html:'<span class="object_field_setting_warning">' +t('default_value_warning')+'</span>'
        }
        ];
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    defaultValue: source.datax.defaultValue,
                    defaultValueGenerator: source.datax.defaultValueGenerator
                });
        }
    }

});
