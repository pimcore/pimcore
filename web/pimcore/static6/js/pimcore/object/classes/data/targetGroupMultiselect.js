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

pimcore.registerNS("pimcore.object.classes.data.targetGroupMultiselect");
pimcore.object.classes.data.targetGroupMultiselect = Class.create(pimcore.object.classes.data.multiselect, {

    type: "targetGroupMultiselect",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: false,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "targetGroupMultiselect";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "crm";
    },

    getTypeName: function () {
        return t("target_group_multiselect");
    },

    getIconClass: function () {
        return "pimcore_icon_targetGroupMultiselect";
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax, false);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("height"),
                name: "height",
                value: datax.height
            }
        ];
    },



    applyData: function ($super) {
        $super();
        delete this.datax.options;
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }

            Ext.apply(this.datax, {
                options: source.datax.options,
                width: source.datax.width,
                height: source.datax.height
            });
        }
    }
});
