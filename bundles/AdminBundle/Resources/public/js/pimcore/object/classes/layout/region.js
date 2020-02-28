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

pimcore.registerNS("pimcore.object.classes.layout.region");
pimcore.object.classes.layout.region = Class.create(pimcore.object.classes.layout.layout, {

    type: "region",

    initialize: function (treeNode, initData) {
        this.type = "region";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("region");
    },

    getIconClass: function () {
        return "pimcore_icon_region";
    },

    getLayout: function ($super) {
        $super();

        this.layout.add({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [this.getIconFormElement()]
        });

        return this.layout;
    }
});