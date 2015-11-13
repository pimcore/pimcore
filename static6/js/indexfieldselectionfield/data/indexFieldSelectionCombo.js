/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


pimcore.registerNS("pimcore.object.classes.data.indexFieldSelectionCombo");
pimcore.object.classes.data.indexFieldSelectionCombo = Class.create(pimcore.object.classes.data.data, {

    type: "indexFieldSelectionCombo",
    allowIndex: true,

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
        this.type = "indexFieldSelectionCombo";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("indexFieldSelectionCombo");
    },

    getGroup: function () {
        return "ecommerce";
    },

    getIconClass: function () {
        return "pimcore_icon_indexFieldSelectionCombo";
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
