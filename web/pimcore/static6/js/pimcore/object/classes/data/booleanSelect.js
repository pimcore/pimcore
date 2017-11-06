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

pimcore.registerNS("pimcore.object.classes.data.booleanSelect");
pimcore.object.classes.data.booleanSelect = Class.create(pimcore.object.classes.data.data, {

    type: "booleanSelect",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : true,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "booleanSelect";

        this.initData(initData);

        if (typeof this.datax.yesLabel == "undefined") {
            this.datax.yesLabel = "yes";
        }

        if (typeof this.datax.noLabel == "undefined") {
            this.datax.noLabel = "no";
        }

        if (typeof this.datax.emptyLabel == "undefined") {
            this.datax.emptyLabel = "empty";
        }


        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("boolean_select");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_booleanSelect";
    },

    getLayout: function ($super) {

        if(typeof this.datax.options != "object") {
            this.datax.options = [];
        }



        $super();

        this.mandatoryCheckbox.disable();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "textfield",
                fieldLabel: t("yes_label"),
                name: "yesLabel",
                value: this.datax.yesLabel
            },
            {
                xtype: "textfield",
                fieldLabel: t("no_label"),
                name: "noLabel",
                value: this.datax.noLabel
            },
            {
                xtype: "textfield",
                fieldLabel: t("empty_label"),
                name: "emptyLabel",
                value: this.datax.emptyLabel
            }
        ]);

        return this.layout;
    },

    applyData: function ($super) {
        $super();
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
                    yesLabel: source.datax.yesLabel,
                    noLabel: source.datax.noLabel,
                    emptyLabel: source.datax.emptyLabel
                });
        }
    }
});
