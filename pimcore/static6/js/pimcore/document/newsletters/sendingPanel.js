/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.newsletters.sendingPanel");
pimcore.document.newsletters.sendingPanel = Class.create({

    isProcessRunning: false,

    initialize: function(document) {
        this.document = document;
    },

    updateDirtyState: function() {
        if(this.document.isDirty()) {
            this.dirtyWarning.show();
            this.sendTestMessage.disable();
            this.sendNewsletter.disable();
        } else {
            this.dirtyWarning.hide();
            if(!this.isProcessRunning) {
                this.sendTestMessage.enable();
                this.sendNewsletter.enable();
            }
        }
    },

    getLayout: function () {

        if (this.layout == null) {

            this.dirtyWarning = Ext.create('Ext.form.Panel', {
                html: "<p style='color: #a94442; background: #fcf2f2; padding: 10px'><strong>" + t("newsletter_dirty_warning") + "</strong></p>"
            });

            this.sendTestMessage = Ext.create('Ext.button.Button', {
                text: t("send_test_newsletter"),
                iconCls: "pimcore_icon_email",
                handler: this.sendTest.bind(this)
            });


            this.testSendPanel = Ext.create('Ext.form.FieldSet', {
                title: t('newsletter_testsend'),
                collapsible: true,
                collapsed: false,
                autoHeight:true,
                labelWidth: 300,
                hidden: true,
                style: "margin-top: 50px",
                defaults: {width: 560},
                defaultType: 'textfield',
                items :[
                    {
                        fieldLabel: t('email_to'),
                        name: 'to'
                    },
                    this.sendTestMessage
                ]
            });

            this.sendNewsletter = Ext.create('Ext.button.Button', {
                text: t("send_newsletter"),
                iconCls: "pimcore_icon_email",
                hidden: true,
                handler: this.send.bind(this)
            });

            var sourceAdapterList = [];
            var adapterNames = Object.keys(pimcore.document.newsletters.addressSourceAdapters);
            for(var i = 0; i < adapterNames.length; i++) {
                sourceAdapterList.push(
                    {
                        'key': adapterNames[i],
                        'value': t("newsletter_" + adapterNames[i])
                    }
                );
            }

            this.sourceSelectionPanel = Ext.create('Ext.form.Panel', {});

            this.sourceAdapterSelection = Ext.create('Ext.form.ComboBox', {
                fieldLabel: t('newsletter_sourceAdapter'),
                store: Ext.create('Ext.data.Store', {
                    fields: ['key', 'value'],
                    data : sourceAdapterList
                }),
                queryMode: 'local',
                displayField: 'value',
                valueField: 'key',
                listeners: {
                    select: function(combo, record, eOpts) {
                        this.sourceSelectionPanel.removeAll();
                        this.currentSourceAdapter = new pimcore.document.newsletters.addressSourceAdapters[record.data.key](this.document);
                        this.sourceSelectionPanel.add(this.currentSourceAdapter.getLayout());
                        this.sendNewsletter.show();
                        this.testSendPanel.show();
                    }.bind(this)
                }
            });


            this.progressBar = Ext.create('Ext.ProgressBar', {
                style: "margin-bottom: 10px; margin-top: 20px"
            });

            this.statusUpdateBox = Ext.create('Ext.Panel', {
                autoHeight: true,
                border: false,
                hidden: true,
                items: [this.progressBar, {
                    xtype: 'button',
                    style: "float: right;",
                    text: t("stop"),
                    iconCls: "pimcore_icon_stop",
                    handler: function() {
                        Ext.Ajax.request({
                            url: "/admin/newsletter/stop-send",
                            params: {id: this.document.id}
                        });
                    }.bind(this)
                }]
            });


            this.layout = Ext.create('Ext.form.Panel', {

                title: t('newsletter_sendingPanel'),
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_newsletter",
                items: [
                    this.dirtyWarning,
                    {
                        xtype: 'panel',
                        title: t('newsletter_sending'),
                        bodyStyle:'padding: 10px;',
                        collapsible: false,
                        autoHeight: true,
                        defaultType: 'textfield',
                        defaults: {labelWidth: 200, width: 600},
                        items :[
                            this.sourceAdapterSelection,
                            this.sourceSelectionPanel,
                            this.sendNewsletter,
                            this.statusUpdateBox,
                            this.testSendPanel
                        ]
                    }
                ]
            });
            this.layout.on("activate", this.refresh.bind(this));
        }

        return this.layout;
    },

    getValues: function () {
        //currently nothing to do
    },

    send: function() {

        Ext.MessageBox.confirm(t("are_you_sure"), t("do_you_really_want_to_send_the_newsletter_to_all_recipients"), function (buttonValue) {

            if (buttonValue == "yes") {
                var fieldValues = this.layout.getForm().getFieldValues();

                var params = {
                    id: this.document.id,
                    adapterParams: Ext.encode(this.currentSourceAdapter.getValues()),
                    addressAdapterName: this.currentSourceAdapter.getName()
                };

                Ext.Ajax.request({
                    url: "/admin/newsletter/send",
                    method: "post",
                    params: params,
                    success: function (response) {
                        this.checkForActiveSendingProcess();

                        var res = Ext.decode(response.responseText);

                        if (res.success) {
                            Ext.MessageBox.alert(t("info"), t("newsletter_sent_message"))
                        } else {
                            Ext.MessageBox.alert(t("error"), t("newsletter_send_error"))
                        }

                        //again check in 2 seconds since it may take a while until process starts
                        window.setTimeout(function() {
                            this.checkForActiveSendingProcess();
                        }.bind(this), 2000);

                    }.bind(this)
                });
            }

        }.bind(this));

    },

    sendTest: function() {

        var fieldValues = this.layout.getForm().getFieldValues();

        var params = {
            id: this.document.id,
            adapterParams: Ext.encode(this.currentSourceAdapter.getValues()),
            addressAdapterName: this.currentSourceAdapter.getName(),
            testMailAddress: fieldValues.to
        };

        Ext.Ajax.request({
            url: "/admin/newsletter/send-test",
            method: "post",
            params: params,
            success: function(response) {
                var res = Ext.decode(response.responseText);

                if(res.success) {
                    Ext.MessageBox.alert(t("info"), t("newsletter_test_sent_message"))
                } else {
                    Ext.MessageBox.alert(t("error"), t("newsletter_send_error"))
                }
            }
        });

    },

    checkForActiveSendingProcess: function() {
        Ext.Ajax.request({
            url: "/admin/newsletter/get-send-status",
            params: {id: this.document.id},
            success: function(response) {
                var result = Ext.decode(response.responseText);

                if(result.data && result.data.inProgress) {

                    this.isProcessRunning = true;
                    this.statusUpdateBox.show();
                    this.sendTestMessage.disable();
                    this.sendNewsletter.disable();

                    if(result.data.progress) {
                        var text = result.data.progress + "%";
                        this.progressBar.updateProgress(result.data.progress / 100, text);
                    } else {
                        this.progressBar.updateProgress(0, "0%");
                    }

                    window.setTimeout(function() {
                        this.checkForActiveSendingProcess();
                    }.bind(this), 2000);
                } else {

                    this.isProcessRunning = false;
                    this.sendTestMessage.enable();
                    this.sendNewsletter.enable();
                    this.statusUpdateBox.hide();
                }
            }.bind(this)
        });
    },

    refresh: function () {
        this.checkForActiveSendingProcess();
    }

});