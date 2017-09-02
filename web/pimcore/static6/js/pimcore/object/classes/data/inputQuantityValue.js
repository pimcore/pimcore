
pimcore.registerNS("pimcore.object.classes.data.inputQuantityValue");
pimcore.object.classes.data.inputQuantityValue = Class.create(pimcore.object.classes.data.quantityValue, {

    type: "inputQuantityValue",

    initialize: function (treeNode, initData) {
        this.type = "inputQuantityValue";

        this.initData(initData);

        this.treeNode = treeNode;

        this.store = pimcore.helpers.quantityValue.getClassDefinitionStore();

    },

    getTypeName: function () {
        return t("inputQuantityValue_field");
    },

    getGroup: function () {
        return "text";
    },

    getIconClass: function () {
        return "pimcore_icon_inputQuantityValue";
    },

    getLayout: function ($super) {

        $super();

        var defaultValueItemIndex = -1;
        var item = null;
        for (i = 0; i < this.specificPanel.items.items.length; i++) {
            item = this.specificPanel.items.items[i];
            if (item.name === 'defaultValue') {
                defaultValueItemIndex = i;
                break;
            }
        }

        if (defaultValueItemIndex >= 0) {
            this.specificPanel.remove(defaultValueItemIndex);
        }

        return this.layout;
    }
});