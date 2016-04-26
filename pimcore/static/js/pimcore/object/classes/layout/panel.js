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