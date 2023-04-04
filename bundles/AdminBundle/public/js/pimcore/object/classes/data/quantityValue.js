
pimcore.registerNS("pimcore.object.classes.data.quantityValue");
/**
 * @private
 */
pimcore.object.classes.data.quantityValue = Class.create(pimcore.object.classes.data.data, {

    type: "quantityValue",
    allowIndex: true,

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : true,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "quantityValue";

        this.initData(initData);

        this.treeNode = treeNode;

        this.store = pimcore.helpers.quantityValue.getClassDefinitionStore();

    },



    getTypeName: function () {
        return t("quantityValue_field");
    },

    getGroup: function () {
        return "numeric";
    },

    getIconClass: function () {
        return "pimcore_icon_quantityValue";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "textfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "textfield",
                fieldLabel: t("unit_width"),
                name: "unitWidth",
                value: this.datax.unitWidth
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('width_explanation')
            }
        ]);

        if (!this.inCustomLayoutEditor) {
            this.specificPanel.add([

                {
                    xtype: "numberfield",
                    fieldLabel: t("default_value"),
                    name: "defaultValue",
                    value: this.datax.defaultValue
                },{
                    xtype: 'combobox',
                    name: 'defaultUnit',
                    triggerAction: "all",
                    editable: true,
                    typeAhead: true,
                    selectOnFocus: true,
                    forceSelection: true,
                    fieldLabel: t('default_unit'),
                    store: this.store,
                    value: this.datax.defaultUnit,
                    displayField: 'abbreviation',
                    valueField: 'id',
                    width: 275
                },{
                    xtype: 'textfield',
                    width: 600,
                    fieldLabel: t("default_value_generator"),
                    labelWidth: 140,
                    name: 'defaultValueGenerator',
                    value: this.datax.defaultValueGenerator
                },
                {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html: '<span class="object_field_setting_warning">' + t('inherited_default_value_warning') + '</span>'
                },
                {
                    xtype: 'multiselect',
                    queryDelay: 0,
                    triggerAction: 'all',
                    resizable: true,
                    width: 600,
                    fieldLabel: t("valid_quantityValue_units"),
                    typeAhead: true,
                    name: 'validUnits',
                    value: this.datax.validUnits,
                    store: this.store,
                    displayField: 'abbreviation',
                    valueField: 'id'
                },
                {
                    xtype: "checkbox",
                    name: "autoConvert",
                    fieldLabel: t("auto_convert"),
                    checked: this.datax.autoConvert
                },
                {
                    xtype: "numberfield",
                    fieldLabel: t("decimal_size"),
                    name: "decimalSize",
                    maxValue: 65,
                    value: this.datax.decimalSize
                },
                {
                    xtype: "numberfield",
                    fieldLabel: t("decimal_precision"),
                    name: "decimalPrecision",
                    maxValue: 30,
                    value: this.datax.decimalPrecision
                },
                {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html: t('decimal_mysql_type_info')
                },
                {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html:'<span class="object_field_setting_warning">' +t('decimal_mysql_type_naming_warning')+'</span>'
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("integer"),
                    name: "integer",
                    checked: this.datax.integer
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("only_unsigned"),
                    name: "unsigned",
                    checked: this.datax["unsigned"]
                },
                {
                    xtype: "numberfield",
                    fieldLabel: t("min_value"),
                    name: "minValue",
                    value: this.datax.minValue
                },
                {
                    xtype: "numberfield",
                    fieldLabel: t("max_value"),
                    name: "maxValue",
                    value: this.datax.maxValue
                }
            ])
        }

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
                    unitWidth: source.datax.unitWidth,
                    height: source.datax.height,
                    validUnits: source.datax.validUnits,
                    defaultUnit: source.datax.defaultUnit,
                    defaultValue: source.datax.defaultValue,
                    integer: source.datax.integer,
                    unsigned: source.datax.unsigned,
                    minValue: source.datax.minValue,
                    maxValue: source.datax.maxValue,
                    decimalSize: source.datax.decimalSize,
                    decimalPrecision: source.datax.decimalPrecision,
                    autoConvert: source.datax.autoConvert,
                    defaultValueGenerator: source.datax.defaultValueGenerator
                });
        }
    }
});
