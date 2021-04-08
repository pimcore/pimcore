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

pimcore.registerNS("pimcore.object.tags.quantityValue");
pimcore.object.tags.quantityValue = Class.create(pimcore.object.tags.abstract, {

    type: "quantityValue",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    applyDefaultValue: function() {
        this.defaultValue = null;
        this.defaultUnit = null;
        this.autoConvert = false;
        if ((typeof this.data === "undefined" || this.data === null) && (this.fieldConfig.defaultValue || this.fieldConfig.defaultUnit || this.fieldConfig.autoConvert)) {
            this.data = {
                value: this.fieldConfig.defaultValue,
                unit: this.fieldConfig.defaultUnit,
                autoConvert: this.fieldConfig.autoConvert
            };
            this.defaultValue = this.data.value;
            this.defaultUnit = this.data.unit;
            this.autoConvert = this.data.autoConvert;
        }
    },

    finishSetup: function() {
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'data',
            fields: ['id', 'abbreviation']
        });

        pimcore.helpers.quantityValue.initUnitStore(this.setData.bind(this), this.fieldConfig.validUnits, this.data);
    },

    setData: function(data) {
        var storeData = data.data;
        storeData.unshift({'id': -1, 'abbreviation' : "(" + t("empty") + ")"});

        this.store.loadData(storeData);

        if (this.unitField) {
            this.unitField.reset();
        }
    },

    getLayoutEdit: function () {
        var compatibleUnitsTooltip = Ext.create('Ext.tip.QuickTip', {
            title: t('unit_tooltip')
        });

        var compatibleUnitsButton = Ext.create('Ext.Button', {
            iconCls: "pimcore_icon_calculatedValue",
            style: "margin-left: 5px",
            hidden: true,
            handler: function() {
                compatibleUnitsTooltip.show();
            },
            listeners: {
                afterrender: function(me) {
                    compatibleUnitsTooltip.setTarget(me.getId());
                }
            }
        });

        var updateCompatibleUnitsToolTipContent = function() {
            if (this.inputField.value === '' || this.inputField.value === null || !this.unitField.value) {
                compatibleUnitsButton.hide();
                return false;
            }

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_quantityvalue_convertall'),
                params: {
                    value: this.inputField.value,
                    unit: this.unitField.value,
                },
                success: function (response) {
                    response = Ext.decode(response.responseText);
                    if (response && response.success && response.values.length > 0) {
                        var html = '<dl><dt>'+Ext.util.Format.number(response.value) + ' ' + response.fromUnit + ' =</dt>';
                        for (var i = 0; i < response.values.length; i++) {
                            html += '<dd>' + Ext.util.Format.number(response.values[i].value) + ' ' + response.values[i].unit + '</dd>';
                        }
                        html += '</dl>';
                        compatibleUnitsTooltip.setHtml(html);
                        compatibleUnitsButton.show();
                    } else {
                        compatibleUnitsButton.hide();
                    }
                }
            });
        }.bind(this);

        if (typeof this.store === "undefined") {
            this.finishSetup();
        }

        this.store.on('datachanged', function() {
            updateCompatibleUnitsToolTipContent();
        });

        var input = {};

        if (this.data && !isNaN(this.data.value)) {
            input.value = this.data.value;
        } else {
            // wipe invalid data
            if (this.data) {
                this.data.value = null;
            }
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        if (this.fieldConfig["decimalPrecision"] !== null) {
            input.decimalPrecision = this.fieldConfig["decimalPrecision"];
        }

        input.listeners = {
            change: function(input, newValue) {
                updateCompatibleUnitsToolTipContent();
            }
        };

        this.inputField = new Ext.form.field.Number(input);

        var labelWidth = 100;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
        }

        var unitWidth = 100;
        if(this.fieldConfig.unitWidth) {
            unitWidth = this.fieldConfig.unitWidth;
        }

        var options = {
            width: unitWidth,
            triggerAction: "all",
            autoSelect: true,
            editable: true,
            selectOnFocus: true,
            forceSelection: true,
            store: this.store,
            valueField: 'id',
            displayField: 'abbreviation',
            queryMode: 'local',
            listeners: {
                change: function( combo, newValue, oldValue) {
                    if(this.fieldConfig.autoConvert && (oldValue !== '' || oldValue !== null) && (newValue !== '' && newValue !== null)) {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_dataobject_quantityvalue_convert'),
                            params: {
                                value: this.inputField.value,
                                fromUnit: oldValue,
                                toUnit: newValue
                            },
                            success: function (response) {
                                response = Ext.decode(response.responseText);
                                if (response && response.success) {
                                    this.inputField.setValue(response.value);
                                }
                            }.bind(this)
                        });
                    }

                    updateCompatibleUnitsToolTipContent();
                }.bind(this)
            }
        };

        if(this.data && this.data.unit != null) {
            options.value = this.data.unit;
        } else {
            options.value = -1;
        }

        this.unitField = new Ext.form.ComboBox(options);

        updateCompatibleUnitsToolTipContent();

        this.component = new Ext.form.FieldContainer({
            layout: 'hbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            combineErrors: false,
            items: [this.inputField, this.unitField, compatibleUnitsButton],
            componentCls: "object_field object_field_type_" + this.type,
            isDirty: function() {
                return this.defaultValue || this.inputField.isDirty() || this.unitField.isDirty()
            }.bind(this)
        });

        return this.component;
    },

    getGridColumnConfig:function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                try {
                    metaData.tdCls += " grid_value_inherited";
                } catch (e) {
                    console.log(e);
                }
            }

            if (value) {
                return (value.value ? value.value : "") + " " + value.unitAbbr;
            } else {
                return "";
            }

        }.bind(this, field.key);

        return {
            getEditor:this.getWindowCellEditor.bind(this, field),
            text: t(field.label),
            sortable:true,
            dataIndex:field.key,
            renderer:renderer
        };
    },

    getGridColumnFilter: function (field) {
        if(typeof Ext.grid.filters.filter.QuantityValue === 'undefined') {
            Ext.define('Ext.grid.filters.filter.QuantityValue', {
                extend: 'Ext.grid.filters.filter.Number',
                alias: 'grid.filter.quantityValue',
                type: 'quantityValue',
                constructor: function(config) {
                    var me = this;
                    me.callParent([
                        config
                    ]);

                    this.store = config.store;
                    this.defaultUnit = config.defaultUnit;
                },
                createMenu: function () {
                    var me = this;
                    me.callParent();

                    var cfg = {
                        xtype: 'combo',
                        name: 'unit',
                        labelClsExtra: Ext.baseCSSPrefix + 'grid-filters-icon pimcore_nav_icon_quantityValue',
                        queryMode: 'local',
                        editable: false,
                        forceSelection: true,
                        hideEmptyLabel: false,
                        store: this.store,
                        value: this.defaultUnit,
                        valueField: 'id',
                        displayField: 'abbreviation',
                        margin: 0,
                        listeners: {
                            change: function (field) {
                                var me = this;

                                me.onValueChange(field, {
                                    RETURN: 1,
                                    getKey: function () {
                                        return null;
                                    }
                                });

                                var value = {};
                                if(me.filter) {
                                    for(var i in me.filter) {
                                        if (this.filter[i].getValue() !== null) {
                                            value[i] = me.filter[i].getValue()[0][0];
                                        }
                                    }
                                }

                                me.setValue(value);
                            }.bind(this)
                        }
                    };
                    if (me.getItemDefaults()) {
                        cfg = Ext.merge({}, me.getItemDefaults(), cfg);
                    }

                    me.menu.insert(0, '-');
                    me.fields.unit = me.menu.insert(0, cfg);
                },
                setValue: function (value) {
                    var me = this;
                    var unitId = me.fields.unit.getValue();

                    for (var i in value) {
                        value[i] = [[value[i], unitId]];
                    }

                    me.callParent([value]);
                },
                showMenu: function (menuItem) {
                    this.callParent([menuItem]);

                    for (var i in this.filter) {
                        if (this.filter[i].getValue() !== null) {
                            this.fields[i].setValue(this.filter[i].getValue()[0][0]);
                        }
                    }
                }
            });
        }

        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'data',
            fields: ['id', 'abbreviation']
        });
        pimcore.helpers.quantityValue.initUnitStore(function(data) {
            store.loadData(data.data);
        }, field.layout.validUnits);

        return {
            type: 'quantityValue',
            dataIndex: field.key,
            store: store,
            defaultUnit: field.layout.defaultUnit
        };
    },

    getLayoutShow: function () {
        this.getLayoutEdit();
        this.unitField.setReadOnly(true);
        this.inputField.setReadOnly(true);

        return this.component;
    },

    getValue: function () {
        return {
            unit: this.unitField.getValue(),
            unitAbbr: this.unitField.getRawValue(),
            value: this.inputField.getValue()
        };
    },

    getCellEditValue: function () {
        var value = this.getValue();
        value["unitAbbr"] = this.unitField.getRawValue();
        return value;
    },


    getName: function () {
        return this.fieldConfig.name;
    }
});
