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

pimcore.registerNS("pimcore.object.classes.data.persona");
pimcore.object.classes.data.persona = Class.create(pimcore.object.classes.data.data, {

    type: "persona",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true, 
        objectbrick: true,
        fieldcollection: true,
        localizedfield: false
    },        

    initialize: function (treeNode, initData) {
        this.type = "persona";

        if(!initData["name"]) {
            initData = {
                title: t("persona")
            };
        }

        initData.fieldtype = "persona";
        initData.datatype = "data";
        initData.name = "persona";
        initData.noteditable = false;
        treeNode.setText("persona");

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("persona");
    },

    getGroup: function () {
        return "crm";
    },

    getIconClass: function () {
        return "pimcore_icon_personas";
    },

    getLayout: function ($super) {

        $super();

        var nameField = this.layout.getComponent("standardSettings").getComponent("name");
        nameField.disable();

        return this.layout;
    }

});
