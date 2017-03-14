/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
            proxy: {
                type: 'ajax',
                url: '/plugin/EcommerceFramework/index/get-fields',
                reader: {
                    rootProperty: 'data',
                    idProperty: 'key'
                },
                extraParams: {class_id: fieldConfig.classId, specific_price_field: this.fieldConfig.specificPriceField, show_all_fields: this.fieldConfig.showAllFields }
            },
            fields: ['key','name'],
            listeners: {
                load: function (store) {
                    if(this.fieldsCombobox) {
                        this.fieldsCombobox.setValue(this.data);
                    }
                }.bind(this)
            }
        });


        if(this.fieldConfig.considerTenants) {
            this.tenantStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/plugin/EcommerceFramework/index/get-all-tenants',
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'key'
                    }
                },
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
            this.fieldsCombobox.setFieldLabel("");
            var tenantCombobox = new Ext.form.ComboBox({
                triggerAction: "all",
                fieldLabel: this.fieldConfig.title,
                editable: false,
                store: this.tenantStore,
                valueField: 'key',
                displayField: 'name',
                itemCls: "object_field",
                width: 300,
                listeners: {
                    select: function(combo, record) {
                        this.fieldsCombobox.setValue("");

                        var proxy = this.store.getProxy();
                        proxy.extraParams.tenant = record.data.key;
                        this.store.reload({params: {tenant: record.data.key}});
                    }.bind(this)
                }
            });

            this.component = Ext.create('Ext.form.Panel', {
                layout: {
                    type: 'hbox',
                    align: "middle"
                },
                margin: '0 0 10 0',
                combineErrors: false,

                items: [tenantCombobox, this.fieldsCombobox],
                cls: "object_field",
                isDirty: function() {
                    return tenantCombobox.isDirty() || this.fieldsCombobox.isDirty()
                }.bind(this)
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