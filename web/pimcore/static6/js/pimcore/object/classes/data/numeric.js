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
        block: true
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
        this.specificPanel.add([
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                value: this.datax.defaultValue
            }, {
                xtype: "panel",
                bodyStyle: "padding-top: 3px",
                style: "margin-bottom: 10px",
                html:'<span class="object_field_setting_warning">' +t('default_value_warning')+'</span>'
            }
        ]);

        if (!this.isInCustomLayoutEditor()) {
            this.specificPanel.add([
                {
                    xtype: "numberfield",
                    fieldLabel: t("decimal_precision"),
                    name: "decimalPrecision",
                    maxValue: 65,
                    value: this.datax.decimalPrecision
                }, {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html: t('if_specified_decimal_mysql_type_is_used_automatically')
                }, {
                    xtype: "checkbox",
                    fieldLabel: t("integer"),
                    name: "integer",
                    checked: this.datax.integer
                }, {
                    xtype: "checkbox",
                    fieldLabel: t("only_unsigned"),
                    name: "unsigned",
                    checked: this.datax["unsigned"]
                }, {
                    xtype: "numberfield",
                    fieldLabel: t("min_value"),
                    name: "minValue",
                    value: this.datax.minValue
                },{
                    xtype: "numberfield",
                    fieldLabel: t("max_value"),
                    name: "maxValue",
                    value: this.datax.maxValue
                }
            ]);
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
                    width: source.datax.width,
                    defaultValue: source.datax.defaultValue,
                    integer: source.datax.integer,
                    unsigned: source.datax.unsigned,
                    minValue: source.datax.minValue,
                    maxValue: source.datax.maxValue,
                    decimalPrecision: source.datax.decimalPrecision
                });
        }
    }

});
