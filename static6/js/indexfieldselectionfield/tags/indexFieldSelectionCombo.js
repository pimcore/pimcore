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

pimcore.registerNS("pimcore.object.tags.indexFieldSelectionCombo");
pimcore.object.tags.indexFieldSelectionCombo = Class.create(pimcore.object.tags.select, {

    type: "indexFieldSelectionCombo",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
        
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            baseParams: {class_id: fieldConfig.classId, specific_price_field: this.fieldConfig.specificPriceField, add_empty: !this.fieldConfig.mandatory, show_all_fields: this.fieldConfig.showAllFields },
            url: '/plugin/OnlineShop/index/get-fields',
            root: 'data',
            fields: ['key', 'name']
        });


        if(this.fieldConfig.considerTenants) {
            this.tenantStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                url: '/plugin/OnlineShop/index/get-all-tenants',
                root: 'data',
                fields: ['key', 'name']
            });
        }

    },

    getLayoutEdit: function () {

        var options = {
            name: this.fieldConfig.name,
            triggerAction: "all",
            editable: false,
            fieldLabel: this.fieldConfig.title,
            store: this.store,
            valueField: 'key',
            displayField: 'name',
            itemCls: "object_field",
            width: 300
        };

        if (this.fieldConfig.width) {
            options.width = this.fieldConfig.width;
        }

        if (typeof this.data == "string" || typeof this.data == "number") {
            options.value = this.data;
        } else {
            options.value = "";
        }

        this.fieldsCombobox = new Ext.form.ComboBox(options);

        if(this.fieldConfig.considerTenants) {
            var tenantCombobox = new Ext.form.ComboBox({
                triggerAction: "all",
                editable: false,
                store: this.tenantStore,
                valueField: 'key',
                displayField: 'name',
                itemCls: "object_field",
                width: 150,
                listeners: {
                    select: function(combo, record) {
                        this.fieldsCombobox.setValue("");

                        this.store.setBaseParam("tenant", record.data.key);
                        this.store.reload({params: {tenant: record.data.key}});
                    }.bind(this)
                }
            });

            this.component = new Ext.form.CompositeField({
                xtype: 'compositefield',
                fieldLabel: this.fieldConfig.title,
                items: [
                    tenantCombobox,
                    this.fieldsCombobox
                ]
            });

        } else {
            this.component = this.fieldsCombobox;
        }

        return this.component;
    },

    getValue: function() {
        return this.fieldsCombobox.getValue();
    }

});