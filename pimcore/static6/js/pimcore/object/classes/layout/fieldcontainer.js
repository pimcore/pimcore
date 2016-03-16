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

pimcore.registerNS("pimcore.object.classes.layout.fieldcontainer");
pimcore.object.classes.layout.fieldcontainer = Class.create(pimcore.object.classes.layout.layout, {

    type: "fieldcontainer",

    initialize: function (treeNode, initData) {
        this.type = "fieldcontainer";

        this.initData(initData);
        this.datax.name = t("fieldcontainer");

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("fieldcontainer");
    },

    getIconClass: function () {
        return "pimcore_icon_fieldcontainer";
    },

    getLayout: function ($super) {
        $super();

        var layouts = Ext.create('Ext.data.Store', {
            fields: ['abbr', 'name'],
            data: [
                {"abbr": "", "name": "vbox"},
                {"abbr": "hbox", "name": "hbox"}
            ]
        });

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
                    valueField: 'abbr',
                }
            ]
        });

        return this.layout;
    }

});