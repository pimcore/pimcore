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

pimcore.registerNS("pimcore.object.classes.data.hotspotimage");
pimcore.object.classes.data.hotspotimage = Class.create(pimcore.object.classes.data.image, {

    type: "hotspotimage",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "hotspotimage";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
                                        "visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("imageadvanced");
    },

    getIconClass: function () {
        return "pimcore_icon_hotspotimage";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.add({
            xtype: "fieldset",
            title: t("crop"),
            style: "margin-top: 10px;",
            items: [{
                xtype: "spinnerfield",
                fieldLabel: t("ratio") + " X",
                name: "ratioX",
                value: this.datax.ratioX
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("ratio") + " Y",
                name: "ratioY",
                value: this.datax.ratioY
            }]
        });

        return this.layout;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    height: source.datax.height,
                    uploadPath: source.datax.uploadPath,
                    ratioX: source.datax.ratioX,
                    ratioY: source.datax.ratioY
                });
        }
    }
});
