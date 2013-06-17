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

pimcore.registerNS("pimcore.object.classes.data.geobounds");
pimcore.object.classes.data.geobounds = Class.create(pimcore.object.classes.data.data, {

    type: "geobounds",
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
        this.type = "geobounds";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","mandatory","noteditable","invisible","index","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("geobounds");
    },

    getGroup: function () {
            return "geo";
    },

    getIconClass: function () {
        return "pimcore_icon_geobounds";
    },

    getLayout: function ($super) {

        $super();


        return this.layout;
    }

});
