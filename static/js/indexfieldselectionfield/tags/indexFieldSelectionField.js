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


pimcore.registerNS("pimcore.object.tags.indexFieldSelectionField");
pimcore.object.tags.indexFieldSelectionField = Class.create(pimcore.object.tags.abstract, {

    type: "indexFieldSelectionField",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            baseParams: {class_id: fieldConfig.classId, specific_price_field: this.fieldConfig.specificPriceField, show_all_fields: this.fieldConfig.showAllFields },
            url: '/plugin/EcommerceFramework/index/get-fields',
            root: 'data',
            fields: ['key','name'],
            listeners: {
                load: function(store) {
                    if(this.firstLoad !== false) {
                        var values = this.data.split(",");
                        for(var i = 0; i < values.length; i++) {
                            if(store.find('key', values[i]) < 0) {
                                var defaultData = {
                                    'key': values[i],
                                    'name': ts(values[i])
                                };
                                var record = new store.recordType(defaultData, values[i]);
                                store.add(record);
                            }
                        }
                        this.firstLoad = false;
                    }

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
                url: '/plugin/EcommerceFramework/index/get-all-tenants',
                root: 'data',
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
            itemCls: "object_field",
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

        this.fieldsCombobox = new Ext.ux.form.SuperBoxSelect(conf);


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


    getLayoutShow: function () {

        this.fieldsCombobox = this.getLayoutEdit();
        this.fieldsCombobox.disable();

        return this.fieldsCombobox;
    },

    getValue: function () {
        return this.fieldsCombobox.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});