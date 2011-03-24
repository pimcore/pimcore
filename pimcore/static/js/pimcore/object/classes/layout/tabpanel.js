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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.classes.layout.tabpanel");
pimcore.object.classes.layout.tabpanel = Class.create(pimcore.object.classes.layout.layout, {

    type: "tabpanel",

    initialize: function (treeNode, initData) {
        this.type = "tabpanel";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("tabpanel");
    },

    getIconClass: function () {
        return "pimcore_icon_layout_tabpanel";
    }

});