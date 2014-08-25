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

pimcore.registerNS("pimcore.object.classes.data.slider");
pimcore.object.classes.data.slider = Class.create(pimcore.object.classes.data.data, {

    type: "slider",
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
        this.type = "slider";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","noteditable","invisible","visibleGridView",
                                        "visibleSearch","index","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("slider");
    },

    getGroup: function () {
            return "numeric";
    },

    getIconClass: function () {
        return "pimcore_icon_slider";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                decimalPrecision: 0,
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("height"),
                name: "height",
                decimalPrecision: 0,
                value: this.datax.height
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("min_value"),
                name: "minValue",
                value: this.datax.minValue,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("max_value"),
                name: "maxValue",
                value: this.datax.maxValue,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("increment"),
                name: "increment",
                value: this.datax.increment,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("decimalPrecision"),
                name: "decimalPrecision",
                decimalPrecision: 0,
                value: this.datax.decimalPrecision,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "checkbox",
                fieldLabel: t("vertical"),
                name: "vertical",
                checked: this.datax.vertical
            }
        ]);

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
                    height: source.datax.height,
                    minValue: source.datax.minValue,
                    maxValue: source.datax.maxValue,
                    vertical: source.datax.vertical,
                    increment: source.datax.increment,
                    decimalPrecision: source.datax.decimalPrecision
                });
        }
    }

});
