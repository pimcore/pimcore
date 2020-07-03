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
        localizedfield: true,
        classificationstore: false,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "hotspotimage";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name", "title", "tooltip", "mandatory", "noteditable", "invisible",
            "visibleGridView", "visibleSearch", "style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("imageadvanced");
    },

    getIconClass: function () {
        return "pimcore_icon_hotspotimage";
    },

    getGroup: function () {
        return "media";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.add({
            xtype: "fieldset",
            title: t("crop"),
            style: "margin-top: 10px;",
            items: [{
                xtype: "numberfield",
                fieldLabel: t("ratio") + " X",
                name: "ratioX",
                value: this.datax.ratioX
            },
                {
                    xtype: "numberfield",
                    fieldLabel: t("ratio") + " Y",
                    name: "ratioY",
                    value: this.datax.ratioY
                },
                {
                    xtype: "textarea",
                    name: "predefinedDataTemplates",
                    height: 300,
                    width: "100%",
                    value: this.datax.predefinedDataTemplates,
                    validator: function (value) {
                        if(Ext.isString(value) && value.length > 3)
                        try {
                            Ext.decode(value);
                            return true;
                        } catch (e) {
                            return false;
                        }
                    },
                    fieldLabel: t("predefined_hotspot_data_templates")
                }
            ]
        });

        return this.layout;
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    height: source.datax.height,
                    uploadPath: source.datax.uploadPath,
                    ratioX: source.datax.ratioX,
                    ratioY: source.datax.ratioY,
                    predefinedDataTemplates: source.datax.predefinedDataTemplates
                });
        }
    }
});
