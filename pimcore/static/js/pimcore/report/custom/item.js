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

pimcore.registerNS("pimcore.report.custom.item");
pimcore.report.custom.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentElements = [];
        this.currentElementCount = 0;
        this.addLayout();
    },


    addLayout: function () {

        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        });

        this.columnStore = new Ext.data.JsonStore({
            autoDestroy: false,
            data: [],
            fields: ["name", "filter", "filter_drilldown", "display", "export", "order", "width", "label"]
        });

        var checkDisplay = new Ext.grid.CheckColumn({
            header: t("display"),
            dataIndex: "display",
            width: 50
        });

        var checkExport = new Ext.grid.CheckColumn({
            header: t("export"),
            dataIndex: "export",
            width: 50
        });

        var checkOrder = new Ext.grid.CheckColumn({
            header: t("order"),
            dataIndex: "order",
            width: 50
        });

        this.columnGrid = new Ext.grid.EditorGridPanel({
            store: this.columnStore,
            columns: [
                {header: t("name"), sortable: false, dataIndex: 'name', editable: false, width: 200},
                checkDisplay,
                checkExport,
                checkOrder,
                {header: t("filter_type"), width:100, sortable: false, dataIndex: 'filter', editable: true, editor: new Ext.form.ComboBox({
                    store: [
                        ["", t("empty")],
                        ["string", t("text")],
                        ["numeric", t("numeric")],
//                        ["date", t("date")],
                        ["boolean", t("bool")]
                    ],
                    mode: "local",
                    typeAhead: false,
                    editable: false,
                    forceSelection: true,
                    triggerAction: "all"
                })},
                {header: t("custom_report_filter_drilldown"), width:100, sortable: false, dataIndex: 'filter_drilldown', editable: true, editor: new Ext.form.ComboBox({
                    store: [
                        //["date", t("date")],
                        ["", t("empty")],
                        ["only_filter", t("custom_report_only_filter")],
                        ["filter_and_show", t("custom_report_filter_and_show")]
                    ],
                    mode: "local",
                    typeAhead: false,
                    editable: false,
                    forceSelection: true,
                    triggerAction: "all"
                })},
                {header: t("width"), sortable: false, dataIndex: 'width', editable: true, width: 70, editor: new Ext.ux.form.SpinnerField({
                    decimalPrecision: 0
                })},
                {header: t("label"), sortable: false, dataIndex: 'label', editable: true, width: 150, editor: new Ext.form.TextField({})},
                {
                    xtype:'actioncolumn',
                    width:30,
                    items:[
                        {
                            tooltip:t('up'),
                            icon:"/pimcore/static/img/icon/arrow_up.png",
                            handler:function (grid, rowIndex) {
                                if (rowIndex > 0) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(rowIndex - 1, [rec]);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype:'actioncolumn',
                    width:30,
                    items:[
                        {
                            tooltip:t('down'),
                            icon:"/pimcore/static/img/icon/arrow_down.png",
                            handler:function (grid, rowIndex) {
                                if (rowIndex < (grid.getStore().getCount() - 1)) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(rowIndex + 1, [rec]);
                                }
                            }.bind(this)
                        }
                    ]
                }
            ],
            columnLines: true,
            trackMouseOver: true,
            stripeRows: true,
            autoHeight: true,
            plugins: [checkDisplay,checkExport, checkOrder],
            title: t('column_configuration')
        });

        this.panel = new Ext.Panel({
            region: "center",
            id: "pimcore_sql_panel_" + this.data.name,
            bodyStyle: "padding:10px",
            labelWidth: 150,
            autoScroll: true,
            border:false,
            items: [
                this.getGeneralDefinitionPanel(),
                this.getSourceDefinitionPanel(),
                this.columnGrid,
                this.getChartDefinitionPanel()
            ],
            buttons: panelButtons,
            title: this.data.name,
            bodyStyle: "padding: 20px;",
            closable: true,
            listeners: {
                afterrender: this.getColumnSettings.bind(this)
            }
        });

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },

    getGeneralDefinitionPanel: function() {
        this.generalDefinitionForm = new Ext.form.FormPanel({
            border:false,
            layout: "pimcoreform",
            items: [{
                xtype: "fieldset",
                itemId: "generalFieldset",
                title: t("general"),
                collapsible: false,
                items: [{
                    xtype: "textfield",
                    name: "name",
                    value: this.data.name,
                    fieldLabel: t("name"),
                    width: 300,
                    disabled: true
                },{
                    xtype: "textfield",
                    name: "niceName",
                    value: this.data.niceName,
                    fieldLabel: t("nice_name"),
                    width: 300
                },{
                    xtype: "textfield",
                    name: "iconClass",
                    value: this.data.iconClass,
                    fieldLabel: t("icon_class"),
                    width: 300
                },{
                    xtype: "textfield",
                    name: "group",
                    value: this.data.group,
                    fieldLabel: t("group"),
                    width: 300
                },{
                    xtype: "textfield",
                    name: "groupIconClass",
                    value: this.data.groupIconClass,
                    fieldLabel: t("group_icon_class"),
                    width: 300
                },{
                    xtype: "checkbox",
                    name: "menuShortcut",
                    checked: this.data.menuShortcut,
                    fieldLabel: t("create_menu_shortcut"),
                    width: 300
                }]
            }]
        });

        return this.generalDefinitionForm;
    },

    getChartDefinitionPanel: function() {

        var chartTypeSelector = new Ext.form.ComboBox({
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            name: 'chartType',
            fieldLabel: t('custom_report_charttype'),
            value: this.data.chartType,
            store: new Ext.data.ArrayStore({
                fields: [
                    'chartType',
                    'text'
                ],
                data: [['', t('custom_report_charttype_none')],['pie', t('custom_report_charttype_pie')], ['line', t('custom_report_charttype_line')], ['bar', t('custom_report_charttype_bar')]]
            }),
            valueField: 'chartType',
            displayField: 'text',
            listeners: {
                afterrender: function() {
                    this.updateTypeSpecificCartDefinitionPanel(this.data.chartType);
                }.bind(this),
                select: function(combo, record, index) {
                    var chartType = combo.getValue();
                    this.updateTypeSpecificCartDefinitionPanel(chartType);
                }.bind(this)
            }
        });

        this.pieChartDefinitionPanel = this.getPieChartDefinitionPanel();
        this.lineChartDefinitionPanel = this.getLineChartDefinitionPanel();

        this.chartDefinitionForm = new Ext.form.FormPanel({
            border:false,
            layout: "pimcoreform",
            items: [{
                xtype: 'fieldset',
                itemId: "chartdefinitionFieldset",
                title: t("custom_report_chart_settings"),
                style: "margin-top: 20px;margin-bottom: 20px",
                collapsible: false,
                items: [
                    chartTypeSelector,
                    this.pieChartDefinitionPanel,
                    this.lineChartDefinitionPanel
                ]
            }]
        });


        return this.chartDefinitionForm;
    },

    updateTypeSpecificCartDefinitionPanel: function(chartType) {
        this.pieChartDefinitionPanel.setVisible(false);
        this.lineChartDefinitionPanel.setVisible(false);

        if(chartType == "pie") {
            this.pieChartDefinitionPanel.setVisible(true);
        }
        if(chartType == "line" || chartType == "bar") {
            this.lineChartDefinitionPanel.setVisible(true);
        }

        this.chartDefinitionForm.doLayout();
    },

    getPieChartDefinitionPanel: function() {
        return new Ext.form.FieldSet({
            title: t("custom_report_chart_options"),
            hidden: true,
            style: "margin-top: 20px;margin-bottom: 20px",
            collapsible: false,
            items: [new Ext.form.ComboBox({
                triggerAction: 'all',
                lazyRender:true,
                name: 'pieLabelColumn',
                value: this.data.pieLabelColumn,
                mode: 'local',
                width: 300,
                fieldLabel: t('custom_report_labelcolumn'),
                store: this.columnStore,
                valueField: 'name',
                displayField: 'name'
            }),
                new Ext.form.ComboBox({
                    triggerAction: 'all',
                    lazyRender:true,
                    name: 'pieColumn',
                    value: this.data.pieColumn,
                    mode: 'local',
                    width: 300,
                    fieldLabel: t('custom_report_datacolumn'),
                    store: this.columnStore,
                    valueField: 'name',
                    displayField: 'name'
                })
            ]
        });
    },

    getLineChartDefinitionPanel: function() {
        return new Ext.form.FieldSet({
            title: t("custom_report_chart_options"),
            hidden: true,
            style: "margin-top: 20px;margin-bottom: 20px",
            collapsible: false,
            listeners: {
                afterrender: function() {
                    if(this.data.yAxis && this.data.yAxis.length > 1) {
                        for(var i = 1; i < this.data.yAxis.length; i++) {
                            this.addAdditionalYAxis(this.data.yAxis[i]);
                        }
                    }
                }.bind(this)
            },
            items: [
                new Ext.form.ComboBox({
                    triggerAction: 'all',
                    lazyRender:true,
                    name: 'xAxis',
                    mode: 'local',
                    width: 300,
                    value: this.data.xAxis,
                    fieldLabel: t('custom_report_x_axis'),
                    store: this.columnStore,
                    valueField: 'name',
                    displayField: 'name'
                }),{
                    xtype: "compositefield",
                    fieldLabel: t("custom_report_y_axis"),
                    width: 360,
                    items: [{
                        xtype: "combo",
                        triggerAction: 'all',
                        lazyRender:true,
                        name: 'yAxis',
                        mode: 'local',
                        width: 300,
                        store: this.columnStore,
                        value: this.data.yAxis ? this.data.yAxis[0] : null,
                        valueField: 'name',
                        displayField: 'name'
                    },{
                        xtype: "button",
                        iconCls: "pimcore_icon_add",
                        handler: function () {
                            this.addAdditionalYAxis();
                        }.bind(this)
                    }]
                }
            ]
        });
    },

    addAdditionalYAxis: function(value) {
        this.lineChartDefinitionPanel.add({
            xtype: "compositefield",
            fieldLabel: t("custom_report_y_axis"),
            width: 360,
            items: [{
                xtype: "combo",
                triggerAction: 'all',
                lazyRender:true,
                name: 'yAxis',
                mode: 'local',
                width: 300,
                store: this.columnStore,
                value: value ? value : null,
                valueField: 'name',
                displayField: 'name'
            },{
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                handler: function (button) {
                    this.lineChartDefinitionPanel.remove(button.findParentByType('compositefield'));
                    this.lineChartDefinitionPanel.doLayout();
                }.bind(this)
            }]
        });
        this.lineChartDefinitionPanel.doLayout();
    },

    getSourceDefinitionPanel: function() {

        this.sourceDefinitionsItems = new Ext.Panel({
            style: "margin-bottom: 20px",
            layout: "pimcoreform",
            items: [
                this.getAddControl()
            ]
        });

        var sourceDefinitionFieldset = new Ext.form.FieldSet({
            itemId: "sourcedefinitionFieldset",
            title: t("source_definition"),
            style: "margin-top: 20px;margin-bottom: 20px",
            collapsible: false,
            items: [
                this.sourceDefinitionsItems,
                {
                    xtype: "displayfield",
                    name: "errorMessage",
                    itemId: "errorMessage",
                    style: "color: red;"
                }
            ]
        });

        for(var i = 0; i < this.data.dataSourceConfig.length; i++) {
            if(this.data.dataSourceConfig[i]) {
                this.addSourceDefinition(this.data.dataSourceConfig[i]);
            }
        }
        return sourceDefinitionFieldset;
    },

    getDeleteControl: function (title, index) {

        var items = [{xtype: 'tbtext', text: title}];

        items.push({
            cls: "pimcore_block_button_minus",
            iconCls: "pimcore_icon_minus",
            listeners: {
                "click": this.removeSourceDefinition.bind(this, index)
            }
        });

        var toolbar = new Ext.Toolbar({
            items: items
        });

        return toolbar;
    },

    removeSourceDefinition: function(key) {
        for(var i = 0; i < this.currentElements.length; i++) {
            if(this.currentElements[i].key == key) {
                this.currentElements[i].deleted = true;
                this.sourceDefinitionsItems.remove(this.currentElements[i].adapter.getElement());
            }
        }
        this.currentElementCount--;
        this.sourceDefinitionsItems.remove(this.sourceDefinitionsItems.get(0));
        this.sourceDefinitionsItems.insert(0, this.getAddControl());
        this.sourceDefinitionsItems.doLayout();
    },

    getAddControl: function() {
        var classMenu = [];

        if(this.currentElementCount < 1) {
            classMenu.push({
                text: t("custom_report_adapter_sql"),
                handler: this.addSourceDefinition.bind(this, {type:'sql'}),
                iconCls: "pimcore_icon_objectbricks"
            });

            classMenu.push({
                text: t("custom_report_adapter_analytics"),
                handler: this.addSourceDefinition.bind(this, {type:'analytics'}),
                iconCls: "pimcore_icon_objectbricks"
            });
        }

        var items = [];

        if(classMenu.length == 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                text: ts(classMenu[0].text),
                iconCls: "pimcore_icon_plus_no_repeat",
                handler: classMenu[0].handler
            });
        } else if (classMenu.length > 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus",
                menu: classMenu
            });
        } else {
            items.push({
                xtype: "tbtext",
                text: t("no_further_sources_allowed")
            });
        }

        var toolbar = new Ext.Toolbar({
            items: items
        });

        return toolbar;
    },

    addSourceDefinition: function (sourceDefinitionData) {
        this.sourceDefinitionsItems.remove(this.sourceDefinitionsItems.get(0));

        var currentData = {};

        if(!this.currentElements) {
            this.currentElements = [];
        }

        var key = this.currentElements.length;

        sourceDefinitionData.type = sourceDefinitionData.type ? sourceDefinitionData.type : 'sql';

        var adapter = new pimcore.report.custom.definition[sourceDefinitionData.type](sourceDefinitionData, key, this.getDeleteControl(t("custom_report_adapter_"+sourceDefinitionData.type), key), this.getColumnSettings.bind(this));



        this.currentElements.push({key: key, adapter: adapter});
        this.currentElementCount++;

        this.sourceDefinitionsItems.add(adapter.getElement());
        this.sourceDefinitionsItems.insert(0, this.getAddControl());
        this.sourceDefinitionsItems.doLayout();
    },

    getColumnSettings: function () {
        var m = this.getValues();
        Ext.Ajax.request({
            url: "/admin/reports/custom-report/column-config",
            method: "post",
            params: {configuration: Ext.encode(m.dataSourceConfig)},
            success: function (response) {
                var res = Ext.decode(response.responseText);

                if(res.success) {
                    this.updateColumnSettings(res.columns);
                }

                var errorField = this.panel.getComponent("sourcedefinitionFieldset").getComponent("errorMessage");

                errorField.setValue("");
                if(!res.success && res.errorMessage) {
                    errorField.setValue(res.errorMessage);
                }
            }.bind(this)
        });
    },

    updateColumnSettings: function (columns) {

        var insertData, isInStore,o;
        var cc = this.data.columnConfiguration;

        if(columns && columns.length > 0) {
            // cleanup
            this.columnStore.each(function (columns, rec) {
                if(!in_array(rec.get("name"), columns)) {
                    this.columnStore.remove(rec);
                }
            }.bind(this, columns));

            // insert
            for(var i=0; i<columns.length; i++) {
                isInStore = (this.columnStore.findExact("name", columns[i]) >= 0) ? true : false;
                if(!isInStore) {

                    insertData = {
                        name: columns[i],
                        display: true,
                        "export": true,
                        order: true,
                        width: "",
                        label: ""
                    };

                    if(typeof cc == "object" && cc.length > 0) {
                        for(o=0; o<cc.length; o++) {
                            if(cc[o]["name"] == columns[i]) {
                                insertData["display"] = cc[o]["display"];
                                insertData["export"] = cc[o]["export"];
                                insertData["order"] = cc[o]["order"];
                                insertData["filter"] = cc[o]["filter"];
                                insertData["filter_drilldown"] = cc[o]["filter_drilldown"];
                                insertData["width"] = cc[o]["width"];
                                insertData["label"] = cc[o]["label"];
                                break;
                            }
                        }
                    }

                    var u = new this.columnStore.recordType(insertData);
                    this.columnStore.add([u]);
                }
            }
        }
    },

    getValues: function() {
        var allValues = this.generalDefinitionForm.getForm().getFieldValues();

        var chartValues = this.chartDefinitionForm.getForm().getFieldValues();
        for (var key in chartValues) {
            allValues[key] = chartValues[key];
        }

        var columnData = [];
        this.columnStore.each(function (rec) {
            columnData.push(rec.data);
        }.bind(this));

        allValues["columnConfiguration"] = columnData;

        var dataSourceConfig = [];
        for(var i = 0; i < this.currentElements.length; i++) {
            if(!this.currentElements[i].deleted) {
                dataSourceConfig.push(this.currentElements[i].adapter.getValues());
            }
        }

        allValues["dataSourceConfig"] = dataSourceConfig;
        allValues["sql"] = "";

        return allValues;
    },

    save: function () {

        var m = this.getValues();

        Ext.Ajax.request({
            url: "/admin/reports/custom-report/update",
            method: "post",
            params: {
                configuration: Ext.encode(m),
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getRootNode().reload();
        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");

        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
            if (buttonValue == "yes") {
                window.location.reload();
            }
        }.bind(this));
    }
});
