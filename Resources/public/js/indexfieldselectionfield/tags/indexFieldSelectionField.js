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


pimcore.registerNS("pimcore.object.tags.indexFieldSelectionField");
pimcore.object.tags.indexFieldSelectionField = Class.create(pimcore.object.tags.abstract, {

    type: "indexFieldSelectionField",

    initialize: function (data, fieldConfig) {
        if(data) {
            this.data = data;
        } else {
            this.data = "";
        }

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
                load: function(store) {

                    //add values to store, even if they are not in store
                    //needed, becuase on initial load, no tenant is selected, and there might be values selected, that are not in default tenant
                    if(this.firstLoad !== false) {
                        var values = this.data.split(",");
                        for(var i = 0; i < values.length; i++) {
                            if(store.find('key', values[i]) < 0) {
                                var defaultData = {
                                    'key': values[i],
                                    'name': ts(values[i])
                                };
                                store.add(defaultData);
                            }
                        }
                        this.firstLoad = false;

                        if(this.fieldsCombobox) {
                            this.fieldsCombobox.setValue(this.data);
                        }

                    } else {

                        //on subsequent loads, check this.data for only allowed values
                        var allowedValues = [];
                        var originalValues = this.data.split(",");
                        for(var i = 0; i < originalValues.length; i++) {
                            if(store.find('key', originalValues[i]) >= 0) {
                                allowedValues.push(originalValues[i]);
                            }
                        }
                        if(this.fieldsCombobox) {
                            this.fieldsCombobox.setValue(allowedValues.join());
                        }
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
        if (parseInt(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 100;
        }
        if (parseInt(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 100;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            fieldLabel: this.fieldConfig.title,
            itemCls: "object_field hugo",
            queryDelay: 0,
            triggerAction: 'all',
            resizable: true,
            mode: 'local',
            minChars: 1,
            store: this.store,
            displayField: 'name',
            valueField: 'key',
            forceFormValue: true
        };

        if (this.data) {
            conf.value = this.data;
        }

        this.fieldsCombobox = Ext.create('Ext.form.field.Tag', conf);


        if(this.fieldConfig.considerTenants) {
            this.fieldsCombobox.setFieldLabel("");
            var tenantCombobox = new Ext.form.ComboBox({
                triggerAction: "all",
                fieldLabel: this.fieldConfig.title,
                editable: false,
                store: this.tenantStore,
                valueField: 'key',
                displayField: 'name',
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
                items: [
                    tenantCombobox,
                    this.fieldsCombobox
                ],
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


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.fieldsCombobox.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});
