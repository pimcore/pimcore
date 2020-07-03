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

pimcore.registerNS("pimcore.document.newsletters.addressSourceAdapters.report");
pimcore.document.newsletters.addressSourceAdapters.report = Class.create({

    initialize: function(document, data) {
        this.document = document;
    },

    /**
     * returns name of corresponding php implementation class
     *
     * @returns {string}
     */
    getName: function() {
        return "reportAdapter";
    },

    /**
     * returns layout for sending panel
     *
     * @returns {Ext.form.Panel|*}
     */
    getLayout: function () {

        if (this.layout == null) {

            this.layout = Ext.create('Ext.form.Panel', {
                border: false,
                autoScroll: true,
                defaults: {labelWidth: 200},
                items: [
                    {
                        xtype: "combo",
                        name: "reportId",
                        fieldLabel: t("newsletter_choose_report"),
                        triggerAction: 'all',
                        editable: false,
                        store: new Ext.data.Store({
                            autoDestroy: true,
                            proxy: {
                                type: 'ajax',
                                url: Routing.generate('pimcore_admin_document_newsletter_getavailablereports', {task: 'list'}),
                                reader: {
                                    type: 'json',
                                    rootProperty: 'data'
                                }
                            },
                            fields: ["id", "text"]
                        }),
                        id: "pimcore_newsletter_send_report_" + this.document.id,
                        width: 600,
                        displayField: 'text',
                        valueField: 'id',
                        listeners: {
                            "change": function (el) {
                                Ext.getCmp("email_field_name_" + this.document.id).clearValue();
                                Ext.getCmp("email_field_name_" + this.document.id).getStore().reload({
                                    params: {
                                        reportId: el.getValue()
                                    }
                                });
                            }.bind(this)
                        }
                    },{
                        xtype:'combo',
                        name: "emailFieldName",
                        fieldLabel: t('newsletter_email_field_name'),
                        triggerAction: "all",
                        editable: false,
                        store: new Ext.data.JsonStore({
                            autoDestroy: true,
                            proxy: {
                                type: 'ajax',
                                url: Routing.generate('pimcore_admin_document_newsletter_getavailablereports', {task: 'fieldNames'}),
                                reader: {
                                    type: 'json',
                                    rootProperty: 'data'
                                }
                            },
                            listeners: {
                                beforeload : function(store, options) {
                                    store.getProxy().extraParams = {
                                        reportId: Ext.getCmp("pimcore_newsletter_send_report_"
                                            + this.document.id).getValue()
                                    };
                                }.bind(this)
                            },
                            fields: ["name"]
                        }),
                        width: 600,
                        displayField: 'name',
                        valueField: 'name',
                        id: "email_field_name_" + this.document.id,
                    }
                ]
            });
        }

        return this.layout;
    },

    /**
     * returns values for sending process
     *
     * @returns {*|Object}
     */
    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        return this.getLayout().getForm().getFieldValues();
    }

});
