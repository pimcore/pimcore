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

pimcore.registerNS("pimcore.object.classes.data.select");
pimcore.object.classes.data.select = Class.create(pimcore.object.classes.data.data, {

    type: "select",

    initialize: function (treeNode, initData) {
        this.type = "select";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("select");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_select";
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
            new Ext.ux.form.SuperField({
                allowEdit: true,
                name: "options",
                values:this.datax.options,
                stripeRows:false,
                items: [
                    new Ext.form.TextField({
                        fieldLabel: t("display_name"),
                        name: "key",
                        allowBlank:false,
                        summaryDisplay:true
                    }),
                    new Ext.form.TextField({
                        fieldLabel: t("value"),
                        name: "value",
                        allowBlank:false,
                        summaryDisplay:true
                    })
                ]
            })
        ]);

        return this.layout;
    }
});
