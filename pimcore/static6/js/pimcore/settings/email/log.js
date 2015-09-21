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

pimcore.registerNS("pimcore.settings.email.log");
pimcore.settings.email.log = Class.create({

    filterField: null,

    initialize: function(document) {
        this.document = document;

        this.filterField = new Ext.form.TextField({
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            value: this.preconfiguredFilter,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        if(!this.document) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.getLayout());
            tabPanel.setActiveTab(this.getLayout());

            pimcore.layout.refresh();

            this.getLayout().on("destroy", function () {
                pimcore.globalmanager.remove("sent_emails");
            }.bind(this));
        }
    },

    activate: function () {
        // this is only for standalone mode (without document set)
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate(this.getLayout());
    },

    load: function () {
    },

    getLayout: function () {

        if (this.layout == null) {

            this.grid = this.getGrid();

            this.layout = new Ext.Panel({
                title: t('email_logs'),
                border: false,
                layout: "fit",
                items: [this.grid],
                closable: this.document ? false : true,
                iconCls: "pimcore_icon_email_transfer",
                listeners: {
                    activate: function() {
                        this.store.load();
                        this.grid.getView().refresh();
                    }.bind(this)
                }
            });
        }

        return this.layout;
    },

    getGrid: function () {

        var iFrameSettings = { width : 700, height : 500};

        var itemsPerPage = 20;

        var gridColumns = [{
            header: "ID",
            dataIndex: "id",
            flex: 40,
            hidden: true
        },{
            header: "Document Id",
            dataIndex: "documentId",
            flex: 130,
            hidden: true
        },
        {
            header: t('email_log_sent_Date'),
            dataIndex: "sentDate",
            width: 130,
            flex: false,
            sortable: false,
            renderer: function (d) {
                var date = new Date(intval(d) * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            }
        },
        {
            header: t('email_log_from'),
            sortable: false,
            dataIndex: "from",
            flex: 120
        },
        {
            header: t('email_log_to'),
            sortable: false,
            dataIndex: "to",
            flex: 120
        },
        {
            header: t('email_log_cc'),
            sortable: false,
            dataIndex: "cc",
            flex: 120
        },
        {
            header: t('email_log_bcc'),
            sortable: false,
            dataIndex: "bcc",
            flex: 120
        },
        {
            header: t('email_log_subject'),
            sortable: false,
            dataIndex: "subject",
            flex: 220
        },
        {
            xtype: 'actioncolumn',
            sortable: false,
            width: 50,
            dataIndex: "emailLogExistsHtml",
            header: t('email_log_html'),
            items : [{
                tooltip: t('email_log_show_html_email'),
                icon: "/pimcore/static6/img/icon/email_open.png",
                handler: function(grid, rowIndex){
                    var rec = grid.getStore().getAt(rowIndex);
                    var iframe = new Ext.Window({
                        title: t("email_log_iframe_title_html"),
                        width: iFrameSettings.width,
                        height: iFrameSettings.height,
                        layout: 'fit',
                        items : [{
                                xtype : "box",
                                autoEl: {tag: 'iframe', src: "/admin/email/show-email-log/?id=" + rec.get('id')
                                                                                + "&type=html"}
                            }]
                    });
                    iframe.show();
                }.bind(this),
                getClass: function(v, meta, rec) {
                    if(!rec.get('emailLogExistsHtml')){
                        return "pimcore_hidden";
                    }
                }
            }]
        },
        {
            xtype: 'actioncolumn',
            sortable: false,
            width: 50,
            dataIndex: "emailLogExistsText",
            header: t('email_log_text'),
            hidden: true,
            items : [{
                tooltip: t('email_log_show_text_email'),
                icon: "/pimcore/static6/img/icon/text_align_justify.png",
                handler: function(grid, rowIndex){
                    var rec = grid.getStore().getAt(rowIndex);
                    var iframe = new Ext.Window({
                        title: t("email_log_iframe_title_text"),
                        width: iFrameSettings.width,
                        height: iFrameSettings.height,
                        layout: 'fit',
                        items : [{
                                xtype : "box",
                                autoEl: {tag: 'iframe', src: "/admin/email/show-email-log/?id=" + rec.get('id')
                                                                    + "&type=text"}
                            }]
                    });
                    iframe.show();
                }.bind(this),
                getClass: function(v, meta, rec) {
                    if(!rec.get('emailLogExistsText')){
                        return "pimcore_hidden";
                    }
                }
            }]
        },
        {
            xtype: 'actioncolumn',
            sortable: false,
            width: 120,
            dataIndex: "params",
            hidden: true,
            header: t('email_log_params'),
            items : [{
                tooltip: t('email_log_show_text_params'),
                icon: "/pimcore/static6/img/icon/information.png",
                handler: function(grid, rowIndex){
                    var rec = grid.getStore().getAt(rowIndex);

                    this.tree = new Ext.ux.tree.TreeGrid({
                        width: 700,
                        height: 700,
                        renderTo: Ext.getBody(),
                        enableDD: true,

                        columns:[{
                            header: t('email_log_property'),
                            dataIndex: 'key',
                            width: 230
                        },{
                            header: t('email_log_data'),
                            width: 370,
                            dataIndex: 'data',
                            tpl: new Ext.XTemplate('{data:this.formatData}', {
                                formatData: function (data){
                                    if(data.type == 'simple'){
                                        return data.value;
                                    }else{
                                        //when the objectPath is set -> the object is still available otherwise it was
                                        // deleted in the meantime
                                        if(data.objectPath){
                                            var subtype = data.objectClassSubType.toLowerCase();
                                            return '<span onclick="pimcore.helpers.open'
                                                + data.objectClassBase + '(' + data.objectId + ', \''
                                                + subtype + '\');" class="input_drop_target" style="display: block;">'
                                                                + data.objectPath + '</span>';
                                        }else{
                                            return '"' + data.objectClass + '" with Id: '
                                                + data.objectId + ' (deleted)';
                                        }
                                    }
                                }
                            })
                        }],

                        dataUrl: '/admin/email/show-email-log/?id=' + rec.get('id') + '&type=params'
                    });

                    this.window = new Ext.Window({
                         modal: true,
                         width: 620,
                         height: 700,
                         title: t('email_log_params'),
                         items: [this.tree],
                         layout: "fit"
                     });
                     this.window.show();

                }.bind(this)
            }]
        },
        {
            xtype:'actioncolumn',
            width:30,
            items:[
                {
                    tooltip:t('email_log_resend'),
                    icon:"/pimcore/static6/img/icon/email_start.png",
                    handler:function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                            Ext.Msg.confirm(t('email_log_resend_window_title'), t('email_log_resend_window_msg'),
                                function(btn){
                                if (btn == 'yes'){
                                    Ext.Ajax.request({
                                        url: '/admin/email/resend-email/',
                                        success: function(response){
                                            var data = Ext.decode( response.responseText );
                                            if(data.success){
                                                Ext.Msg.alert(t('email_log_resend_window_title'),
                                                              t('email_log_resend_window_success_message'));
                                            }else{
                                                Ext.Msg.alert(t('email_log_resend_window_title'),
                                                              t('email_log_resend_window_error_message'));
                                            }
                                        },
                                        failure: function () {
                                            alert("Could not resend email");
                                        },
                                        params: { id : rec.get('id') }
                                    });
                                }
                            });
                    }.bind(this),
                    getClass: function(v, meta, rec) {
                        if(!rec.get('emailLogExistsHtml') && !rec.get('emailLogExistsText') ){
                            return "pimcore_hidden";
                        }
                    }
                }
            ]
        },
         {
            xtype: 'actioncolumn',
            width: 30,
            items: [{
                tooltip: t('delete'),
                icon: "/pimcore/static6/img/icon/cross.png",
                handler: function (grid, rowIndex) {
                    var rec = grid.getStore().getAt(rowIndex);
                    Ext.Ajax.request({
                        url: '/admin/email/delete-email-log/',
                        success: function(response){
                            var data = Ext.decode( response.responseText );
                            if(!data.success){
                                alert("Could not delete email log");
                            }
                        },
                        failure: function () {
                            alert("Could not delete email log");
                        }, 
                        params: { id : rec.get('id') }
                    });
                    grid.getStore().removeAt(rowIndex);
                }.bind(this)
            }]
        }

        ];

       var storeFields = ["id","documentId","subject","emailLogExistsHtml","params","sentDate","params",
                          "modificationDate","requestUri","from","to","cc","bcc","emailLogExistsHtml",
                          "emailLogExistsText"];


        this.store = pimcore.helpers.grid.buildDefaultStore(
            '/admin/email/email-logs?',
            storeFields,
            itemsPerPage
        );

        if(this.document) {
            var proxy = this.store.getProxy();
            proxy.extraParams["documentId"] = this.document.id;
        }

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [
                "->",
                {
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },this.filterField
            ]
        });

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            store: this.store,
            columns : gridColumns,
            columnLines: true,
            stripeRows: true,
            border: true,
            trackMouseOver: true,
            loadMask: true,
            viewConfig: {
                forceFit: true
            },
            tbar: toolbar,
            bbar: this.pagingtoolbar
        });

        return this.grid;
    },

    reload: function () {

        this.grid.store.reload();
        this.grid.getView().refresh();

    }
});