/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
