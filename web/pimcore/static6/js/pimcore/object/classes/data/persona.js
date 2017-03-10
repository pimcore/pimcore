/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        localizedfield: false,
        classificationstore : false,
        block: true
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
        treeNode.set("text", "persona");

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
