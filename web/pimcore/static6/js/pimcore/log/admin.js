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

pimcore.registerNS("pimcore.log.admin");
pimcore.log.admin = Class.create({
    refreshInterval : 5,

    searchParams: {},
    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_applicationlog_admin");
    },

    getTabPanel: function () {
        if(!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_applicationlog_admin",
                title: t("log_applicationlog"),
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_log_admin",
                closable:true
            });

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



            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_applicationlog_admin");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("pimcore_applicationlog_admin");
            }.bind(this));

            var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                '/admin/log/show?',
                [
                    'id', 'pid', 'message', 'priority', 'timestamp', 'fileobject', 'filename', 'component', 'relatedobject', 'source'
                ],
                itemsPerPage
            );
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
                    header: t("log_timestamp"),
                    dataIndex: 'timestamp',
                    width: 150,
                    align: 'left',
                    sortable: true
                },{
                    header: t("log_pid"),
                    dataIndex: 'pid',
                    flex: 40,
                    sortable: true,
                    hidden: true
                },{
                    id: 'p_message',
                    header: t("log_message"),
                    dataIndex: 'message',
                    flex: 220,
                    sortable: true
                },{
                    header: t("log_type"),
                    dataIndex: 'priority',
                    flex: 25,
                    sortable: true
                },{
                    header: t("log_fileobject"),
                    dataIndex: 'fileobject',
                    flex: 70,
                    renderer: function(value, p, record){
                        return Ext.String.format('<a href="/admin/log/show-file-object?filePath={0}" target="_blank">{1}</a>', record.data.fileobject, t("open"));
                    },
                    sortable: true
                },{
                    header: t("log_relatedobject"),
                    dataIndex: 'relatedobject',
                    flex: 20,
                    sortable: false
                },{
                    header: t("log_component"),
                    dataIndex: 'component',
                    flex: 50,
                    sortable: true
                },{
                    header: t("log_source"),
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
                    }
                },

                listeners: {
                    rowdblclick : function(grid, record, tr, rowIndex, e, eOpts ) {
                        new pimcore.log.detailwindow(this.store.getAt(rowIndex).data);
                    }.bind(this)
                },

                // paging bar on the bottom
                bbar: this.pagingToolbar

            });

            this.fromDate = new Ext.form.DateField({
                id: 'from_date',
                name: 'from_date',
                width: 130,
                xtype: 'datefield'
            });

            this.fromTime = new Ext.form.TimeField({
                id: 'from_time',
                name: 'from_time',
                width: 100,
                xtype: 'timefield'
            });

            this.toDate = new Ext.form.DateField({
                id: 'to_date',
                name: 'to_date',
                width: 130,
                xtype: 'datefield'
            });

            this.toTime = new Ext.form.TimeField({
                id: 'to_time',
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
                buttons: [{
                    text: t("log_reset_search"),
                    handler: this.clearValues.bind(this),
                    iconCls: "pimcore_icon_stop"
                },{
                    text: t("log_search"),
                    handler: this.find.bind(this),
                    iconCls: "pimcore_icon_search"
                }],
                items: [ {
                    xtype:'fieldset',
                    id:'log_search_form',
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
                            store: new Ext.data.JsonStore({
                                autoDestroy: true,
                                proxy: {
                                    type: 'ajax',
                                    url: '/admin/log/priority-json',
                                    reader: {
                                        rootProperty: 'priorities',
                                        idProperty: 'key'
                                    }
                                },
                                autoLoad: true,
                                fields: ['key', 'value']
                            }),
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
                            store: new Ext.data.JsonStore({
                                autoDestroy: true,
                                proxy: {
                                    type: 'ajax',
                                    url: '/admin/log/component-json',
                                    reader: {
                                        type: 'json',
                                        rootProperty: 'components',
                                        idProperty: 'key'
                                    }
                                },
                                autoLoad: true,
                                fields: ['key', 'value']
                            }),
                            displayField: 'value',
                            valueField: 'key'
                        },{
                            xtype:'textfield',
                            name: 'relatedobject',
                            fieldLabel: t('log_search_relatedobject'),
                            width: 335,
                            listWidth: 150
                        },{
                            xtype:'textfield',
                            name: 'message',
                            fieldLabel: t('log_search_message'),
                            width: 335,
                            listWidth: 150
                        }]
                }]});

            this.layout = new Ext.Panel({
                border: false,
                layout: "border",
                items: [this.searchpanel, this.resultpanel],
            });


            this.panel.add(this.layout);
            this.store.load();
            pimcore.layout.refresh();
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
        this.store.baseParams = this.searchParams;
        this.store.reload({
            params:this.searchParams
        });
    },


    find: function(){
        var formValues = this.searchpanel.getForm().getFieldValues();

        this.searchParams.fromDate = this.fromDate.getValue();
        this.searchParams.fromTime = this.fromTime.getValue();
        this.searchParams.toDate = this.toDate.getValue();
        this.searchParams.toTime = this.toTime.getValue();
        this.searchParams.priority = formValues.priority;
        this.searchParams.component = formValues.component;
        this.searchParams.relatedobject = formValues.relatedobject;
        this.searchParams.message = formValues.message;

        var proxy = this.store.getProxy();
        proxy.extraParams = this.searchParams;
        //this.store.baseParams = this.searchParams;

        this.pagingToolbar.moveFirst();
    }


});
