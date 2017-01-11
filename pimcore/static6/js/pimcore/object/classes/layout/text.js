/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        return "pimcore_icon_text";
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
                    xtype: "textfield",
                    fieldLabel: t("rendering_class"),
                    value: this.datax.renderingClass,
                    width: 600,
                    name: "renderingClass"
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("rendering_data"),
                    width: 600,
                    value: this.datax.renderingData,
                    name: "renderingData"
                },
                {
                    xtype: "htmleditor",
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