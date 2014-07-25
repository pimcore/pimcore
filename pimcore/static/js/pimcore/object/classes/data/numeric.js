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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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
        localizedfield: true
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
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                value: this.datax.defaultValue
            }, {
                xtype: "displayfield",
                hideLabel:true,
                style: "margin-bottom: 10px",
                html:'<span class="object_field_setting_warning">' +t('default_value_warning')+'</span>'
            }
        ]);

        if (!this.isInCustomLayoutEditor()) {
            this.specificPanel.add([
                {
                    xtype: "spinnerfield",
                    fieldLabel: t("decimal_precision"),
                    name: "decimalPrecision",
                    maxValue: 65,
                    value: this.datax.decimalPrecision
                }, {
                    xtype: "displayfield",
                    hideLabel:true,
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
                    xtype: "spinnerfield",
                    fieldLabel: t("min_value"),
                    name: "minValue",
                    value: this.datax.minValue
                },{
                    xtype: "spinnerfield",
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
