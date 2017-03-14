/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


pimcore.registerNS("pimcore.object.classes.data.indexFieldSelectionField");
pimcore.object.classes.data.indexFieldSelectionField = Class.create(pimcore.object.classes.data.data, {
    type: "indexFieldSelectionField",
    allowIndex: false,

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
        this.type = "indexFieldSelectionField";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("indexFieldSelectionFieldMulti");
    },

    getGroup: function () {
            return "ecommerce";
    },


    getIconClass: function () {
        return "pimcore_icon_indexFieldSelectionField";
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
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },
            {
                xtype: "checkbox",
                fieldLabel: t("specificPriceField"),
                name: "specificPriceField",
                checked: this.datax.specificPriceField
            },
            {
                xtype: "checkbox",
                fieldLabel: t("showAllFields"),
                name: "showAllFields",
                checked: this.datax.showAllFields
            },
            {
                xtype: "checkbox",
                fieldLabel: t("considerTenants"),
                name: "considerTenants",
                checked: this.datax.considerTenants
            }
        ]);

        return this.layout;
    }
});
