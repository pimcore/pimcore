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

pimcore.registerNS("pimcore.object.classes.data.localizedfields");
pimcore.object.classes.data.localizedfields = Class.create(pimcore.object.classes.data.data, {

    type: "localizedfields",
    allowIndex: false,
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
        this.type = "localizedfields";

        initData.name = "localizedfields";
        treeNode.setText("localizedfields");

        this.initData(initData);
        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("localizedfields");
    },

    getGroup: function () {
        return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_localizedfields";
    },

    getLayout: function ($super) {

        this.datax.name = "localizedfields";

        $super();

        this.specificPanel.removeAll();

        this.layout = new Ext.Panel({
            bodyStyle: "padding: 10px;",
            items: [
                {
                    xtype: "form",
                    title: t("general_settings"),
                    bodyStyle: "padding: 10px;",
                    style: "margin: 10px 0 10px 0",
                    labelWidth: 140,
                    items: [
                        {
                            xtype: "textfield",
                            fieldLabel: t("name"),
                            name: "name",
                            enableKeyEvents: true,
                            value: this.datax.name,
                            disabled: true
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("title"),
                            name: "title",
                            value: this.datax.title
                        },
                        {
                            xtype: "combo",
                            fieldLabel: t("region"),
                            name: "region",
                            value: this.datax.region,
                            store: ["","center", "north", "south", "east", "west"],
                            triggerAction: 'all',
                            editable: false
                        },
                        {
                            xtype: "combo",
                            fieldLabel: t("layout"),
                            name: "layout",
                            value: this.datax.layout,
                            store: ["","fit"],
                            triggerAction: 'all',
                            editable: false
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
                        },
                        {
                            xtype: "spinnerfield",
                            fieldLabel: t("maximum_tabs"),
                            name: "maxTabs",
                            value: this.datax.maxTabs
                        }
                    ]
                }
            ]
        });

        this.layout.add({
            xtype: "form",
            labelWidth: 140,
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "spinnerfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
                }
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    },

    getData: function ($super) {
        var data = $super();

        data.name = "localizedfields";

        return data;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    region: source.datax.region,
                    layout: source.datax.layout,
                    width: source.datax.width,
                    height: source.datax.height,
                    maxTabs: source.datax.maxTabs,
                    labelWidth: source.datax.labelWidth
                });
        }
    }
});
