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
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false
    },

    initialize: function (treeNode, initData) {
        this.type = "objectbricks";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","invisible","style"];

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
    },

    isValid: function ($super) {

        if(!$super()) {
            return false;
        }

        // underscore "_" ist not allowed!
        // reason: the backend creates a class with the name of this field, if it contains an _ the autoloader isn't able to load this file
        var data = this.getData();
        if(data.name.match(/[_]+/)) {
            return false;
        }

        return true;
    }
    
});
