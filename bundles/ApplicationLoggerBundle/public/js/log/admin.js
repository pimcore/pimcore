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

pimcore.registerNS("pimcore.bundle.applicationlogger.log.admin");
pimcore.bundle.applicationlogger.log.admin = Class.create({

    initialize: function (config) {

        this.panel = null;
        this.config = {
            searchParams: {},
            refreshInterval: 5
        };

        Ext.apply(this.config, config);
        this.searchParams = this.config.searchParams;
        this.refreshInterval = this.config.refreshInterval;

        if(!this.config['localMode']) {
            this.getTabPanel();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_applicationlog_admin");
    },

    getTabPanel: function () {
        if(!this.panel) {

            var panelConfig = {
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_log_admin",
            };

            if (!this.config.localMode) {
                panelConfig.title = t("log_applicationlog");
                panelConfig.id =  "pimcore_applicationlog_admin";
                panelConfig.closable = true;
            } else {
                panelConfig.tooltip = t("log_applicationlog");
            }

            this.panel = new Ext.Panel(panelConfig);

            this.autoRefreshTask = {
                run: function(){
                    this.store.reload();
                }.bind(this),
                interval: (this.refreshInterval*1000)
            };

            this.intervalInSeconds = {
                xtype: "numberfield",
                name: "interval",
                width: 70,
                value: 5,
                listeners: {
                    change: function (item, value) {
                        if(value < 1){
                            value = 1;
                        }
                        Ext.TaskManager.stop(this.autoRefreshTask);
                        if(this.autoRefresh.getValue()){
                            this.autoRefreshTask.interval = value*1000;
                            Ext.TaskManager.start(this.autoRefreshTask);
                        }

                    }.bind(this)
                }
            }

            this.autoRefresh = new Ext.form.Checkbox({
                stateful: true,
                stateId: 'log_auto_refresh',
                stateEvents: ['click'],
                checked : false,
                boxLabel: t('log_refresh_label'),
                listeners: {
                    change: function (cbx, checked) {
                        if (checked) {
                            // this.resultpanel.view.loadMask.destroy();
                            Ext.TaskManager.start(this.autoRefreshTask);
                        } else {
                            //Todo: enable load mask
                            Ext.TaskManager.stop(this.autoRefreshTask);
                        }
                    }.bind(this)
                }
            });

            this.priorityStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_bundle_applicationlogger_log_priorityjson'),
                    reader: {
                        rootProperty: 'priorities',
                        idProperty: 'key'
                    }
                },
                fields: ['key', 'value']
            });

            this.componentStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_bundle_applicationlogger_log_componentjson'),
                    reader: {
                        type: 'json',
                        rootProperty: 'components',
                        idProperty: 'key'
                    }
                },
                fields: ['key', 'value']
            });

            if (!this.config.localMode) {
                var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                tabPanel.add(this.panel);
                tabPanel.setActiveItem("pimcore_applicationlog_admin");

                this.panel.on("destroy", function () {
                    pimcore.globalmanager.remove("pimcore_applicationlog_admin");
                }.bind(this));
            } else {
                this.panel.on("afterrender", function () {
                    this.priorityStore.load();
                    this.componentStore.load();
                    this.store.load();
                }.bind(this));
            }

            var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('pimcore_admin_bundle_applicationlogger_log_show'),
                [
                    'id', 'pid', 'message', 'priority', 'timestamp', 'fileobject', 'component', 'relatedobject', 'source'
                ],
                itemsPerPage, {
                    autoLoad: false
                }
            );
            if (this.config.localMode && this.searchParams.relatedobject) {
                this.store.getProxy().setExtraParam("relatedobject",this.searchParams.relatedobject);
            }
            var reader = this.store.getProxy().getReader();
            reader.setRootProperty('p_results');
            reader.setTotalProperty('p_totalCount');

            this.pagingToolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);
            //auto reload items
            this.pagingToolbar.insert(11,"-");
            this.pagingToolbar.insert(12,this.autoRefresh);
            this.pagingToolbar.insert(13,this.intervalInSeconds);
            this.pagingToolbar.insert(14,t("log_refresh_seconds"));

            this.resultpanel = new Ext.grid.GridPanel({
                store: this.store,
                title: t("log_applicationlog"),
                trackMouseOver:false,
                disableSelection:true,
                autoScroll: true,
                region: "center",
                columns:[{
                    text: t("log_timestamp"),
                    dataIndex: 'timestamp',
                    width: 150,
                    align: 'left',
                    sortable: true,
                    renderer: function (d) {
                        const localeDateTime = pimcore.globalmanager.get('localeDateTime');
                        return Ext.Date.format(new Date(d*1000), localeDateTime.getDateTimeFormat());
                    }
                },{
                    text: t("log_pid"),
                    dataIndex: 'pid',
                    flex: 40,
                    sortable: true,
                    hidden: true
                },{
                    text: t("log_message"),
                    dataIndex: 'message',
                    flex: 220,
                    sortable: true,
                    renderer: function (s) {
                        return Ext.util.Format.htmlEncode(s);
                    }
                },{
                    text: t("log_type"),
                    dataIndex: 'priority',
                    flex: 25,
                    sortable: true
                },{
                    text: t("log_fileobject"),
                    dataIndex: 'fileobject',
                    flex: 70,
                    renderer: function(value, p, record){
                        if (value) {
                            var url = Routing.generate('pimcore_admin_bundle_applicationlogger_log_showfileobject', {filePath: record.data.fileobject});
                            return Ext.String.format('<a href="{0}" target="_blank">{1}</a>', url,  t("open"));
                        }

                        return '';
                    },
                    sortable: true
                },{
                    text: t("log_relatedobject"),
                    dataIndex: 'relatedobject',
                    flex: 35,
                    sortable: false,
                    renderer: function (value, p, record) {
                        if (value) {
                            return Ext.String.format('<a href="#">{0}</a>', record.get('relatedobjecttype')+' '+value);
                        }

                        return '';
                    }
                },{
                    text: t("log_component"),
                    dataIndex: 'component',
                    flex: 50,
                    sortable: true
                },{
                    text: t("log_source"),
                    dataIndex: 'source',
                    flex: 50,
                    sortable: true
                }],

                // customize view config
                viewConfig: {
                    forceFit:true,
                    // loadMask: false,
                    getRowClass: function(record) {
                        return 'log-type-' + record.get('priority');
                    },
                    enableTextSelection: true
                },

                listeners: {
                    rowdblclick : function(grid, record, tr, rowIndex, e, eOpts ) {
                        new pimcore.bundle.applicationlogger.log.detailwindow(this.store.getAt(rowIndex).data);
                    }.bind(this),
                    cellclick: function(grid,  td, cellIndex, record, tr, rowIndex, e, eOpts) {
                        const row = this.store.getAt(rowIndex);
                        if (cellIndex === 5 && row.data.relatedobject && row.data.relatedobjecttype) { //5 = relatedobject
                            pimcore.helpers.openElement(row.data.relatedobject, row.data.relatedobjecttype);
                        }
                    }.bind(this)
                },

                // paging bar on the bottom
                bbar: this.pagingToolbar

            });

            this.fromDate = new Ext.form.DateField({
                name: 'from_date',
                width: 130,
                xtype: 'datefield'
            });

            this.fromTime = new Ext.form.TimeField({
                name: 'from_time',
                width: 100,
                xtype: 'timefield'
            });

            this.toDate = new Ext.form.DateField({
                name: 'to_date',
                width: 130,
                xtype: 'datefield'
            });

            this.toTime = new Ext.form.TimeField({
                name: 'to_time',
                width: 100,
                xtype: 'timefield'
            });

            this.searchpanel = new Ext.FormPanel({
                region: "east",
                title: t("log_search_form"),
                width: 370,
                height: 500,
                border: false,
                autoScroll: true,
                referenceHolder: true,
                defaultButton: 'log_search_button',
                buttons: [{
                    text: t("reset"),
                    handler: this.clearValues.bind(this),
                    iconCls: "pimcore_icon_stop"
                },{
                    reference: 'log_search_button',
                    text: t("search"),
                    handler: this.find.bind(this),
                    iconCls: "pimcore_icon_search"
                }],
                items: [ {
                    xtype:'fieldset',
                    autoHeight:true,
                    labelWidth: 150,
                    items :[
                        {
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            fieldLabel: t('log_search_from'),
                            combineErrors: true,
                            name: 'from',
                            items: [this.fromDate, this.fromTime]
                        },{
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            fieldLabel: t('log_search_to'),
                            combineErrors: true,
                            name: 'to',
                            items: [this.toDate, this.toTime]
                        },{
                            xtype:'combo',
                            name: 'priority',
                            fieldLabel: t('log_search_type'),
                            width: 335,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead:true,
                            forceSelection: true,
                            triggerAction: 'all',
                            store: this.priorityStore,
                            displayField: 'value',
                            valueField: 'key'
                        },{
                            xtype:'combo',
                            name: 'component',
                            fieldLabel: t('log_search_component'),
                            width: 333,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead:true,
                            forceSelection: true,
                            triggerAction: 'all',
                            store: this.componentStore,
                            displayField: 'value',
                            valueField: 'key'
                        },{
                            xtype:'numberfield',
                            name: 'relatedobject',
                            fieldLabel: t('log_search_relatedobject'),
                            value: this.searchParams.relatedobject ? this.searchParams.relatedobject : "",
                            width: 335,
                            listWidth: 150,
                            disabled: this.config.localMode
                        },{
                            xtype:'textfield',
                            name: 'message',
                            fieldLabel: t('log_search_message'),
                            width: 335,
                            listWidth: 150
                        },{
                            xtype:'numberfield',
                            name: 'pid',
                            fieldLabel: t('log_search_pid'),
                            width: 335,
                            listWidth: 150
                        }]
                }]});

            var layout = new Ext.Panel({
                border: false,
                layout: "border",
                items: [this.searchpanel, this.resultpanel],
            });


            this.panel.add(layout);
            if (!this.config.localMode) {
                this.store.load();
            }
            pimcore.layout.refresh();

            this.panel.on("destroy", function () {
                Ext.TaskManager.stop(this.autoRefreshTask);
            }.bind(this));
        }
        return this.panel;
    },

    clearValues: function(){
        this.searchpanel.getForm().reset();

        this.searchParams.fromDate = null;
        this.searchParams.fromTime = null;
        this.searchParams.toDate = null;
        this.searchParams.toTime = null;
        this.searchParams.priority = null;
        this.searchParams.component = null;
        this.searchParams.message = null;
        this.searchParams.pid = null;
        this.store.baseParams = this.searchParams;
        this.store.reload({
            params:this.searchParams
        });
    },


    find: function() {
        var formValues = this.searchpanel.getForm().getFieldValues();

        this.searchParams.fromDate = this.fromDate.getValue();
        this.searchParams.fromTime = this.fromTime.getValue();
        this.searchParams.toDate = this.toDate.getValue();
        this.searchParams.toTime = this.toTime.getValue();
        this.searchParams.priority = formValues.priority;
        this.searchParams.component = formValues.component;
        if (!this.config.localMode) {
            this.searchParams.relatedobject = formValues.relatedobject;
        }
        this.searchParams.message = formValues.message;
        this.searchParams.pid = formValues.pid;

        var proxy = this.store.getProxy();
        proxy.extraParams = this.searchParams;
        this.pagingToolbar.moveFirst();
    }

});
