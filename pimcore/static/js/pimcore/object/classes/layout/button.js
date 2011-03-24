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

pimcore.registerNS("pimcore.object.classes.layout.button");
pimcore.object.classes.layout.button = Class.create(pimcore.object.classes.layout.layout, {

    type: "button",

    initialize: function (treeNode, initData) {
        this.type = "button";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("button");
    },

    getIconClass: function () {
        return "pimcore_icon_layout_button";
    },

    getLayout: function () {

        this.layout = new Ext.Panel({
            bodyStyle: "padding: 10px;",
            items: [
                {
                    xtype: "form",
                    title: t("general_settings"),
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
                            fieldLabel: t("text"),
                            name: "text",
                            value: this.datax.text
                        },
                        {
                            xtype: "textarea",
                            width: 400,
                            height: 300,
                            emptyText: '(function () {  alert("This is just an example ;-)")  }) ',
                            fieldLabel: t("handler"),
                            name: "handler",
                            value: this.datax.handler
                        },
                        {
                            xtype: "spinnerfield",
                            fieldLabel: t("width"),
                            name: "width",
                            value: this.datax.width
                        },
                        {
                            xtype: "spinnerfield",
                            fieldLabel: t("height"),
                            name: "height",
                            value: this.datax.height
                        }
                    ]
                }
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    }
});