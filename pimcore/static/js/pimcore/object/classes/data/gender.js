/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.object.classes.data.gender");
pimcore.object.classes.data.gender = Class.create(pimcore.object.classes.data.data, {

    type: "gender",
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
        this.type = "gender";

        if(!initData["name"]) {
            initData = {
                title: t("gender")
            };
        }

        initData.fieldtype = "gender";
        initData.datatype = "data";
        initData.name = "gender";
        treeNode.setText("gender");

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("gender");
    },

    getGroup: function () {
        return "crm";
    },

    getIconClass: function () {
        return "pimcore_icon_gender";
    },

    getLayout: function ($super) {

        $super();

        var nameField = this.layout.getComponent("standardSettings").getComponent("name");
        nameField.disable();

        this.specificPanel.removeAll();
        return this.layout;
    }
});
