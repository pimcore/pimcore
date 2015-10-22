
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
        localizedfield: true
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
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                value: this.datax.defaultValue
            },
            {
                xtype: 'multiselect',
                queryDelay: 0,
                triggerAction: 'all',
                resizable: true,
                width: 600,
                fieldLabel: t("valid_quantityValue_units"),
                typeAhead: true,
                //emptyText: t("valid_quantityValue_units_empty_text"),
                name: 'validUnits',
                value: this.datax.validUnits,
                store: this.store,

                displayField: 'abbreviation',
                valueField: 'id'
            }
        ]);

        return this.layout;
    }
});
