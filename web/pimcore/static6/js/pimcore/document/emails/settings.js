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

pimcore.registerNS("pimcore.document.emails.settings");
pimcore.document.emails.settings = Class.create(pimcore.document.settings_abstract, {

    getLayout: function () {

        if (this.layout == null) {

            this.layout = Ext.create('Ext.form.Panel', {

                title: t('settings'),
                bodyStyle:'padding:0 10px 0 10px;',
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_settings",
                items: [
                    {
                        xtype:'fieldset',
                        title: t('email_settings'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 700},
                        items :[
                            {
                                fieldLabel: t('email_subject'),
                                name: 'subject',
                                value: this.document.data.subject
                            },
                            {
                                fieldLabel: t('email_from'),
                                name: 'from',
                                value: this.document.data.from
                            },
                            {
                                fieldLabel: t('email_to'),
                                name: 'to',
                                value: this.document.data.to
                            },
                            {
                                fieldLabel: t('email_cc'),
                                name: 'cc',
                                value: this.document.data.cc
                            },
                            {
                                fieldLabel: t('email_bcc'),
                                name: 'bcc',
                                value: this.document.data.bcc
                            },
                            {
                                xtype: "displayfield",
                                value: t("email_settings_receiver_description"),
                                style: "font-size: 10px;"
                            }
                        ]
                    },
                    this.getControllerViewFields(),
                    this.getPathAndKeyFields()
                ]
            });
        }

        return this.layout;
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        var fields = ["controller","action","template"];
        var form = this.getLayout().getForm();
        var element = null;

        // get values
        var settings = this.getLayout().getForm().getFieldValues();

        return settings;
    }

});