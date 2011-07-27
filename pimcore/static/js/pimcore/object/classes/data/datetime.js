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

pimcore.registerNS("pimcore.object.classes.data.datetime");
pimcore.object.classes.data.datetime = Class.create(pimcore.object.classes.data.data, {

    type: "datetime",

    initialize: function (treeNode, initData) {
        this.type = "datetime";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getGroup: function () {
            return "date";
        },

    
    getTypeName: function () {
        return t("datetime");
    },

    getIconClass: function () {
        return "pimcore_icon_date";
    },

    getLayout: function ($super) {

        $super();
        return this.layout;
    }
});
