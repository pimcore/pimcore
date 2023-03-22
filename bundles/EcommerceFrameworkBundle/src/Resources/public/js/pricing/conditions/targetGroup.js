/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

//Add condition only when personalization bundle is enabled
if (pimcore.bundle.personalization) {
    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    pimcore.bundle.EcommerceFramework.pricing.conditions.conditionTargetGroup = function (panel, data, getName) {
        var niceName = t("bundle_ecommerce_pricing_config_condition_targetgroup");
        if (typeof getName !== "undefined" && getName) {
            return niceName;
        }

        // check params
        if (typeof data === "undefined") {
            data = {};
        }


        this.targetGroupStore = Ext.create('Ext.data.JsonStore', {
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_bundle_personalization_targeting_targetgrouplist')
            },
            fields: ["id", "text"],
            listeners: {
                load: function () {
                    this.targetGroup.setValue(data.targetGroupId);
                }.bind(this)
            }
        });

        this.targetGroup = new Ext.form.ComboBox({
            displayField: 'text',
            valueField: "id",
            name: "targetGroupId",
            fieldLabel: t("bundle_ecommerce_pricing_config_condition_targetgroup"),
            store: this.targetGroupStore,
            editable: false,
            triggerAction: 'all',
            width: 500,
            listeners: {}
        });


        // create item
        var myId = Ext.id();
        var item = new Ext.form.FormPanel({
            id: myId,
            type: 'TargetGroup',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionTargetGroup"),
            items: [
                this.targetGroup,
                {
                    xtype: "numberfield",
                    fieldLabel: t("bundle_ecommerce_pricing_config_condition_targetgroup_threshold"),
                    name: "threshold",
                    width: 200,
                    value: data.threshold
                }
            ]
        });

        return item;
    };
}
