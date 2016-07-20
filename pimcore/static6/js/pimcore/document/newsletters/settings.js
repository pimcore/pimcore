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

pimcore.registerNS("pimcore.document.newsletters.settings");
pimcore.document.newsletters.settings = Class.create(pimcore.document.settings_abstract, {

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
                        defaultType: 'textfield',
                        defaults: {width: 700, labelWidth: 320},
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
                                xtype: 'checkbox',
                                fieldLabel: t('newsletter_enableTrackingParameters'),
                                name: 'enableTrackingParameters',
                                checked: this.document.data.enableTrackingParameters
                            },
                            {
                                fieldLabel: t('newsletter_trackingParameterSource'),
                                name: 'trackingParameterSource',
                                value: this.document.data.trackingParameterSource
                            },
                            {
                                fieldLabel: t('newsletter_trackingParameterMedium'),
                                name: 'trackingParameterMedium',
                                value: this.document.data.trackingParameterMedium
                            },
                            {
                                fieldLabel: t('newsletter_trackingParameterName'),
                                name: 'trackingParameterName',
                                value: this.document.data.trackingParameterName
                            },
                            {
                                xtype: 'combo',
                                store: Ext.create('Ext.data.Store', {
                                    fields: ['key', 'value'],
                                    data : [
                                        {'key': 'single', 'value': t("newsletter_sendingmode_single")},
                                        {'key': 'batch', 'value': t("newsletter_sendingmode_batch")}
                                    ]
                                }),
                                queryMode: 'local',
                                displayField: 'value',
                                valueField: 'key',
                                fieldLabel: t('newsletter_sendingMode'),
                                name: 'sendingMode',
                                value: this.document.data.sendingMode
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