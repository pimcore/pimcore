pimcore.registerNS("pimcore.log.admin");
pimcore.log.admin = Class.create({

    searchParams: {start: 0, limit: 25},

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

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_applicationlog_admin");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("pimcore_applicationlog_admin");
            }.bind(this));

            var itemsPerPage = 20;
            this.store = pimcore.helpers.grid.buildDefaultStore(
                '/admin/log/show?',
                [
                    'id', 'message', 'priority', 'timestamp', 'fileobject', 'filename', 'component', 'relatedobject', 'source'
                ],
                itemsPerPage
            );
            var reader = this.store.getProxy().getReader();
            reader.setRootProperty('p_results');
            reader.setTotalProperty('p_totalCount');

            this.pagingToolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);

            this.resultpanel = new Ext.grid.GridPanel({
                    store: this.store,
                    title: t("log_applicationlog"),
                    trackMouseOver:false,
                    disableSelection:true,
                    loadMask: true,
                    autoScroll: true,
                    region: "center",
                    columns:[{
                        header: t("log_timestamp"),
                        dataIndex: 'timestamp',
                        width: 150,
                        align: 'left',
                        /*hidden: true,*/
                        sortable: true
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
                            return Ext.String.format('<a href="{0}" target="_blank">{1}</a>', record.data.fileobject, record.data.fileobject);
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
                    iconCls: "pimcore_icon_cancel"
                },{
                    text: t("log_search"),
                    handler: this.find.bind(this),
                    iconCls: "pimcore_icon_tab_search"
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
                    items: [this.searchpanel, this.resultpanel]
            });


            this.panel.add(this.layout);
            this.store.load();
            //this.store.load({params:this.searchParams});
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

        //this.store.reload();
    }


});
