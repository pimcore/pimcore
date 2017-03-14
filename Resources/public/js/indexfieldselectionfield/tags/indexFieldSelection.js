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


pimcore.registerNS("pimcore.object.tags.indexFieldSelection");
pimcore.object.tags.indexFieldSelection = Class.create(pimcore.object.tags.select, {

    type: "indexFieldSelection",

    initialize: function (data, fieldConfig) {
        if(data) {
            this.data = data;
        } else {
            this.data = {};
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
                extraParams: {class_id: fieldConfig.classId, add_empty: !this.fieldConfig.mandatory, filtergroup: this.fieldConfig.filterGroups }
            },
            fields: ['key', 'name']
        });

        if(this.fieldConfig.multiPreSelect == 'remote_single' || this.fieldConfig.multiPreSelect == 'remote_multi') {
            this.preSelectStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/plugin/EcommerceFramework/index/get-values-for-filter-field',
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'key'
                    },
                    extraParams: {
                        tenant: this.data ? this.data.tenant : "",
                        field: this.data ? this.data.field : ""
                    }
                },
                listeners: {
                    load: function(store) {
                        if(this.data) {
                            if(this.preSelectCombobox.rendered) {
                                this.preSelectCombobox.setValue(this.data.preSelect);
                            } else {
                                this.preSelectCombobox.addListener("afterRender", function() {
                                    this.preSelectCombobox.setValue(this.data.preSelect);
                                }.bind(this));
                            }
                        }
                    }.bind(this)
                },
                fields: ['key', 'value']

            });
        } else if(this.fieldConfig.multiPreSelect == 'local_single' || this.fieldConfig.multiPreSelect == 'local_multi') {
            this.preSelectStore = new Ext.data.JsonStore({
                autoDestroy: true,
                data: this.fieldConfig.predefinedPreSelectOptions,
                proxy: {
                    type: 'memory'
                },
                fields: ['key', 'value']
            });
        }

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
                listeners: {
                    load: function(store) {
                        if(this.data) {
                            if(this.tenantCombobox.rendered) {
                                this.tenantCombobox.setValue(this.data.tenant);
                            } else {
                                this.tenantCombobox.addListener("afterRender", function() {
                                    this.tenantCombobox.setValue(this.data.tenant);
                                }.bind(this));
                            }
                        }
                    }.bind(this)
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
            listeners: {
                select: function(combo, record) {
                    if(this.data && this.data.preSelect) {
                        this.data.preSelect = "";
                    }

                    if(this.fieldConfig.multiPreSelect == 'remote_single' || this.fieldConfig.multiPreSelect == 'remote_multi') {
                        var proxy = this.preSelectStore.getProxy();
                        proxy.extraParams.field = record.data.key;
                        var params = {field: record.data.key};
                        if(this.tenantCombobox) {
                            proxy.extraParams.tenant = this.tenantCombobox.getValue();
                            params.tenant = this.tenantCombobox.getValue();
                        }
                        this.preSelectStore.reload({params: params});
                    }
                }.bind(this)
            },
            width: 300
        };

        if (this.fieldConfig.width) {
            options.width = this.fieldConfig.width;
        }

        if(this.data) {
            options.value = this.data.field;
        }

        this.fieldsCombobox = new Ext.form.ComboBox(options);

        var panel = new Ext.form.FormPanel({
            border: false
        });

        if(this.fieldConfig.considerTenants) {
            this.fieldsCombobox.setFieldLabel("");
            this.tenantCombobox = new Ext.form.ComboBox({
                triggerAction: "all",
                data: (this.data ? this.data.tenant : ""),
                editable: false,
                store: this.tenantStore,
                fieldLabel: this.fieldConfig.title,
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

            panel.add(Ext.create('Ext.form.Panel', {
                layout: {
                    type: 'hbox',
                    align: "middle"
                },
                margin: '0 0 10 0',
                combineErrors: false,
                cls: "object_field",
                isDirty: function() {
                    return this.tenantCombobox.isDirty() || this.fieldsCombobox.isDirty()
                }.bind(this),
                items: [
                    this.tenantCombobox,
                    this.fieldsCombobox
                ]
            }));

        } else {
            panel.add(this.fieldsCombobox);
        }

        if(this.fieldConfig.multiPreSelect == 'remote_multi' || this.fieldConfig.multiPreSelect == 'local_multi') {
            this.preSelectCombobox = new Ext.ux.form.MultiSelect({
                triggerAction: "all",
                fieldLabel: t("preSelect"),
                editable: false,
                name: "preSelect",
                store: this.preSelectStore,
                valueField: 'key',
                displayField: 'value',
                itemCls: "object_field",
                height: 300,
                width: (this.fieldConfig.width ? this.fieldConfig.width : 300) + (this.fieldConfig.considerTenants ? 300 : 0)
            });

            panel.add(this.preSelectCombobox);
        } else if(this.fieldConfig.multiPreSelect == 'remote_single' || this.fieldConfig.multiPreSelect == 'local_single') {
            this.preSelectCombobox = new Ext.form.ComboBox({
                triggerAction: "all",
                fieldLabel: t("preSelect"),
                editable: false,
                name: "preSelect",
                store: this.preSelectStore,
                valueField: 'key',
                displayField: 'value',
                itemCls: "object_field",
                width: (this.fieldConfig.width ? this.fieldConfig.width : 300) + (this.fieldConfig.considerTenants ? 300 : 0)
            });
            panel.add(this.preSelectCombobox);
        }

        if(this.fieldConfig.multiPreSelect == 'local_single' || this.fieldConfig.multiPreSelect == 'local_multi') {
            if(this.preSelectCombobox.rendered) {
                this.preSelectCombobox.setValue(this.data.preSelect);
            } else {
                this.preSelectCombobox.addListener("afterRender", function() {
                    this.preSelectCombobox.setValue(this.data.preSelect);
                }.bind(this));
            }
        }

        this.component = panel;
        return this.component;
    },

    getValue: function () {
        var value = {
            tenant: (this.tenantCombobox ? this.tenantCombobox.getValue() : null),
            field: this.fieldsCombobox.getValue(),
            preSelect: (this.preSelectCombobox ? this.preSelectCombobox.getValue() : null)
        };
        return value;
    },

    isDirty: function() {
        return this.fieldsCombobox.isDirty() || (this.preSelectCombobox && this.preSelectCombobox.isDirty());
    },

    isInvalidMandatory: function () {
        if (this.fieldsCombobox.getValue()) {
            return false;
        }
        return true;
    }

});