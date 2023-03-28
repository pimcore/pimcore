/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


pimcore.registerNS("pimcore.settings.profile.twoFactorSettings");
/**
 * @private
 */
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
                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_user_reset_my_2fa_secret'),
                        method: 'PUT',
                        success: function (response) {
                            document.getElementById('pimcore_logout_form').submit();
                        }.bind(this)
                    });
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
    }
});
