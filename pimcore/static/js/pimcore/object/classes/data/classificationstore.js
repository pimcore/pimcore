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

pimcore.registerNS("pimcore.object.classes.data.classificationstore");
pimcore.object.classes.data.classificationstore = Class.create(pimcore.object.classes.data.data, {

    type: "classificationstore",
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
        this.type = "classificationstore";

        this.initData(initData);
        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("classificationstore");
    },

    getGroup: function () {
        return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_classificationstore";
    },

    getLayout: function ($super) {

        //this.datax.name = "classificationstore";

        $super();

        this.specificPanel.removeAll();

        this.specificPanel.add({
                    xtype: "spinnerfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
        });

        this.specificPanel.add({
            xtype: "checkbox",
            name: "localized",
            fieldLabel: t("localized"),
            checked: this.datax.localized

        });

        this.specificPanel.add({
            xtype: "textarea",
            name: "allowedGroupIds",
            width: 500,
            height: 150,
            fieldLabel: t("allowed_group_ids"),
            value: this.datax.allowedGroupIds
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    },

    getData: function ($super) {
        var data = $super();

        //data.name = "localizedfields";

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
