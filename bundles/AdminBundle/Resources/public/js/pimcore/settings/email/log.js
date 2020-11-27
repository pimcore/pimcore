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

pimcore.registerNS('pimcore.settings.email.log');
pimcore.settings.email.log = Class.create({

    filterField: null,

    initialize: function(document) {
        this.document = document;

        this.filterField = new Ext.form.TextField({
            width: 200,
            style: 'margin: 0 10px 0 0;',
            enableKeyEvents: true,
            value: this.preconfiguredFilter,
            listeners: {
                'keydown' : function (field, key) {
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
            var tabPanel = Ext.getCmp('pimcore_panel_tabs');
            tabPanel.add(this.getLayout());
            tabPanel.setActiveTab(this.getLayout());

            pimcore.layout.refresh();

            this.getLayout().on('destroy', function () {
                pimcore.globalmanager.remove('sent_emails');
            }.bind(this));
        }
    },

    activate: function () {
        // this is only for standalone mode (without document set)
        var tabPanel = Ext.getCmp('pimcore_panel_tabs');
        tabPanel.setActiveTab(this.getLayout());
    },

    load: function () {
    },

    getLayout: function () {

        if (this.layout == null) {

            this.grid = this.getGrid();

            this.layout = new Ext.Panel({
                title: t('email_logs'),
                border: false,
                layout: 'fit',
                items: [this.grid],
                closable: this.document ? false : true,
                iconCls: this.document ? 'pimcore_material_icon_email_sent pimcore_material_icon' : 'pimcore_icon_email pimcore_icon_overlay_go',
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

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();

        var gridColumns = [
            {
                text: 'ID',
                dataIndex: 'id',
                flex: 40,
                hidden: true
            },{
                text: 'Document Id',
                dataIndex: 'documentId',
                flex: 130,
                hidden: true
            },
            {
                text: t('email_log_sent_Date'),
                dataIndex: 'sentDate',
                width: 150,
                flex: false,
                sortable: false,
                renderer: function (d) {
                    var date = new Date(intval(d) * 1000);
                    return Ext.Date.format(date, 'Y-m-d H:i:s');
                }
            },
            {
                text: t('email_log_from'),
                sortable: false,
                dataIndex: 'from',
                flex: 120,
                renderer: function (s) {
                    return Ext.util.Format.htmlEncode(s);
                }
            },
            {
                text: t('email_reply_to'),
                sortable: false,
                dataIndex: 'replyTo',
                flex: 120,
                hidden: true,
                renderer: function (s) {
                    return Ext.util.Format.htmlEncode(s);
                }
            },
            {
                text: t('email_log_to'),
                sortable: false,
                dataIndex: 'to',
                flex: 120,
                renderer: function (s) {
                    return Ext.util.Format.htmlEncode(s);
                }
            },
            {
                text: t('email_log_cc'),
                sortable: false,
                dataIndex: 'cc',
                flex: 120,
                renderer: function (s) {
                    return Ext.util.Format.htmlEncode(s);
                }
            },
            {
                text: t('email_log_bcc'),
                sortable: false,
                dataIndex: 'bcc',
                flex: 120,
                renderer: function (s) {
                    return Ext.util.Format.htmlEncode(s);
                }
            },
            {
                text: t('email_log_subject'),
                sortable: false,
                dataIndex: 'subject',
                flex: 220,
                renderer: function (s) {
                    return Ext.util.Format.htmlEncode(s);
                }
            },
            {
                xtype: 'actioncolumn',
                sortable: false,
                width: 50,
                dataIndex: 'emailLogExistsHtml',
                menuText: t('html'),
                text: t('html'),
                items : [{
                    icon: '/bundles/pimcoreadmin/img/flat-color-icons/feedback.svg',
                    handler: function(grid, rowIndex){
                        var rec = grid.getStore().getAt(rowIndex);
                        var url = Routing.generate('pimcore_admin_email_showemaillog', {id: rec.get('id'), type: 'html'});
                        var iframe = new Ext.Window({
                            title: t('html'),
                            width: iFrameSettings.width,
                            height: iFrameSettings.height,
                            layout: 'fit',
                            items : [{
                                xtype : 'box',
                                autoEl: {tag: 'iframe', src: url}
                            }]
                        });
                        iframe.show();
                    }.bind(this),
                    getClass: function(v, meta, rec) {
                        if(!rec.get('emailLogExistsHtml')){
                            return 'pimcore_hidden';
                        }
                    }
                }]
            },
            {
                xtype: 'actioncolumn',
                sortable: false,
                width: 50,
                dataIndex: 'emailLogExistsText',
                menuText: t('text'),
                text: t('text'),
                items : [{
                    icon: '/bundles/pimcoreadmin/img/flat-color-icons/text.svg',
                    handler: function(grid, rowIndex){
                        var rec = grid.getStore().getAt(rowIndex);
                        var url = Routing.generate('pimcore_admin_email_showemaillog', {id: rec.get('id'), type: 'text'});
                        var iframe = new Ext.Window({
                            title: t('text'),
                            width: iFrameSettings.width,
                            height: iFrameSettings.height,
                            layout: 'fit',
                            items : [{
                                xtype : 'box',
                                autoEl: {tag: 'iframe', src: url}
                            }]
                        });
                        iframe.show();
                    }.bind(this),
                    getClass: function(v, meta, rec) {
                        if(!rec.get('emailLogExistsText')) {
                            return 'pimcore_hidden';
                        }
                    }
                }]
            },
            {
                xtype: 'actioncolumn',
                sortable: false,
                width: 120,
                dataIndex: 'params',
                hidden: false,
                menuText: t('parameters'),
                text: t('parameters'),
                items : [{
                    icon: '/bundles/pimcoreadmin/img/flat-color-icons/info.svg',
                    handler: function(grid, rowIndex){
                        var rec = grid.getStore().getAt(rowIndex);
                        var url = Routing.generate('pimcore_admin_email_showemaillog', {id: rec.get('id'), type: 'params'});
                        var store = Ext.create('Ext.data.TreeStore', {
                            proxy: {
                                type: 'ajax',
                                url: url,
                                reader: {
                                    type: 'json'
                                },
                                autoDestroy: true
                            }
                        });

                        this.tree =  Ext.create('Ext.tree.Panel', {
                            expanded: true,
                            rootVisible: false,
                            store: store,
                            lines: true,
                            columnLines: true,
                            columns:[
                                new Ext.tree.Column({
                                    text: t('name'),
                                    dataIndex: 'key',
                                    width: 230
                                }),
                                {
                                    text: t('value'),
                                    width: 370,
                                    dataIndex: 'data',
                                    renderer: function(value, metadata, record) {

                                        var data = record.data.data;
                                        if (data.type == 'simple') {
                                            return data.value;
                                        } else {
                                            //when the objectPath is set -> the object is still available otherwise it was
                                            // deleted in the meantime
                                            if (data.objectPath) {
                                                var type = data.type;
                                                var subtype = data.objectClassSubType.toLowerCase();
                                                metadata.tdAttr = 'data-qtip="' + t("open") + '"';
                                                return '<span onclick="pimcore.helpers.openElement(' + data.objectId + ', \'' + type + '\' , \''
                                                    + subtype + '\'); Ext.getCmp(\'email_log_params_panel\').close();" class="x-grid-cell-inner input_drop_target" style="display: block;">'
                                                    + data.objectPath + '</span>';
                                            } else {
                                                return '"' + data.objectClass + '" with Id: '
                                                    + data.objectId + ' (deleted)';
                                            }
                                        }
                                    }
                                }]
                        });

                        this.window = new Ext.Window({
                            id: "email_log_params_panel",
                            modal: true,
                            width: 620,
                            height: "90%",
                            title: t('parameters'),
                            items: [this.tree],
                            layout: 'fit'
                        });
                        this.window.show();

                    }.bind(this)
                }]
            },
            {
                xtype:'actioncolumn',
                width: 30,
                menuText: t('email_log_resend'),
                items:[
                    {
                        tooltip: t('email_log_resend'),
                        icon: '/bundles/pimcoreadmin/img/flat-color-icons/email.svg',
                        handler: function (grid, rowIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.Msg.confirm(t('email_log_resend'), t('email_log_resend_window_msg'),
                                function(btn){
                                    if (btn == 'yes'){
                                        Ext.Ajax.request({
                                            url: Routing.generate('pimcore_admin_email_resendemail'),
                                            method: 'POST',
                                            success: function(response){
                                                var data = Ext.decode( response.responseText );
                                                if(data.success){
                                                    Ext.Msg.alert(t('email_log_resend'),
                                                        t('email_log_resend_window_success_message'));
                                                }else{
                                                    Ext.Msg.alert(t('email_log_resend'),
                                                        t('email_log_resend_window_error_message'));
                                                }
                                            },
                                            failure: function () {
                                                Ext.Msg.alert(t('email_log_resend'),
                                                    t('email_log_resend_window_error_message'));
                                            },
                                            params: { id : rec.get('id') }
                                        });
                                    }
                                });
                        }.bind(this),
                        getClass: function(v, meta, rec) {
                            if(!rec.get('emailLogExistsHtml') && !rec.get('emailLogExistsText') ){
                                return 'pimcore_hidden';
                            }
                        }
                    }
                ]
            },
            {
                xtype:'actioncolumn',
                width: 30,
                menuText: t('email_log_forward'),
                items:[
                    {
                        tooltip: t('email_log_forward'),
                        icon: '/bundles/pimcoreadmin/img/flat-color-icons/email-forward.svg',
                        handler: function (grid, rowIndex) {
                            var rec = grid.getStore().getAt(rowIndex);

                            Ext.Ajax.request({
                                url: Routing.generate('pimcore_admin_email_showemaillog', {id: rec.get('id'), type: 'details'}),
                                success: function(response){
                                    var data = Ext.decode( response.responseText );
                                    var win = this.getForwardEmailWindow(data);
                                    win.show();
                                }.bind(this),
                                failure: function () {
                                    Ext.Msg.alert(t('email_log_forward'),
                                        t('email_log_resend_window_error_message'));
                                },
                            });
                        }.bind(this),
                        getClass: function(v, meta, rec) {
                            if(!rec.get('emailLogExistsHtml') && !rec.get('emailLogExistsText') ){
                                return 'pimcore_hidden';
                            }
                        }
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                menuText: t('delete'),
                items: [{
                    tooltip: t('delete'),
                    icon: '/bundles/pimcoreadmin/img/flat-color-icons/delete.svg',
                    handler: function (grid, rowIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_email_deleteemaillog'),
                            method: 'DELETE',
                            success: function(response){
                                var data = Ext.decode( response.responseText );
                                if(!data.success){
                                    Ext.Msg.alert(t('error'),
                                        t('error_deleting_item'));
                                }
                            },
                            failure: function () {
                                Ext.Msg.alert(t('error'),
                                    t('error_deleting_item'));
                            },
                            params: { id : rec.get('id') }
                        });
                        grid.getStore().reload();
                    }.bind(this)
                }]
            }
        ];

        var storeFields = ["id","documentId","subject","emailLogExistsHtml","params","sentDate","params",
            "modificationDate","requestUri","from","to","cc","bcc","emailLogExistsHtml",
            "emailLogExistsText"];

        this.store = pimcore.helpers.grid.buildDefaultStore(
            Routing.generate('pimcore_admin_email_emaillogs'),
            storeFields,
            itemsPerPage
        );

        if(this.document) {
            var proxy = this.store.getProxy();
            proxy.extraParams['documentId'] = this.document.id;
        }

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                '->',
                {
                    text: t('filter') + '/' + t('search'),
                    xtype: 'tbtext',
                    style: 'margin: 0 10px 0 0;'
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

    },

    getForwardEmailWindow: function (data) {
        if (data) {
            var emailType = data.emailLogExistsHtml ? 'html' : 'text';
            var emailPreviewUrl = Routing.generate('pimcore_admin_email_showemaillog', {id: data.id, type: emailType});

            var win =  new Ext.Window({
                width: 800,
                height: 600,
                modal: true,
                title: t("email_log_forward"),
                layout: "fit",
                closeAction: "close",
                items: [{
                    xtype: "form",
                    bodyStyle: "padding:10px;",
                    itemId: "form",
                    items: [
                        {
                            xtype: 'hiddenfield',
                            name: 'id',
                            value: data.id
                        },
                        {
                            xtype: 'textfield',
                            value: data.subject,
                            readOnly: true,
                            fieldLabel: t("subject"),
                        },
                        {
                            xtype: 'textfield',
                            value: data.from,
                            readOnly: true,
                            fieldLabel: t("from"),
                        },
                        {
                            xtype: 'textfield',
                            value: data.replyTo,
                            hidden: empty(data.replyTo),
                            readOnly: true,
                            fieldLabel: t("replyTo"),
                        },
                        {
                            xtype: 'textfield',
                            name: "to",
                            allowBlank: false,
                            fieldLabel: t("to"),
                        },
                        {
                            xtype: 'panel',
                            height: 350,
                            layout: 'fit',
                            items : [{
                                xtype : 'box',
                                autoEl: {tag: 'iframe', src: emailPreviewUrl}
                            }]
                        }

                    ],
                    defaults: {
                        width: 780
                    }
                }],
                buttons: [{
                    text: t("send"),
                    iconCls: "pimcore_icon_email",
                    handler: function () {
                        var form = win.getComponent("form").getForm();
                        var params = form.getFieldValues();
                        if (form.isValid()) {
                            Ext.Ajax.request({
                                url: Routing.generate('pimcore_admin_email_resendemail'),
                                method: 'POST',
                                success: function (response) {
                                    var data = Ext.decode(response.responseText);
                                    if (data.success) {
                                        Ext.Msg.alert(t('email_log_forward'),
                                            t('email_log_resend_window_success_message'));
                                        win.close();
                                    } else {
                                        Ext.Msg.alert(t('email_log_forward'),
                                            t('email_log_resend_window_error_message'));
                                    }
                                },
                                failure: function () {
                                    Ext.Msg.alert(t('email_log_forward'),
                                        t('email_log_resend_window_error_message'));
                                },
                                params: params
                            });
                        }
                    }
                }]
            });

            return win;
        }
    }
});
