pimcore.registerNS("pimcore.object.tags.indexFieldSelectionField");
pimcore.object.tags.indexFieldSelectionField = Class.create(pimcore.object.tags.abstract, {

    type: "indexFieldSelectionField",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            baseParams: {class_id: fieldConfig.classId, specific_price_field: this.fieldConfig.specificPriceField },
            url: '/plugin/OnlineShop/index/get-fields',
            root: 'data',
            fields: ['key','name'],
            listeners: {
                load: function() {
                    if(this.component) {
                        this.component.setValue(this.data);
                    }
                }.bind(this)
            }
        });

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

        this.component = new Ext.ux.form.SuperBoxSelect(conf);
        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    }
});