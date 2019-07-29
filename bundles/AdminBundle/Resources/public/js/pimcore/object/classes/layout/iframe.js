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

pimcore.registerNS("pimcore.object.classes.layout.iframe");
pimcore.object.classes.layout.iframe = Class.create(pimcore.object.classes.layout.layout, {

    type: "iframe",

    initialize: function (treeNode, initData) {
        this.type = "iframe";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("iframe");
    },

    getIconClass: function () {
        return "pimcore_icon_iframe";
    },

    getLayout: function () {

        this.layout = new Ext.Panel({
            title: '<b>' + this.getTypeName() + '</b>',
            bodyStyle: 'padding: 10px;',
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px;",
                    style: "margin: 10px 0 10px 0",
                    items: [
                        {
                            xtype: "textfield",
                            fieldLabel: t("name"),
                            name: "name",
                            enableKeyEvents: true,
                            value: this.datax.name
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("title"),
                            name: "title",
                            value: this.datax.title
                        },
                        {
                            xtype: "numberfield",
                            fieldLabel: t("width"),
                            name: "width",
                            value: this.datax.width
                        },
                        {
                            xtype: "numberfield",
                            fieldLabel: t("height"),
                            name: "height",
                            value: this.datax.height
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("iframe_url"),
                            name: "iframeUrl",
                            width: 800,
                            value: this.datax.iframeUrl
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("rendering_data"),
                            name: "renderingData",
                            width: 800,
                            value: this.datax.renderingData
                        }


                    ]
                }
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    }
});