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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.report.custom.definition.sql");
pimcore.report.custom.definition.sql = Class.create({

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
            layout: "pimcoreform",
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
                    xtype: "textarea",
                    name: "sql",
                    fieldLabel: "SELECT <br /><small>(eg. a,b,c)</small>",
                    value: (sourceDefinitionData ? sourceDefinitionData.sql : ""),
                    width: 500,
                    height: 50,
                    grow: true,
                    growMax: 200,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function() {
                            this.updateGroupByMultiSelectStore(false);
                        }.bind(this)
                    }
                },
                {
                    xtype: "textarea",
                    name: "from",
                    fieldLabel: "FROM <br /><small>(eg. d INNER JOIN e ON c.a = e.b)</small>",
                    value: (sourceDefinitionData ? sourceDefinitionData.from : ""),
                    width: 500,
                    height: 50,
                    grow: true,
                    growMax: 200,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function() {
                            this.updateGroupByMultiSelectStore(false);
                        }.bind(this)
                    }
                },
                {
                    xtype: "textarea",
                    name: "where",
                    fieldLabel: "WHERE <br /><small>(eg. c = 'some_value')</small>",
                    value: (sourceDefinitionData ? sourceDefinitionData.where : ""),
                    width: 500,
                    height: 50,
                    grow: true,
                    growMax: 200,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function() {
                            this.updateGroupByMultiSelectStore(false);
                        }.bind(this)
                    }
                },
                {
                    xtype: "textarea",
                    name: "groupby",
                    fieldLabel: "GROUP BY <br /><small>(eg. b, c )</small>",
                    value: (sourceDefinitionData ? sourceDefinitionData.groupby : ""),
                    width: 500,
                    height: 50,
                    grow: true,
                    growMax: 200,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function() {
                            this.updateGroupByMultiSelectStore(false);
                        }.bind(this)
                    }
                }
            ]
        });

        this.sqlText = new Ext.form.DisplayField({
            name: "sqlText",
            style: "color: blue;"
        });
        this.element.add(this.sqlText);
        this.element.doLayout();
    },

    getElement: function() {
        return this.element;
    },

    getValues: function() {
        var values = this.element.getForm().getFieldValues();
        values.type = "sql";
        return values;
    },

    updateGroupByMultiSelectStore: function(addItem) {
        this.columnSettingsCallback();
        var values = this.getValues();

        if(this.sqlText) {
            var sqlText = "";
            if(values.sql) {
                if(values.sql.indexOf("SELECT") < 0 || values.sql.indexOf("SELECT") > 5) {
                    sqlText += "SELECT ";
                }
                sqlText += values.sql;
            }

            if(values.from) {
                if(values.from.indexOf("FROM") < 0) {
                    sqlText += " FROM ";
                }
                sqlText += values.from;
            }

            if(values.where) {
                if(values.where.indexOf("WHERE") < 0) {
                    sqlText += " WHERE ";
                }
                sqlText += values.where;
            }

            if(values.groupby) {
                if(values.groupby.indexOf("GROUP BY") < 0) {
                    sqlText += " GROUP BY ";
                }
                sqlText += values.groupby;
            }

            this.sqlText.setValue(sqlText);
        }
    }
});
