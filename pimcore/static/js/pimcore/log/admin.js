pimcore.registerNS("pimcore.log.admin");

pimcore.log.admin = Class.create({

    searchParams: {start: 0, limit: 25},

    initialize: function () {
        console.log('init');
        this.getTabPanel();
    },

    activate: function () {
        console.log('blub');
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_applicationlog_admin");
        console.log(tabPanel.getItem("pimcore_applicationlog_admin"));
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

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_applicationlog_admin");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("pimcore_applicationlog_admin");
            }.bind(this));


            // create the Data Store
            this.store = new Ext.data.JsonStore({
                root: 'p_results',
                totalProperty: 'p_totalCount',
                idProperty: 'id',
                remoteSort: true,
                fields: [
                    'id', 'message', 'priority', 'timestamp', 'fileobject', 'filename', 'component', 'relatedobject', 'source'
                ],
                url: '/admin/log/show'
            });

            function renderLink(value, p, record){
                return String.format('<a href="{0}" target="_blank">{1}</a>', record.data.fileobject, record.data.fileobject);
            }

            this.resultpanel = new Ext.grid.GridPanel({
                //autoHeight: true,
                store: this.store,
                title: t("log_applicationlog"),
                trackMouseOver:false,
                disableSelection:true,
                loadMask: true,
                autoScroll: true,
                region: "center",
                // grid columns
                columns:[{
                    header: t("log_timestamp"),
                    dataIndex: 'timestamp',
                    width: 40,
                    align: 'left',
                    /*hidden: true,*/
                    sortable: true
                },{
                    id: 'p_message',
                    header: t("log_message"),
                    dataIndex: 'message',
                    width: 220,
                    sortable: true
                },{
                    header: t("log_type"),
                    dataIndex: 'priority',
                    width: 15,
                    sortable: true
                },{
                    header: t("log_fileobject"),
                    dataIndex: 'fileobject',
                    width: 70,
                    renderer: renderLink,
                    sortable: true
                },{
                    header: t("log_relatedobject"),
                    dataIndex: 'relatedobject',
                    width: 15,
                    sortable: false
                },{
                    header: t("log_component"),
                    dataIndex: 'component',
                    width: 50,
                    sortable: true
                },{
                    header: t("log_source"),
                    dataIndex: 'source',
                    width: 50,
                    sortable: true
                }],

                // customize view config
                viewConfig: {
                    forceFit:true,
                    enableRowBody:false,
                    showPreview:false,
                    getRowClass: function(record) {
                        return 'pimcore-log-type-' + record.get('priority');
                    }
                },

                listeners: {
                    rowdblclick : function(grid, rowIndex, event ) {
                        new pimcore.log.detailwindow(this.store.getAt(rowIndex).data);
                    }.bind(this)
                },

                // paging bar on the bottom
                bbar: new Ext.PagingToolbar({
                    pageSize: this.searchParams.limit,
                    store: this.store,
                    displayInfo: true,
                    displayMsg: '{0} - {1} / {2}',
                    emptyMsg: t("no_items_found"),
                    items:[]
                })
            });

            this.fromDate = new Ext.form.DateField({
                id: 'from_date',
                name: 'from_date',
                xtype: 'datefield'
            });

            this.fromTime = new Ext.form.TimeField({
                id: 'from_time',
                name: 'from_time',
                width: 60,
                xtype: 'timefield'
            });

            this.toDate = new Ext.form.DateField({
                id: 'to_date',
                name: 'to_date',
                xtype: 'datefield'
            });

            this.toTime = new Ext.form.TimeField({
                id: 'to_time',
                name: 'to_time',
                width: 60,
                xtype: 'timefield'
            });

            this.searchpanel = new Ext.FormPanel({
                region: "east",
                title: t("log_search_form"),
                width: 340,
                height: 500,
                border: true,
                autoScroll: true,
                buttons: [{
                    text: t("log_reset_search"),
                    handler: this.clearValues.bind(this),
                    iconCls: "pimcore_icon_cancel"
                },{
                    text: t("log_search"),
                    handler: this.find.bind(this),
                    iconCls: "pimcore_icon_tab_search"
                }],
                items: [ {
                    xtype:'fieldset',
                    //title: t('carsearch_parameters6'),
                    id:'log_search_form',
                    //collapsible: true,
                    autoHeight:true,
                    labelWidth: 140,
                    items :[
                        {
                            xtype: 'compositefield',
                            fieldLabel: t('log_search_from'),
                            combineErrors: true,
                            id: 'from',
                            name: 'from',
                            items: [this.fromDate, this.fromTime]
                        },{
                            xtype: 'compositefield',
                            fieldLabel: t('log_search_to'),
                            combineErrors: true,
                            id: 'to',
                            name: 'to',
                            items: [this.toDate, this.toTime]
                        },{
                            xtype:'combo',
                            id: 'priority',
                            name: 'priority',
                            fieldLabel: t('log_search_type'),
                            width: 150,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead:true,
                            forceSelection: true,
                            triggerAction: 'all',
                            store: new Ext.data.JsonStore({
                                autoDestroy: true,
                                url: '/admin/log/priority-json',
                                root: 'priorities',
                                autoLoad: true,
                                idProperty: 'key',
                                fields: ['key', 'value']
                            }),
                            displayField: 'value',
                            valueField: 'key'
                        },{
                            xtype:'combo',
                            id: 'component',
                            name: 'component',
                            fieldLabel: t('log_search_component'),
                            width: 150,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead:true,
                            forceSelection: true,
                            triggerAction: 'all',
                            store: new Ext.data.JsonStore({
                                autoDestroy: true,
                                url: '/admin/log/component-json',
                                root: 'components',
                                autoLoad: true,
                                idProperty: 'key',
                                fields: ['key', 'value']
                            }),
                            displayField: 'value',
                            valueField: 'key'
                        },{
                            xtype:'textfield',
                            id: 'relatedobject',
                            name: 'relatedobject',
                            fieldLabel: t('log_search_relatedobject'),
                            width: 150,
                            listWidth: 150
                        },{
                            xtype:'textfield',
                            id: 'message',
                            name: 'message',
                            fieldLabel: t('log_search_message'),
                            width: 150,
                            listWidth: 150
                        }]
                }]});

            this.layout = new Ext.Panel({
                border: false,
                layout: "border",
                items: [this.searchpanel, this.resultpanel]
            });


            this.panel.add(this.layout);
            this.store.load({params:this.searchParams});
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

        this.store.baseParams = this.searchParams;

        this.store.reload({
            params:this.searchParams
        });
    }


});
