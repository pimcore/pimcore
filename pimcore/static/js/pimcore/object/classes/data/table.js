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

pimcore.registerNS("pimcore.object.classes.data.table");
pimcore.object.classes.data.table = Class.create(pimcore.object.classes.data.data, {

    type: "table",
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
        this.type = "table";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
                                        "visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
            return "structured";
    },

    getTypeName: function () {
        return t("table");
    },

    getIconClass: function () {
        return "pimcore_icon_table";
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
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("rows"),
                name: "rows",
                value: this.datax.rows,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("cols"),
                name: "cols",
                value: this.datax.cols,
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "textarea",
                fieldLabel: t("data"),
                name: "data",
                width: 300,
                height: 300,
                value: this.datax.data,
                disabled: this.isInCustomLayoutEditor()
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
                    height: source.datax.height,
                    cols: source.datax.cols,
                    rows: source.datax.rows,
                    data: source.datax.data
                });
        }
    }

});
