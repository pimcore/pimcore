/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.object.classes.data.checkbox");
pimcore.object.classes.data.checkbox = Class.create(pimcore.object.classes.data.data, {

    type: "checkbox",

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
        this.type = "checkbox";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("checkbox");
    },

    getIconClass: function () {
        return "pimcore_icon_checkbox";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "checkbox",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                checked: this.datax.defaultValue,
                disabled: this.isInCustomLayoutEditor()
            }, {
                xtype: "displayfield",
                hideLabel:true,
                html:'<span class="object_field_setting_warning">' +t('default_value_warning')+'</span>'
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
                    defaultValue: source.datax.defaultValue

                });
        }
    }

});
