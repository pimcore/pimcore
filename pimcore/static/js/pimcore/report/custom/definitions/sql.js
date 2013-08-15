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

pimcore.registerNS("pimcore.report.custom.definition.sql");
pimcore.report.custom.definition.sql = Class.create({

    element: null,

    initialize: function (sourceDefinitionData, key, deleteControl, columnSettingsCallback) {
        this.element = new Ext.form.FormPanel({
            key: key,
            bodyStyle: "padding:10px;",
            layout: "pimcoreform",
            autoHeight: true,
            border: false,
            tbar: deleteControl, //this.getDeleteControl("SQL", key),
            items: [{
                xtype: "textarea",
                name: "sql",
                fieldLabel: "SQL <br /><small>(eg. SELECT a,b,c FROM d)</small>",
                value: (sourceDefinitionData ? sourceDefinitionData.sql : ""),
                width: 500,
                height: 150,
                enableKeyEvents: true,
                listeners: {
                    keyup: columnSettingsCallback //this.getColumnSettings.bind(this)
                }
            }]
        });
    },

    getElement: function() {
        return this.element;
    },

    getValues: function() {
        var values = this.element.getForm().getFieldValues();
        values.type = "sql";
        return values;
    }


});