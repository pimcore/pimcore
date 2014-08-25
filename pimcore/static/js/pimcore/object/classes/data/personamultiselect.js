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

pimcore.registerNS("pimcore.object.classes.data.personamultiselect");
pimcore.object.classes.data.personamultiselect = Class.create(pimcore.object.classes.data.multiselect, {

    type: "personamultiselect",
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
        this.type = "personamultiselect";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "crm";
    },

    getTypeName: function () {
        return t("personamultiselect");
    },

    getIconClass: function () {
        return "pimcore_icon_persona";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            }
        ]);

        return this.layout;
    },

    applyData: function ($super) {
        $super();
        delete this.datax.options;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    options: source.datax.options,
                    width: source.datax.width,
                    height: source.datax.height
                });
        }
    }

});
