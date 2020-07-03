
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

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "combo",
                fieldLabel: t("type"),
                name: "elementType",
                value: this.datax.elementType,
                labelWidth: 140,
                store: [
                    ['input', t('input')],
                    ['textarea', t('textarea')]
                ]
            },
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width,
                labelWidth: 140
            },
            {
                xtype: "numberfield",
                fieldLabel: t("columnlength"),
                name: "columnLength",
                value: this.datax.columnLength,
                labelWidth: 140
            },
            {
                xtype: 'textfield',
                width: 600,
                fieldLabel: t("calculatedValue_calculatorclass"),
                labelWidth: 140,
                name: 'calculatorClass',
                value: this.datax.calculatorClass
            },
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
                    calculatorClass: source.datax.calculatorClass,
                    elementType: source.datax.elementType,
                    width: source.datax.width,
                    columnLength: source.datax.columnLength
                });
        }
    }
});
