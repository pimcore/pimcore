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
        return "pimcore_icon_fieldset";
    },

    getLayout: function ($super) {
        $super();

        this.layout.add({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "numberfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
                }
            ]
        });

        return this.layout;
    }

});