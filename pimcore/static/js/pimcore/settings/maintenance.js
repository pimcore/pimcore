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

        Ext.Ajax.request({
            url: "/admin/misc/maintenance/activate/true"
        });

        this.window.close();

        this.toolbar = pimcore.globalmanager.get("layout_toolbar").toolbar;

        this.deactivateButton = new Ext.Button({
            text: "DEACTIVATE MAINTENANCE",
            iconCls: "pimcore_icon_maintenance",
            cls: "pimcore_main_menu",
            handler: this.deactivate.bind(this)
        });
        this.toolbar.insertButton(5, [this.deactivateButton]);
        this.toolbar.doLayout();
    },

    deactivate: function () {
        Ext.Ajax.request({
            url: "/admin/misc/maintenance/deactivate/true"
        });

        this.toolbar.remove(this.deactivateButton);
        this.toolbar.doLayout();
    }
});
