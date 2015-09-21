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

pimcore.registerNS("pimcore.object.classes.layout.text");
pimcore.object.classes.layout.text = Class.create(pimcore.object.classes.layout.layout, {

    type: "text",

    initialize: function (treeNode, initData) {
        this.type = "text";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("text");
    },

    getIconClass: function () {
        return "pimcore_icon_layout_text";
    },

    getLayout: function ($super) {
        $super();

        this.layout.add({
            xtype: "form",
            title: t("text"),
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "htmleditor",
                    width: 600,
                    height: 300,
                    value: this.datax.html,
                    name: "html",
                    enableSourceEdit: true
                }
            ]
        });

        return this.layout;
    }
});