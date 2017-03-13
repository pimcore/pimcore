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

pimcore.registerNS("pimcore.object.tags.time");
pimcore.object.tags.time = Class.create(pimcore.object.tags.abstract, {

    type: "time",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    getGridColumnFilter: function(field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayoutEdit: function () {
        this.component = new Ext.form.TimeField({
            fieldLabel: this.fieldConfig.title,
            format: "H:i",
            emptyText: "",
            width: 200,
            value: this.data,
            allowBlank: (!this.fieldConfig.mandatory),
            minValue: (this.fieldConfig.minValue) ? this.fieldConfig.minValue : null,
            maxValue: (this.fieldConfig.maxValue) ? this.fieldConfig.maxValue : null,
            componentCls: "object_field"
        });

        return this.component;
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        var date = this.component.getValue();
        return Ext.Date.format(date, "H:i");
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        if (this.getValue() == false) {
            return true;
        }
        return false;
    }
});