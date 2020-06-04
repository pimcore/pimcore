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

        var buttonLabel = t('setup_two_factor');
        if(this.data['isActive']) {
            buttonLabel = t('renew_2fa_secret');
        }

        var panelConf = {
            xtype: "fieldset",
            title: t("two_factor_authentication"),
            items: [{
                xtype: "button",
                text: buttonLabel,
                style: "margin-right: 10px",
                handler: function () {
                    this.openSetupWindow();
                }.bind(this)
            }, {
                xtype: "button",
                text: t("2fa_disable"),
                hidden: this.data['required'] || !this.data['isActive'],
                handler: function () {
                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_user_disable2fasecret'),
                        method: 'DELETE',
                        success: function (response) {
                            window.location.reload();
                        }.bind(this)
                    });
                }
            }]
        };

        return panelConf;
    },

    openSetupWindow: function () {
        var win = Ext.create('Ext.window.Window', {
            title: t('two_factor_authentication'),
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
                    html: '<img src="'+Routing.generate('pimcore_admin_user_renew2fasecret')+'"/>',
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
        });

        win.show();
    }
});
