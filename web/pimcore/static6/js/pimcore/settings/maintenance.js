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

pimcore.registerNS("pimcore.settings.maintenance");
pimcore.settings.maintenance = Class.create({

    initialize: function () {


        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:200,
            closeAction:'close',
            modal: true,
            items: [{
                xtype: "panel",
                border: false,
                bodyStyle: "padding:20px;font-size:14px;",
                html: "<b style='color:red;'>WARNING</b><br />If you activate the maintenance mode all services "
                        + "(website, admin, api, ...) will be deactivated. This should be only done by administrators!"
                        + "<br />Only this browser (session) will be still able to access the services."
            }],
            buttons: [{
                text: t("activate"),
                iconCls: "pimcore_icon_apply",
                handler: this.activate.bind(this)
            }]
        });

        pimcore.viewport.add(this.window);

        this.window.show();

    },

    activate: function () {
        this.window.close();
        pimcore.helpers.activateMaintenance();
    },

    deactivate: function () {
        pimcore.helpers.deactivateMaintenance();
    }
});
