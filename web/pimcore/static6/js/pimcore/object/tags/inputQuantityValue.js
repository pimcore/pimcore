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

pimcore.registerNS("pimcore.object.tags.inputQuantityValue");
pimcore.object.tags.inputQuantityValue = Class.create(pimcore.object.tags.quantityValue, {

    type: "inputQuantityValue",

    getLayoutEdit: function () {

        var input = {};

        var valueInvalid = false;

        if (this.data) {
            input.value = this.data.value;
        }

        input.width = 171;
        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        var labelWidth = 100;
        if (this.fieldConfig.labelWidth) {
            labelWidth = this.fieldConfig.labelWidth;
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
            valueField: 'id',
            displayField: 'abbreviation',
            queryMode: 'local'
        };

        if(this.data && this.data.unit != null && !isNaN(this.data.unit)) {
            options.value = this.data.unit;
        } else {
            options.value = -1;
        }

        this.unitField = new Ext.form.ComboBox(options);

        this.inputField = new Ext.form.TextField(input);

        this.component = new Ext.form.FieldContainer({
            layout: 'hbox',
            margin: '0 0 10 0',
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            combineErrors: false,
            items: [this.inputField, this.unitField],
            componentCls: "object_field",
            isDirty: function() {
                return this.inputField.isDirty() || this.unitField.isDirty() || valueInvalid
            }.bind(this)
        });

        return this.component;
    }
});