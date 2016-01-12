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

pimcore.registerNS("pimcore.object.classes.data.user");
pimcore.object.classes.data.user = Class.create(pimcore.object.classes.data.data, {

    type: "user",
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
        this.type = "user";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("user");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_user";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        return this.layout;
    }

});
