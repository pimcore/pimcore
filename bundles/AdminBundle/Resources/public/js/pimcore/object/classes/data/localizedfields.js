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

pimcore.registerNS("pimcore.object.classes.data.localizedfields");
pimcore.object.classes.data.localizedfields = Class.create(pimcore.object.classes.data.data, {

    type: "localizedfields",
    allowIndex: false,
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: false,
        classificationstore : false,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "localizedfields";

        initData = initData || {};

        initData.name = "localizedfields";
        treeNode.set("text", "localizedfields");

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
            items: [
                {
                    xtype: "form",
                    title: '<b>' + t("localizedfields") + "</b>",
                    bodyStyle: 'padding: 10px;',
                    defaults: {
                        labelWidth: 140,
                        width: 300
                    },
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
                            xtype: "checkbox",
                            fieldLabel: t("border"),
                            name: "border",
                            checked: this.datax.border,
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
                        }, {
                            xtype: 'combo',
                            fieldLabel: t('tab_position'),
                            name: 'tabPosition',
                            value: this.datax.tabPosition,
                            store: [['top', t('top')], ['left', t('left')], ['right', t('right')], ['bottom', t('bottom')]]
                        },
                        {
                            xtype: "numberfield",
                            fieldLabel: t("maximum_tabs"),
                            name: "maxTabs",
                            value: this.datax.maxTabs
                        },
                        {
                            xtype: "numberfield",
                            fieldLabel: t("hide_locale_labels_when_tabs_reached"),
                            name: "hideLabelsWhenTabsReached",
                            value: this.datax.hideLabelsWhenTabsReached
                        }
                    ]
                }
            ]
        });

        this.layout.add({
            xtype: "form",
            defaults: {
                labelWidth: 140,
                width: 300
            },
            bodyStyle: "padding: 10px;",
            items: [
                {
                    xtype: "numberfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("provide_split_view"),
                    name: "provideSplitView",
                    checked: this.datax.provideSplitView
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
                    labelWidth: source.datax.labelWidth,
                    border: source.datax.border,
                    tabPosition: source.datax.tabPosition,
                    hideLabelsWhenTabsReached: source.datax.hideLabelsWhenTabsReached,
                    provideSplitView: source.datax.provideSplitView
                });
        }
    }
});
