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

pimcore.registerNS("pimcore.object.classes.layout.accordion");
pimcore.object.classes.layout.accordion = Class.create(pimcore.object.classes.layout.layout, {

    type: "accordion",

    initialize: function (treeNode, initData) {
        this.type = "accordion";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("accordion");
    },

    getIconClass: function () {
        return "pimcore_icon_accordion";
    }

});