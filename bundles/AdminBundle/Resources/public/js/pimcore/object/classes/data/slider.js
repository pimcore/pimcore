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
        localizedfield: true,
        classificationstore : true,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "slider";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip", "mandatory", "noteditable","invisible","visibleGridView",
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
        var specificItems = this.getSpecificPanelItems(this.datax, false);
        this.specificPanel.add(specificItems);

        return this.layout;
    },
    
    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                decimalPrecision: 0,
                value: datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("height"),
                name: "height",
                decimalPrecision: 0,
                value: datax.height
            },
            {
                xtype: "numberfield",
                fieldLabel: t("min_value"),
                name: "minValue",
                value: datax.minValue,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "numberfield",
                fieldLabel: t("max_value"),
                name: "maxValue",
                value: datax.maxValue,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "numberfield",
                fieldLabel: t("increment"),
                name: "increment",
                value: datax.increment,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "numberfield",
                fieldLabel: t("decimalPrecision"),
                name: "decimalPrecision",
                decimalPrecision: 0,
                value: datax.decimalPrecision,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "checkbox",
                fieldLabel: t("vertical"),
                name: "vertical",
                checked: datax.vertical
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
