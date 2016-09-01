
pimcore.registerNS("pimcore.object.classes.data.quantityValue");
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
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
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
                fieldLabel: t('default_unit'),
                store: this.store,
                value: this.datax.defaultUnit,
                displayField: 'abbreviation',
                valueField: 'id',
                width: 275
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
            }
        ]);

        if (!this.isInCustomLayoutEditor()) {
            this.specificPanel.add([
                {
                    xtype: "numberfield",
                    fieldLabel: t("decimal_precision"),
                    name: "decimalPrecision",
                    maxValue: 65,
                    value: this.datax.decimalPrecision
                }, {
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
                    html: t('if_specified_decimal_mysql_type_is_used_automatically')
                }, {
                    xtype: "checkbox",
                    fieldLabel: t("integer"),
                    name: "integer",
                    checked: this.datax.integer
                }, {
                    xtype: "checkbox",
                    fieldLabel: t("only_unsigned"),
                    name: "unsigned",
                    checked: this.datax["unsigned"]
                }, {
                    xtype: "numberfield",
                    fieldLabel: t("min_value"),
                    name: "minValue",
                    value: this.datax.minValue
                },{
                    xtype: "numberfield",
                    fieldLabel: t("max_value"),
                    name: "maxValue",
                    value: this.datax.maxValue
                }
            ]);
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
                    defaultValue: source.datax.defaultValue,
                    integer: source.datax.integer,
                    unsigned: source.datax.unsigned,
                    minValue: source.datax.minValue,
                    maxValue: source.datax.maxValue,
                    decimalPrecision: source.datax.decimalPrecision
                });
        }
    }
});
