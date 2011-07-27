/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
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
            width: 60,
            value: this.data,
            itemCls: "object_field"
        });

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
    },

    isInvalidMandatory: function () {
        if (this.getValue() == false) {
            return true;
        }
        return false;
    }
});