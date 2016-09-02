/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.report.custom.definition.custom");
pimcore.report.custom.definition.custom = Class.create({

    element: null,
    sourceDefinitionData: null,
    columnSettingsCallback: null,

    initialize: function (sourceDefinitionData, key, deleteControl, columnSettingsCallback) {
        this.sourceDefinitionData = sourceDefinitionData;
        this.columnSettingsCallback = columnSettingsCallback;
        this.groupByStore = new Ext.data.ArrayStore({
            fields: ['text'],
            data: [],
            expandData: true
        });

        this.element = new Ext.form.FormPanel({
            key: key,
            bodyStyle: "padding:10px;",
            autoHeight: true,
            border: false,
            tbar: deleteControl,
            listeners: {
                afterrender: function() {
                    this.updateGroupByMultiSelectStore(true);
                }.bind(this)
            },
            items: [
                {
                    xtype: "textfield",
                    name: "className",
                    fieldLabel: "Class name",
                    value: (sourceDefinitionData ? sourceDefinitionData.className : ""),
                    width: 500,
                    //height: 150,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: this.onCustomEditorKeyup.bind(this)
                    }
                }
            ]
        });

        this.customText = new Ext.form.DisplayField({
            name: "customText",
            style: "color: blue;",
            value: "Adapter class name must be fully qualified<br /><small>(eg. \\Website\\MyReportAdapter)</small>"
        });
        this.element.add(this.customText);
        this.element.updateLayout();
    },

    getElement: function() {
        return this.element;
    },

    getValues: function() {
        var values = this.element.getForm().getFieldValues();
        values.type = "custom";
        return values;
    },

    onCustomEditorKeyup: function() {
        clearTimeout(this._keyupTimout);

        var self = this;
        this._keyupTimout = setTimeout(function() {
            self.updateGroupByMultiSelectStore(false);
        }, 500);
    },

    updateGroupByMultiSelectStore: function(addItem) {
        this.columnSettingsCallback();
    }
});
