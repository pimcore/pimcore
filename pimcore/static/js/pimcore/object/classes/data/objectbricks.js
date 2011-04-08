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

pimcore.registerNS("pimcore.object.classes.data.objectbricks");
pimcore.object.classes.data.objectbricks = Class.create(pimcore.object.classes.data.data, {

    type: "objectbricks",
    allowIndex: false,

    initialize: function (treeNode, initData) {
        this.type = "objectbricks";

        this.initData(initData);
        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("objectbricks");
    },

    getGroup: function () {
            return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_objectbricks";
    },

    getLayout: function ($super) {
        $super();
        
        this.specificPanel.removeAll();

        return this.layout;
    }
    
});
