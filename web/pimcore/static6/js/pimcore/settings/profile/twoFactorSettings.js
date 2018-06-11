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


pimcore.registerNS("pimcore.settings.profile.twoFactorSettings");
pimcore.settings.profile.twoFactorSettings = Class.create({


    initialize: function (data) {
        this.data = data;
    },


    getPanel: function () {

        var that = this;

        var twoFactorData = this.data;

        var qrCode = this.getQrCodeUrl(twoFactorData.secret);

        var twoFactorEnabledCheckbox = Ext.create({
            xtype: "checkbox",
            fieldLabel: t("2fa_enabled"),
            name: "2fa_enabled",
            checked: twoFactorData.enabled
        });

        var twoFactorSecretField = Ext.create({
            xtype: "textfield",
            fieldLabel: t("2fa_secret"),
            name: "2fa_secret",
            width: 400,
            disabled: true,
            value: twoFactorData.secret
        });

        var twoFactorQrCodePanel = Ext.create({
            xtype: "container",
            html: '<img src="' + qrCode +'"/>'
        });

        if(twoFactorData.secret) {
            twoFactorQrCodePanel.setHeight(230);
            twoFactorQrCodePanel.setWidth(230);
        }

        return Ext.create({
            xtype: "fieldset",
            title: t("2fa_settings"),
            items:
                [
                    twoFactorEnabledCheckbox,
                    twoFactorSecretField,
                    twoFactorQrCodePanel,
                    {
                        xtype: "button",
                        text: t("2fa_renew_button"),
                        handler: function () {

                            Ext.Ajax.request({
                                url: "/admin/user/renew-2fa-qr-secret",
                                method: "post",
                                params: {
                                    'enabled': twoFactorEnabledCheckbox.checked
                                },
                                success: function (response) {

                                    var res = Ext.decode(response.responseText);
                                    if(res.enabled) {
                                        qrCode = that.getQrCodeUrl(res.secret);
                                        twoFactorQrCodePanel.setHtml('<img src="' + qrCode +'"/>');
                                        twoFactorQrCodePanel.setHeight(230);
                                        twoFactorQrCodePanel.setWidth(230);

                                        twoFactorSecretField.setValue(res.secret);


                                        var popup = that.getCodePopup(res.secret);
                                        popup.show();

                                    } else {
                                        twoFactorQrCodePanel.setHtml('');
                                        twoFactorQrCodePanel.setHeight(0);
                                        twoFactorQrCodePanel.setWidth(0);
                                        twoFactorSecretField.setValue('');
                                    }
                                }
                            });
                        }
                    }
                ]
        });
    },

    getCodePopup: function(secret) {

        var qrCode = this.getQrCodeUrl(secret);

        return Ext.create('Ext.window.Window', {
            title: t('2fa_alert_title'),
            resizable: false,
            closable: false,
            draggable: false,
            width: 450,
            height: 450,
            layout: {
                type: 'vbox',
                align: 'center'
            },
            modal: true,
            items: [
                {
                    xtype: "container",
                    html: '<img src="' + qrCode +'"/>',
                    width: 230,
                    height: 230
                },
                {
                    xtype: "container",
                    html: t('2fa_alert_text'),
                    width: 420,
                    height: 420
                }
            ],
            buttons: [{
                text: t('2fa_alert_submit'),
                handler: function() {
                    window.location.reload();
                }
            }]
        })
    }
    ,
    getQrCodeUrl(secret) {

        if(secret) {
            var date = new Date();
            return "/admin/user/get-2fa-qr-code?secret=" + secret + "&_dc=" + date.getTime();
        }

        return '';
    }


});