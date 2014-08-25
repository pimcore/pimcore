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

pimcore.registerNS("pimcore.object.classes.data.firstname");
pimcore.object.classes.data.firstname = Class.create(pimcore.object.classes.data.data, {

    type: "input",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: false
    },

    initialize: function (treeNode, initData) {
        this.type = "firstname";

        if(!initData["name"]) {
            initData = {
                title: t("firstname")
            };
        }

        initData.fieldtype = "firstname";
        initData.datatype = "data";
        initData.name = "firstname";
        treeNode.setText("firstname");

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("firstname");
    },

    getGroup: function () {
            return "crm";
    },

    getIconClass: function () {
        return "pimcore_icon_firstname";
    },

    getLayout: function ($super) {

        $super();

        var nameField = this.layout.getComponent("standardSettings").getComponent("name");
        nameField.disable();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },{
                xtype: "spinnerfield",
                fieldLabel: t("columnlength"),
                name: "columnLength",
                value: this.datax.columnLength
            }
        ]);

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
                    columnLength: source.datax.columnLength
                });
        }
    }

});
