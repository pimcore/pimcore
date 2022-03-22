
pimcore.registerNS("pimcore.object.classes.data.calculatedValue");
pimcore.object.classes.data.calculatedValue = Class.create(pimcore.object.classes.data.data, {

    type: "calculatedValue",
    allowIndex: true,

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : true
    },

    initialize: function (treeNode, initData) {
        this.type = "calculatedValue";

        this.initData(initData);

        this.treeNode = treeNode;
    },



    getTypeName: function () {
        return t("calculatedValue_field");
    },

    getGroup: function () {
            return "other";
    },

    getIconClass: function () {
        return "pimcore_icon_calculatedValue";
    },

    getLayout: function ($super) {

        $super();


        const calculatorClass = Ext.create('Ext.form.TextField', {
            width: 600,
            fieldLabel: t('calculatedValue_calculatorclass'),
            labelWidth: 140,
            name: 'calculatorClass',
            value: this.datax.calculatorClass,
            hidden: this.datax.calculatorType == 'expression'
        });
        const calculatorExpression = Ext.create('Ext.form.TextField', {
            width: 600,
            fieldLabel: t('calculatedValue_calculatorexpression'),
            labelWidth: 140,
            name: 'calculatorExpression',
            value: this.datax.calculatorExpression,
            hidden: this.datax.calculatorType == 'class'
        });

        const calculatorType = Ext.create('Ext.form.ComboBox', {
            xtype: 'textfield',
            fieldLabel: t('calculatedValue_calculatortype'),
            labelWidth: 140,
            name: 'calculatorType',
            displayField: 'name',
            valueField: 'value',
            store: [
                { value: 'class', name: t('calculatedValue_calculatortype_class') },
                { value: 'expression', name: t('calculatedValue_calculatortype_expression') },
            ],
            listeners: {
                change: function(combo, newValue, oldValue) {
                    calculatorExpression.setVisible(newValue == 'expression');
                    calculatorClass.setVisible(newValue == 'class');
                }
            },
            value: this.datax.calculatorType
        });

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "combo",
                fieldLabel: t("type"),
                name: "elementType",
                value: this.datax.elementType,
                labelWidth: 140,
                forceSelection: true,

                store: [
                    ['input', t('input')],
                    ['textarea', t('textarea')],
                    ['html', t('html')]
                ]
            },
            {
                xtype: "textfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width,
                labelWidth: 140
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('width_explanation')
            },
            {
                xtype: "numberfield",
                fieldLabel: t("columnlength"),
                name: "columnLength",
                value: this.datax.columnLength,
                labelWidth: 140
            },
            calculatorType,
            calculatorClass,
            calculatorExpression,
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('calculatedValue_explanation'),
                cls: "pimcore_extra_label_bottom",
                style: "color:red; font-weight: bold; padding-bottom:0;"
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
                    calculatorType: source.datax.calculatorType,
                    calculatorClass: source.datax.calculatorClass,
                    calculatorExpression: source.datax.calculatorExpression,
                    elementType: source.datax.elementType,
                    width: source.datax.width,
                    columnLength: source.datax.columnLength
                });
        }
    }
});
