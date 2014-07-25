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

pimcore.registerNS("pimcore.object.classes.data.keyValue");
pimcore.object.classes.data.keyValue = Class.create(pimcore.object.classes.data.data, {

    type: "keyValue",
    allowIndex: false,
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false
    },

    initialize: function (treeNode, initData) {
        this.type = "keyValue";


        initData.name = "keyvaluepairs";
        treeNode.setText("keyvaluepairs");

        this.initData(initData);

        this.availableSettingsFields = ["title","tooltip","mandatory","noteditable","invisible","visibleGridView",
            "visibleSearch","index","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("keyValue_datatype");
    },

    getGroup: function () {
        return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_keyValue";
    },

    getLayout: function ($super) {

        this.datax.name = "keyvaluepairs";

        $super();

        var keyWidth = 200;
        var groupWidth = 200;
        var groupDescWidth = 200;
        var valueWidth = 500;
        var descWidth = 200;
        var height = 200;
        var maxheight = 0;
        var multivalent = 0;
        var metavisible = 0;
        var metaWidth = 200;
        var metaVisible = false;

        if (this.datax.keyWidth) {
            keyWidth = this.datax.keyWidth;
        }

        if (this.datax.valueWidth) {
            valueWidth = this.datax.valueWidth;
        }

        if (this.datax.descWidth) {
            descWidth = this.datax.descWidth;
        }

        if (this.datax.groupDescWidth) {
            groupDescWidth = this.datax.groupDescWidth;
        }

        if (this.datax.height) {
            height = this.datax.height;
        }

        if (this.datax.maxheight) {
            maxheight = this.datax.maxheight;
        }

        if (this.datax.multivalent) {
            multivalent = this.datax.multivalent;
        }

        if (this.datax.metaVisible) {
            metaVisible = this.datax.metaVisible;
        }

        if (this.datax.metawidth) {
            metaWidth = this.datax.metawidth;
        }



        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_keywidth"),
                name: "keyWidth",
                value: keyWidth
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_groupwidth"),
                name: "groupWidth",
                value: groupWidth
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_groupdescwidth"),
                name: "groupDescWidth",
                value: groupDescWidth
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_valuewidth"),
                name: "valueWidth",
                value: valueWidth
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_descwidth"),
                name: "descWidth",
                value: descWidth
            },
            {
                xtype: "checkbox",
                fieldLabel: t("keyvalue_data_metavisible"),
                name: "metaVisible",
                checked: this.datax.metaVisible
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_metawidth"),
                name: "metawidth",
                value: metaWidth
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_maxheight"),
                name: "maxheight",
                value: maxheight
            },
            {
                xtype: "checkbox",
                name: "multivalent",
                value: multivalent,
                checked: multivalent,
                disabled: this.isInCustomLayoutEditor(),
                fieldLabel: t("keyvalue_data_multivalent"),
                width: 300
            }

        ]);

        return this.layout;
    },

    getData: function ($super) {
        var data = $super();

        data.name = "keyvaluepairs";

        return data;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    keyWidth: source.datax.keyWidth,
                    valueWidth: source.datax.valueWidth,
                    descWidth: source.datax.descWidth,
                    height: source.datax.height,
                    maxheight: source.datax.maxheight,
                    groupWidth: source.datax.groupWidth,
                    groupDescWidth: source.datax.groupDescWidth,
                    multivalent: source.datax.multivalent,
                    metawidth: source.datax.metawidth,
                    metaVisible: source.datax.metaVisible,
                });
        }
    }
});
