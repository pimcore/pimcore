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

pimcore.registerNS("pimcore.object.classes.layout.fieldcontainer");
pimcore.object.classes.layout.fieldcontainer = Class.create(pimcore.object.classes.layout.layout, {

    type: "fieldcontainer",

    initialize: function (treeNode, initData) {
        this.type = "fieldcontainer";

        if (!initData) {
            initData = {
                datatype: "layout",
                fieldtype: this.getType(),
                name: t("fieldcontainer")
            };
        }

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("fieldcontainer");
    },

    supportsTitle: function() {
        return false;
    },

    getIconClass: function () {
        return "pimcore_icon_fieldcontainer";
    },

    getLayout: function ($super) {
        $super();

        var layouts = Ext.create('Ext.data.Store', {
            fields: ['name'],
            data: [
                {"name": "vbox"},
                {"name": "hbox"}
            ]
        });

        if (!this.datax.layout) {
            this.datax.layout = "hbox";
        }

        this.layout.add({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "textfield",
                    name: "fieldLabel",
                    fieldLabel: t("label"),
                    value: this.datax.fieldLabel
                },
                {
                    xtype: "numberfield",
                    name: "labelWidth",
                    fieldLabel: t("label_width"),
                    value: this.datax.labelWidth
                },
                {
                    xtype: "combo",
                    fieldLabel: t("layout"),
                    name: "layout",
                    value: this.datax.layout,
                    store: layouts,
                    triggerAction: 'all',
                    editable: false,
                    displayField: 'name',
                    valueField: 'name'
                }
            ]
        });

        return this.layout;
    }

});