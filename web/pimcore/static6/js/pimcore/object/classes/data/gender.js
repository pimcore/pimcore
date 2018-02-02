/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        localizedfield: false,
        classificationstore : false,
        block: true,
        encryptedField: true
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
        treeNode.set("text", "gender");

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
