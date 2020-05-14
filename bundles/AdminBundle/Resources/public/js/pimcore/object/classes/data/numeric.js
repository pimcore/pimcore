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

pimcore.registerNS("pimcore.object.classes.data.numeric");
pimcore.object.classes.data.numeric = Class.create(pimcore.object.classes.data.data, {

    type: "numeric",
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
        this.type = "numeric";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("numeric");
    },

    getGroup: function () {
            return "numeric";
    },

    getIconClass: function () {
        return "pimcore_icon_numeric";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {

        specificItems = [
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                value: datax.defaultValue
            },
            {
                xtype: 'textfield',
                width: 600,
                fieldLabel: t("default_value_generator"),
                labelWidth: 140,
                name: 'defaultValueGenerator',
                value: this.datax.defaultValueGenerator
            },
            {
                xtype: "panel",
                bodyStyle: "padding-top: 3px",
                style: "margin-bottom: 10px",
                html:'<span class="object_field_setting_warning">' +t('inherited_default_value_warning')+'</span>'
            }
        ];

        if (!this.isInCustomLayoutEditor()) {
            specificItems = specificItems.concat([
                {
                    xtype: "numberfield",
                    fieldLabel: t("decimal_size"),
                    name: "decimalSize",
                    maxValue: 65,
                    value: datax.decimalSize
                }, {
                    xtype: "numberfield",
                    fieldLabel: t("decimal_precision"),
                    name: "decimalPrecision",
                    maxValue: 30,
                    value: datax.decimalPrecision
                }, {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html: t('decimal_mysql_type_info')
                }, {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html:'<span class="object_field_setting_warning">' +t('decimal_mysql_type_naming_warning')+'</span>'
                }, {
                    xtype: "checkbox",
                    fieldLabel: t("integer"),
                    name: "integer",
                    checked: datax.integer
                }, {
                    xtype: "checkbox",
                    fieldLabel: t("only_unsigned"),
                    name: "unsigned",
                    checked: datax["unsigned"]
                }, {
                    xtype: "numberfield",
                    fieldLabel: t("min_value"),
                    name: "minValue",
                    value: datax.minValue
                },{
                    xtype: "numberfield",
                    fieldLabel: t("max_value"),
                    name: "maxValue",
                    value: datax.maxValue
                }
            ]);
        }
        return specificItems;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    defaultValue: source.datax.defaultValue,
                    integer: source.datax.integer,
                    unsigned: source.datax.unsigned,
                    minValue: source.datax.minValue,
                    maxValue: source.datax.maxValue,
                    decimalSize: source.datax.decimalSize,
                    decimalPrecision: source.datax.decimalPrecision,
                    defaultValueGenerator: source.datax.defaultValueGenerator,
                    unique: source.datax.unique
                });
        }
    },

    supportsUnique: function() {
        return true;
    }

});
