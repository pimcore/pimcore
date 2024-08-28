/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.customreports.custom.item");
/**
 * @private
 */
pimcore.bundle.customreports.custom.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentElements = [];
        this.currentElementCount = 0;
        this.addLayout();
    },


    addLayout: function () {

        const panelButtons = [];

        const buttonConfig = {
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this),
            disabled: !this.data.writeable
        };

        if (!this.data.writeable) {
            buttonConfig.tooltip = t("config_not_writeable");
        }

        panelButtons.push(buttonConfig);

        this.columnStore = Ext.create('Ext.data.Store', {
            autoDestroy: false,
            proxy: {
                type: 'memory'
            },
            data: [],
            fields: [
                'name',
                'disableOrderBy',
                'disableFilterable',
                'disableDropdownFilterable',
                'filter',
                'displayType',
                'filter_drilldown',
                'display',
                'export',
                'order',
                'width',
                'label',
                'columnAction'
            ]
        });

        const checkDisplay = new Ext.grid.column.Check({
            text: t("display"),
            dataIndex: "display",
            width: 50
        });

        const checkExport = new Ext.grid.column.Check({
            text: t("export"),
            dataIndex: "export",
            width: 50
        });

        const checkOrder = new Ext.grid.column.Check({
            text: t('order'),
            dataIndex: 'order',
            width: 50,
            listeners: {
                beforecheckchange: function(checkColumn, rowIndex, checked, record) {
                    if (record.get('disableOrderBy') === true) {
                        return false;
                    }
                }
            },
            renderer:function (value, metaData, record, rowIndex, colIndex, store) {
                if (record.get('disableOrderBy') === true) {
                    metaData.tdCls = ' grid_cbx_noteditable';
                }
                return Ext.String.format('<div style="text-align: center"><div role="button" class="x-grid-checkcolumn{0}" style=""></div></div>', value ? '-checked' : '');
            }
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, context) {
                    if (
                        (context.field === 'filter' && context.record.get('disableFilterable') === true) ||
                        (context.field === 'filter_dropdown' && context.record.get('disableDropdownFilterable') === true)
                    ) {
                        return false;
                    }
                }
            },
        });

        const actionStore = new Ext.data.SimpleStore({
            fields: ['key', 'name'],
            data: [
                ["", t("none")],
                ["openDocument", t("open_document_by_id")],
                ["openAsset", t("open_asset_by_id")],
                ["openObject", t("open_object_by_id")],
                ["openUrl", t("open_url")]
            ]
        });

        const displayStore = new Ext.data.SimpleStore({
            fields: ['key', 'name'],
            data: [
                ["", t("none")],
                ["text", t("text")],
                ["date", t("date")],
                ["hide", t("hide")]
            ]
        });

        const disableRenderer = function (fieldName, value, metaData, record, rowIndex, colIndex, store, view) {
            if (record.get(fieldName) === true) {
                metaData.tdCls = ' grid_cbx_noteditable';
            }
            return value;
        };

        this.columnGrid = Ext.create('Ext.grid.Panel', {
            store: this.columnStore,
            plugins: [
                this.cellEditing
            ],
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop'
                }
            },
            columns: [
                {text: t("name"), sortable: false, dataIndex: 'name', editable: false, width: 200},
                checkDisplay,
                checkExport,
                checkOrder,
                {
                    text: t("filter_type"),
                    width: 100,
                    sortable: false,
                    dataIndex: 'filter',
                    editable: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ["", t("empty")],
                            ["string", t("text")],
                            ["numeric", t("numeric")],
                            ["date", t("date")],
                            ["boolean", t("bool")]
                        ],
                        queryMode: 'local',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        triggerAction: "all"
                    }),
                    renderer: disableRenderer.bind(this, 'disableFilterable')
                },
                {
                    text: t("display_type"),
                    width: 100,
                    sortable: false,
                    dataIndex: 'displayType',
                    editable: true,
                    editor: new Ext.form.ComboBox({
                        store: displayStore,
                        valueField: "key",
                        displayField: 'name',
                        queryMode: 'local',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        triggerAction: "all"

                    }),

                    renderer: function (value, metaData, record, rowIndex, colIndex, store, view) {
                        try {
                            var rec = displayStore.findRecord("key", value);
                            if (rec) {
                                return rec.get("name");
                            }
                        }
                        catch (e) {
                        }

                        return value;
                    }
                },
                {
                    text: t("custom_report_filter_drilldown"),
                    width: 100,
                    sortable: false,
                    dataIndex: 'filter_drilldown',
                    editable: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            //["date", t("date")],
                            ["", t("empty")],
                            ["only_filter", t("custom_report_only_filter")],
                            ["filter_and_show", t("custom_report_filter_and_show")]
                        ],
                        queryMode: 'local',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        triggerAction: "all"
                    }),
                    renderer: disableRenderer.bind(this, 'disableDropdownFilterable')
                },
                {
                    text: t("width"),
                    sortable: false,
                    dataIndex: 'width',
                    editable: true,
                    width: 70,
                    editor: new Ext.form.field.Number({
                        decimalPrecision: 0
                    })
                },
                {
                    text: t("label"),
                    sortable: false,
                    dataIndex: 'label',
                    editable: true,
                    width: 150,
                    editor: new Ext.form.TextField({})
                },
                {
                    text: t("action"), width: 160, sortable: true, dataIndex: 'columnAction',
                    editor: new Ext.form.ComboBox({
                        store: actionStore,
                        valueField: "key",
                        displayField: 'name',
                        queryMode: 'local',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        triggerAction: "all",
                    })
                    ,
                    renderer: function (value, metaData, record, rowIndex, colIndex, store, view) {
                        try {
                            var rec = actionStore.findRecord("key", value);
                            if (rec) {
                                return rec.get("name");
                            }
                        }
                        catch (e) {
                        }

                        return value;
                    },
                    filter: 'string'
                }, {
                    xtype: 'actioncolumn',
                    menuText: t('up'),
                    width: 30,
                    items: [
                        {
                            tooltip: t('up'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/up.svg",
                            handler: function (grid, rowIndex) {
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
                    xtype: 'actioncolumn',
                    menuText: t('down'),
                    width: 30,
                    items: [
                        {
                            tooltip: t('down'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/down.svg",
                            handler: function (grid, rowIndex) {
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
            bodyCls: 'pimcore_editable_grid',
            title: t('column_configuration')
        });

        this.panel = new Ext.Panel({
            region: "center",
            id: "pimcore_sql_panel_" + this.data.name,
            labelWidth: 150,
            autoScroll: true,
            border: false,
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

        const user = pimcore.globalmanager.get("user");
        if (user.isAllowed("share_configurations")) {
            this.panel.add(this.getPermissionPanel());
        }

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        pimcore.layout.refresh();
    },

    getGeneralDefinitionPanel: function () {
        this.generalDefinitionForm = new Ext.form.FormPanel({
            border: false,
            items: [{
                xtype: "fieldset",
                itemId: "generalFieldset",
                title: t("general"),
                collapsible: false,
                defaults: {
                    width: 400
                },
                items: [{
                    xtype: "textfield",
                    name: "name",
                    value: this.data.name,
                    fieldLabel: t("name"),
                    disabled: true
                }, {
                    xtype: "textfield",
                    name: "niceName",
                    value: this.data.niceName,
                    fieldLabel: t("nice_name")
                }, {
                    xtype: "textfield",
                    name: "iconClass",
                    value: this.data.iconClass,
                    fieldLabel: t("icon_class")
                }, {
                    xtype: "textfield",
                    name: "group",
                    value: this.data.group,
                    fieldLabel: t("group")
                }, {
                    xtype: "textfield",
                    name: "reportClass",
                    value: this.data.reportClass,
                    fieldLabel: t("custom_report_class")
                }, {
                    xtype: "textfield",
                    name: "groupIconClass",
                    value: this.data.groupIconClass,
                    fieldLabel: t("group_icon_class")
                }, {
                    xtype: "checkbox",
                    name: "menuShortcut",
                    checked: this.data.menuShortcut,
                    fieldLabel: t("create_menu_shortcut")
                }
                ]
            }]
        });

        return this.generalDefinitionForm;
    },

    getChartDefinitionPanel: function () {

        var chartTypeSelector = new Ext.form.ComboBox({
            triggerAction: 'all',
            lazyRender: true,
            queryMode: 'local',
            name: 'chartType',
            fieldLabel: t('custom_report_charttype'),
            value: this.data.chartType,
            store: new Ext.data.ArrayStore({
                fields: [
                    'chartType',
                    'text'
                ],
                data: [['', t('custom_report_charttype_none')], ['pie', t('custom_report_charttype_pie')], ['line', t('custom_report_charttype_line')], ['bar', t('custom_report_charttype_bar')]]
            }),
            valueField: 'chartType',
            displayField: 'text',
            listeners: {
                afterrender: function () {
                    this.updateTypeSpecificCartDefinitionPanel(this.data.chartType);
                }.bind(this),
                select: function (combo, record, index) {
                    var chartType = combo.getValue();
                    this.updateTypeSpecificCartDefinitionPanel(chartType);
                }.bind(this)
            }
        });

        this.pieChartDefinitionPanel = this.getPieChartDefinitionPanel();
        this.lineChartDefinitionPanel = this.getLineChartDefinitionPanel();

        this.chartDefinitionForm = new Ext.form.FormPanel({
            border: false,
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

    getPermissionPanel: function () {

        var items = [];

        var user = pimcore.globalmanager.get("user");

        if (user.admin) {
            var shareGlobally = new Ext.form.field.Checkbox(
                {
                    fieldLabel: t("share_globally"),
                    inputValue: true,
                    name: "shareGlobally",
                    value: this.data.shareGlobally
                }
            );

            items.push(shareGlobally);
        }

        if (user.isAllowed("share_configurations")) {

            var userStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_user_getusersforsharing'),
                    extraParams: {
                        include_current_user: true,
                        permission: 'reports'
                    },
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id',
                    }
                },
                fields: ['id', 'label']
            });

            var userSharingField = Ext.create('Ext.form.field.Tag', {
                name: "sharedUserNames",
                width: '100%',
                height: 100,
                fieldLabel: t("visible_to_users"),
                queryDelay: 0,
                resizable: true,
                queryMode: 'local',
                minChars: 1,
                store: userStore,
                displayField: 'label',
                valueField: 'label',
                forceSelection: true,
                filterPickList: true,
                value: this.data.sharedUserNames ? this.data.sharedUserNames : ""
            });
            items.push(userSharingField);

            var rolesStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_user_getrolesforsharing'),
                    extraParams: {
                        permission: 'reports'
                    },
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['id', 'label']
            });

            var rolesSharingField = Ext.create('Ext.form.field.Tag', {
                name: "sharedRoleNames",
                width: '100%',
                height: 100,
                fieldLabel: t("visible_to_roles"),
                queryDelay: 0,
                resizable: true,
                queryMode: 'local',
                minChars: 1,
                store: rolesStore,
                displayField: 'label',
                valueField: 'label',
                forceSelection: true,
                filterPickList: true,
                value: this.data.sharedRoleNames ? this.data.sharedRoleNames : ""
            });
            items.push(rolesSharingField);
        }

        if (user.isAllowed("share_configurations")) {
            items.push(this.userSharingField);
            items.push(this.rolesSharingField);
        }

        this.permissionsForm = new Ext.form.FormPanel({
            border: false,
            items: [{
                xtype: 'fieldset',
                itemId: "permissionFieldSet",
                title: t("custom_report_permissions"),
                style: "margin-top: 20px;margin-bottom: 20px",
                collapsible: false,
                items: items
            }]
        });

        return this.permissionsForm;
    },

    updateTypeSpecificCartDefinitionPanel: function (chartType) {
        this.pieChartDefinitionPanel.setVisible(false);
        this.lineChartDefinitionPanel.setVisible(false);

        if (chartType == "pie") {
            this.pieChartDefinitionPanel.setVisible(true);
        }
        if (chartType == "line" || chartType == "bar") {
            this.lineChartDefinitionPanel.setVisible(true);
        }

        this.chartDefinitionForm.updateLayout();
    },

    getPieChartDefinitionPanel: function () {
        return new Ext.form.FieldSet({
            title: t("custom_report_chart_options"),
            hidden: true,
            style: "margin-top: 20px;margin-bottom: 20px",
            collapsible: false,
            items: [new Ext.form.ComboBox({
                triggerAction: 'all',
                lazyRender: false,
                name: 'pieLabelColumn',
                value: this.data.pieLabelColumn,
                queryMode: 'local',
                width: 400,
                fieldLabel: t('custom_report_labelcolumn'),
                store: this.columnStore,
                valueField: 'name',
                displayField: 'name'
            }),
                new Ext.form.ComboBox({
                    triggerAction: 'all',
                    lazyRender: true,
                    name: 'pieColumn',
                    value: this.data.pieColumn,
                    queryMode: 'local',
                    width: 400,
                    fieldLabel: t('custom_report_datacolumn'),
                    store: this.columnStore,
                    valueField: 'name',
                    displayField: 'name'
                })
            ]
        });
    },

    getLineChartDefinitionPanel: function () {
        return new Ext.form.FieldContainer({
            title: t("custom_report_chart_options"),
            hidden: true,
            style: "margin-top: 20px;margin-bottom: 20px",
            collapsible: false,
            listeners: {
                afterrender: function () {
                    if (this.data.yAxis && this.data.yAxis.length > 1) {
                        for (var i = 1; i < this.data.yAxis.length; i++) {
                            this.addAdditionalYAxis(this.data.yAxis[i]);
                        }
                    }
                }.bind(this)
            },
            items: [
                new Ext.form.ComboBox({
                    triggerAction: 'all',
                    lazyRender: true,
                    name: 'xAxis',
                    queryMode: 'local',
                    width: 400,
                    value: this.data.xAxis,
                    fieldLabel: t('custom_report_x_axis'),
                    store: this.columnStore,
                    valueField: 'name',
                    displayField: 'name'
                }), {
                    xtype: "fieldcontainer",
                    layout: 'hbox',
                    fieldLabel: t("custom_report_y_axis"),
                    items: [{
                        xtype: "combo",
                        triggerAction: 'all',
                        lazyRender: true,
                        name: 'yAxis',
                        queryMode: 'local',
                        width: 295,
                        store: this.columnStore,
                        value: this.data.yAxis ? this.data.yAxis[0] : null,
                        valueField: 'name',
                        displayField: 'name'
                    }, {
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

    addAdditionalYAxis: function (value) {
        this.lineChartDefinitionPanel.add({
            xtype: "fieldcontainer",
            layout: 'hbox',
            fieldLabel: t("custom_report_y_axis"),
            items: [{
                xtype: "combo",
                triggerAction: 'all',
                lazyRender: true,
                name: 'yAxis',
                queryMode: 'local',
                width: 295,
                store: this.columnStore,
                value: value ? value : null,
                valueField: 'name',
                displayField: 'name'
            }, {
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                handler: function (button) {
                    this.lineChartDefinitionPanel.remove(button.findParentByType('fieldcontainer'));
                    this.lineChartDefinitionPanel.updateLayout();
                }.bind(this)
            }]
        });
        this.lineChartDefinitionPanel.updateLayout();
    },

    getSourceDefinitionPanel: function () {

        this.sourceDefinitionsItems = new Ext.Panel({
            style: "margin-bottom: 20px",
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
                    fieldStyle: "color: red;"
                }
            ]
        });

        if (this.data.dataSourceConfig) {
            for (var i = 0; i < this.data.dataSourceConfig.length; i++) {
                if (this.data.dataSourceConfig[i]) {
                    this.addSourceDefinition(this.data.dataSourceConfig[i]);
                }
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

    removeSourceDefinition: function (key) {
        for (var i = 0; i < this.currentElements.length; i++) {
            if (this.currentElements[i].key == key) {
                this.currentElements[i].deleted = true;
                this.sourceDefinitionsItems.remove(this.currentElements[i].adapter.getElement());
            }
        }
        this.currentElementCount--;
        this.sourceDefinitionsItems.remove(this.sourceDefinitionsItems.getComponent(0));
        this.sourceDefinitionsItems.insert(0, this.getAddControl());
        this.sourceDefinitionsItems.updateLayout();
    },

    getAddControl: function () {
        var classMenu = [];

        if (this.currentElementCount < 1) {

            var definitionNames = Object.keys(pimcore.bundle.customreports.custom.definition);
            for(var i = 0; i < definitionNames.length; i++) {
                classMenu.push(
                    {
                        text: t("custom_report_adapter_" + definitionNames[i], ucfirst(definitionNames[i])),
                        handler: this.addSourceDefinition.bind(this, {type: definitionNames[i]}),
                        iconCls: "pimcore_icon_objectbricks"
                    }
                );
            }
        }

        var items = [];

        if (classMenu.length == 1) {
            items.push({
                cls: "pimcore_block_button_plus",
                text: t(classMenu[0].text),
                iconCls: "pimcore_icon_plus",
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
        this.sourceDefinitionsItems.remove(this.sourceDefinitionsItems.getComponent(0));

        if (!this.currentElements) {
            this.currentElements = [];
        }

        var key = this.currentElements.length;

        sourceDefinitionData.type = sourceDefinitionData.type ? sourceDefinitionData.type : 'sql';

        var adapter = new pimcore.bundle.customreports.custom.definition[sourceDefinitionData.type](sourceDefinitionData, key, this.getDeleteControl(t("custom_report_adapter_" + sourceDefinitionData.type, ucfirst(sourceDefinitionData.type)), key), this.getColumnSettings.bind(this));


        this.currentElements.push({key: key, adapter: adapter});
        this.currentElementCount++;

        this.sourceDefinitionsItems.add(adapter.getElement());
        this.sourceDefinitionsItems.insert(0, this.getAddControl());
        this.sourceDefinitionsItems.updateLayout();
    },

    getColumnSettings: function () {
        var m = this.getValues();
        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_customreports_customreport_columnconfig'),
            method: "post",
            params: {
                configuration: Ext.encode(m.dataSourceConfig),
                name: this.data.name
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);

                if (res.success) {
                    this.updateColumnSettings(res.columns);
                }

                var errorField = this.panel.getComponent("sourcedefinitionFieldset").getComponent("errorMessage");

                errorField.setValue("");
                if (!res.success && res.errorMessage) {
                    errorField.setValue(res.errorMessage);
                }
            }.bind(this)
        });
    },

    updateColumnSettings: function (columns) {

        var insertData, isInStore, o;
        var cc = this.data.columnConfiguration;

        if (columns && columns.length > 0) {
            // cleanup
            this.columnStore.each(function (columns, rec) {
                if (!in_array(rec.get("name"), columns)) {
                    this.columnStore.remove(rec);
                }
            }.bind(this, columns));

            // insert
            for (let i = 0; i < columns.length; i++) {
                isInStore = (this.columnStore.findExact("name", columns[i].name) >= 0) ? true : false;
                if (!isInStore) {

                    insertData = {
                        name: columns[i].name,
                        disableOrderBy: columns[i].disableOrderBy,
                        disableFilterable: columns[i].disableFilterable,
                        disableDropdownFilterable: columns[i].disableDropdownFilterable,
                        display: true,
                        export: true,
                        order: !columns[i].disableOrderBy,
                        width: "",
                        label: ""
                    };

                    if (typeof cc == "object" && cc.length > 0) {
                        for (o = 0; o < cc.length; o++) {
                            if (cc[o]["name"] == columns[i].name) {
                                insertData["display"] = cc[o]["display"];
                                insertData["export"] = cc[o]["export"];
                                insertData["order"] = cc[o]["order"];
                                insertData["filter"] = cc[o]["filter"];
                                insertData["displayType"] = cc[o]["displayType"];
                                insertData["filter_drilldown"] = cc[o]["filter_drilldown"];
                                insertData["width"] = cc[o]["width"];
                                insertData["label"] = cc[o]["label"];
                                insertData["columnAction"] = cc[o]["columnAction"];
                                break;
                            }
                        }
                    }

                    this.columnStore.add(insertData);
                }
            }
        }
    },

    getValues: function () {
        var key;
        var allValues = this.generalDefinitionForm.getForm().getFieldValues();

        var chartValues = this.chartDefinitionForm.getForm().getFieldValues();
        for (key in chartValues) {
            allValues[key] = chartValues[key];
        }

        if(this.permissionsForm) {
            var permissionValues = this.permissionsForm.getForm().getFieldValues();
            for (key in permissionValues) {
                allValues[key] = permissionValues[key];
            }
        }

        var columnData = [];
        this.columnStore.each(function (rec) {
            columnData.push(rec.data);
        }.bind(this));

        allValues["columnConfiguration"] = columnData;

        var dataSourceConfig = [];
        for (var i = 0; i < this.currentElements.length; i++) {
            if (!this.currentElements[i].deleted) {
                dataSourceConfig.push(this.currentElements[i].adapter.getValues());
            }
        }

        allValues["dataSourceConfig"] = dataSourceConfig;
        allValues["sql"] = "";

        return allValues;
    },

    checkMandatoryFields: function () {
        let adapterValues = {};
        let errorFields = [];
        for (let i = 0; i < this.currentElements.length; i++) {
            if (!this.currentElements[i].deleted) {
                adapterValues = this.currentElements[i].adapter.getValues();
                for(let field of this.currentElements[i].adapter.fieldsToCheck) {
                    if(!adapterValues[field.name] || adapterValues[field.name] === '') {
                        errorFields.push(field.label)
                    }
                }
            }
        }

        if(errorFields.length > 0) {
            throw new Error(`${t("mandatory_fields_missing")} ${errorFields.join(', ')}`)
        }
    },

    save: function () {

        try {
            this.checkMandatoryFields();
        } catch (error) {
            Ext.Msg.show({
                title: t("error"),
                msg: error,
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR
            });

            return;
        }

        let m = this.getValues();
        let error = false;

        ['groupIconClass', 'iconClass', 'reportClass'].forEach(function (name) {
            if(m[name].length && !m[name].match(/^[_a-zA-Z]+[_a-zA-Z0-9-.\s]*$/)) {
                error = name;
            }
        });

        if(error !== false) {
            Ext.Msg.show({
                title: t("error"),
                msg: t('class_field_name_error') + ': ' + error,
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR
            });

            return;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_customreports_customreport_update'),
            method: "PUT",
            params: {
                configuration: Ext.encode(m),
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");

        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
            if (buttonValue == "yes") {
                window.location.reload();
            } else {
                this.parentPanel.tree.getStore().load();
            }
        }.bind(this));
    }
});
