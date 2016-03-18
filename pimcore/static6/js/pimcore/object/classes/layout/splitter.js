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

pimcore.registerNS("pimcore.object.classes.layout.splitter");
pimcore.object.classes.layout.splitter = Class.create(pimcore.object.classes.layout.layout, {

    type: "splitter",

    initialize: function (treeNode, initData) {
        this.type = "splitter";

        this.initData(initData);
        this.datax.name = t("splitter");

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("splitter");
    },

    getIconClass: function () {
        return "pimcore_icon_splitter";
    },

    getLayout: function ($super) {
        $super();
        return this.layout;
    }

});