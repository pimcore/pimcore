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

pimcore.registerNS("pimcore.object.classes.data.externalImage");
pimcore.object.classes.data.externalImage = Class.create(pimcore.object.classes.data.data, {

    type: "externalImage",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : false,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "externalImage";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("externalImage");
    },

    getIconClass: function () {
        return "pimcore_icon_externalImage";
    },

    getGroup: function () {
        return "media";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [
            {
                xtype: "numberfield",
                fieldLabel: t("preview_width"),
                name: "previewWidth",
                value: datax.previewWidth
            },
            {
                xtype: "numberfield",
                fieldLabel: t("preview_height"),
                name: "previewHeight",
                value: datax.previewHeight
            },
            ,
            {
                xtype: "numberfield",
                fieldLabel: t("url_width"),
                name: "inputWidth",
                value: datax.inputWidth
            }
        ];
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    previewWidth: source.datax.previewWidth,
                    previewHeight: source.datax.previewHeight,
                    inputWidth: source.datax.inputWidth
                });
        }
    }

});
