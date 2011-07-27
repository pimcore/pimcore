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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.classes.data.numeric");
pimcore.object.classes.data.numeric = Class.create(pimcore.object.classes.data.data, {

    type: "numeric",

    initialize: function (treeNode, initData) {
        this.type = "numeric";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("numeric");
    },

    getGroup: function () {
            return "numeric";
    },

    getIconClass: function () {
        return "pimcore_icon_numeric";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                value: this.datax.defaultValue
            }
        ]);

        return this.layout;
    }

});
