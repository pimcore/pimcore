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

pimcore.registerNS("pimcore.object.classes.data.keyValue");
pimcore.object.classes.data.keyValue = Class.create(pimcore.object.classes.data.data, {

    type: "keyValue",
    allowIndex: false,
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
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
                xtype: "spinnerfield",
                fieldLabel: t("keyvalue_data_maxheight"),
                name: "maxheight",
                value: maxheight
            }
        ]);

        return this.layout;
    },

    getData: function ($super) {
        var data = $super();

        data.name = "keyvaluepairs";

        return data;
    }
});
