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

pimcore.registerNS("pimcore.object.classes.data.newsletterConfirmed");
pimcore.object.classes.data.newsletterConfirmed = Class.create(pimcore.object.classes.data.data, {

    type: "newsletterConfirmed",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : false,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "newsletterConfirmed";

        if(!initData["name"]) {
            initData = {
                title: t("newsletter_confirmed")
            };
        }

        initData.fieldtype = "newsletterConfirmed";
        initData.datatype = "data";
        initData.name = "newsletterConfirmed";
        initData.noteditable = true;
        treeNode.set("text", "newsletterConfirmed");

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("newsletter_confirmed");
    },

    getGroup: function () {
        return "crm";
    },

    getIconClass: function () {
        return "pimcore_icon_newsletterConfirmed";
    },

    getLayout: function ($super) {
        $super();

        var nameField = this.layout.getComponent("standardSettings").getComponent("name");
        nameField.disable();

        var noteditable  = this.layout.getComponent("standardSettings").getComponent("noteditable");
        noteditable.disable();

        return this.layout;
    }

});
