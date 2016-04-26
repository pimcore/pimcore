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
        return "pimcore_icon_button";
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
							xtype: "textfield",
							fieldLabel: t("icon"),
							name: "icon",
							value: this.datax.icon,
							enableKeyEvents: true,
							listeners: {
								"keyup": function (el) {
									el.inputEl.applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
								},
								"afterrender": function (el) {
									el.inputEl.applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
								}
							},
                            width: 600
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
                        }
                    ]
                }
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    }
});