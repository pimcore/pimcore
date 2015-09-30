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

pimcore.registerNS("pimcore.object.tags.quantityValue");
pimcore.object.tags.quantityValue = Class.create(pimcore.object.tags.abstract, {

    type: "quantityValue",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'data',
            fields: ['id', 'abbreviation']
        });

        pimcore.helpers.quantityValue.initUnitStore(this.setData.bind(this), fieldConfig.validUnits);
    },

    setData: function(data) { 
        this.storeData = data;
        this.store.loadData(data.data);

        if (this.unitField) {
            this.unitField.reset();
        }

    },

    getLayoutEdit: function () {

        var input = {
            itemCls: "object_field"
        };

        if (this.data && !isNaN(this.data.value)) {
            input.value = this.data.value;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        var options = {
            width: 100,
            triggerAction: "all",
            autoSelect: true,
            editable: true,
            selectOnFocus: true,
            allowBlank: true,
            forceSelection: true,
            store: this.store,
            //itemCls: "object_field",
            valueField: 'id',
            displayField: 'abbreviation',
            queryMode: 'local'
        };

        if(this.data) {
            options.value = this.data.unit;
        } else if(this.storeData && this.storeData.data[0]) {
            options.value = this.storeData.data[0].id;
        }

        this.unitField = new Ext.form.ComboBox(options);

        this.inputField = new Ext.form.field.Number(input);

        this.component = Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            fieldLabel: this.fieldConfig.title,
            combineErrors: false,
            items: [this.inputField, this.unitField],
            componentCls: "object_field",
            isDirty: function() {
                return this.inputField.isDirty() || this.unitField.isDirty()
            }.bind(this)
        });


        return this.component;
    },


    getLayoutShow: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            cls: "object_field"
        };

        if (this.data && this.data.value) {
            input.value = this.data.value;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        this.component = new Ext.form.TextField(input);
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return {
            unit: this.unitField.getValue(),
            value: this.inputField.getValue()
        };
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        if (this.unitField.getValue() && this.inputField.getValue()) {
            return false;
        }
        return true;
    }
});