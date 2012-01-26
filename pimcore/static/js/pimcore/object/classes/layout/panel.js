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

pimcore.registerNS("pimcore.object.classes.layout.panel");
pimcore.object.classes.layout.panel = Class.create(pimcore.object.classes.layout.layout, {

    type: "panel",

    initialize: function (treeNode, initData) {
        this.type = "panel";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("panel");
    },

    getIconClass: function () {
        return "pimcore_icon_layout_panel";
    },

    getLayout: function ($super) {
        $super();

        this.layout.add({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "combo",
                    fieldLabel: t("layout"),
                    name: "layout",
                    value: this.datax.layout,
                    store: ["","fit"],
                    triggerAction: 'all',
                    editable: false
                },{
                    xtype: "spinnerfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
                }
            ]
        });

        return this.layout;
    }

});