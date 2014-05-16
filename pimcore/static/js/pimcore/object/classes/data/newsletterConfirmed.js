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
        localizedfield: true
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
        treeNode.setText("newsletterConfirmed");

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
