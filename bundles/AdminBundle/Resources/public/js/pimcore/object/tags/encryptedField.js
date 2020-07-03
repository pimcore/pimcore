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

pimcore.registerNS("pimcore.object.tags.encryptedField");
pimcore.object.tags.encryptedField = Class.create(pimcore.object.tags.abstract, {

    type: "encryptedField",

    initialize: function (data, fieldConfig) {

        if (typeof pimcore.object.tags[fieldConfig.delegateDatatype] !== "undefined") {
            var delegateFieldConfig = fieldConfig.delegate || {};
            this.delegate = new pimcore.object.tags[fieldConfig.delegateDatatype](data, delegateFieldConfig);
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function (field) {

        if (typeof pimcore.object.tags[field.layout.delegateDatatype] !== "undefined") {
            return pimcore.object.tags[field.layout.delegateDatatype].prototype.getGridColumnConfig(this.getDelegateGridConfig(field));
        } else {
            return {text: t(field.label), width: 150, sortable: false};
        }
    },

    getDelegateGridConfig: function(field) {
        var delegateConfig = {
            layout: field.layout.delegate || {},
            type: field.delegateDatatype,
            key: field.key,
            label: field.label
        }
        return delegateConfig;
    },

    getCellEditValue: function () {
        return this.delegate.getCellEditValue();
    },

    getGridColumnEditor: function (field) {

        return pimcore.object.tags[field.layout.delegateDatatype].prototype.getGridColumnEditor(this.getDelegateGridConfig(field));
    },

    getGridColumnFilter: function (field) {
        return null;
    },

    getLayoutEdit: function () {
        if (this.delegate) {
            this.component = this.delegate.getLayoutEdit();
            return this.component;
        }
    },

    getLayoutShow: function () {
        if (this.delegate) {
            this.component = this.delegate.getLayoutShow();
            return this.component;
        }
    },

    getValue: function () {
        return this.delegate.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        return this.delegate.isDirty();
    }
});
