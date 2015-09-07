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
        return "pimcore_icon_layout_accordion";
    }

});