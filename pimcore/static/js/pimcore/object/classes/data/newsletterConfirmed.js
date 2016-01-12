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
