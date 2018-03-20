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

pimcore.registerNS("pimcore.object.classes.data.consent");
pimcore.object.classes.data.consent = Class.create(pimcore.object.classes.data.data, {

    type: "consent",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false,
        classificationstore : false,
        block: false,
        encryptedField: false
    },

    initialize: function (treeNode, initData) {
        this.type = "consent";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("consent");
    },

    getIconClass: function () {
        return "pimcore_icon_consent";
    },

    getGroup: function () {
        return "crm";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [ {
            xtype: "numberfield",
            fieldLabel: t("width"),
            name: "width",
            value: datax.width
        }
        ];
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }

            Ext.apply(this.datax, {
                width: source.datax.width
            });
        }
    }

});
