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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
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
                html: "<b style='color:red;'>WARNING</b><br />If you activate the maintenance mode all services (website, admin, api, ...) will be deactivated. This should be only done by administrators!<br />Only this browser (session) will be still able to access the services."
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
