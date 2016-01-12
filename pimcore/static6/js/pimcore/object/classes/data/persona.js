/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
