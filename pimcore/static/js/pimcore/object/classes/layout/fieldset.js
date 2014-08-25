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

pimcore.registerNS("pimcore.object.classes.layout.fieldset");
pimcore.object.classes.layout.fieldset = Class.create(pimcore.object.classes.layout.layout, {

    type: "fieldset",

    initialize: function (treeNode, initData) {
        this.type = "fieldset";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("fieldset");
    },

    getIconClass: function () {
        return "pimcore_icon_layout_fieldset";
    },

    getLayout: function ($super) {
        $super();

        this.layout.add({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "spinnerfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
                }
            ]
        });

        return this.layout;
    }

});