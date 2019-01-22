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
pimcore.registerNS("pimcore.notification.plugin");

pimcore.notification.plugin = Class.create({

    getClassName: function () {
        return "pimcore.notification.plugin";
    },

    initialize: function () {
        var self = this;

        pimcore.plugin.broker.registerPlugin(this);

        var element = '<a href="#" style="display: none" id="pimcore_notification" data-menu-tooltip="' + t("Notifications") + '" class="pimcore_icon_comments">'
            + '<span id="notification_value" style="display:none;"></span>'
            + '</a>';

        this.navEl = Ext.get("pimcore_status_dev").insertSibling(element, "before");
        this.menu = new Ext.menu.Menu({
            items: [
                {
                    text: t("Notifications"),
                    iconCls: "pimcore_icon_comments",
                    handler: this.showNotificationTab.bind(this)
                },
                {
                    text: t("Send notification"),
                    iconCls: "pimcore_icon_sms",
                    id: "notifications_new",
                    handler: this.showNotificationModal.bind(this),
                    listeners: {
                        afterRender: function() {
                            if (!self.isAllowedCreateNew()) {
                                this.hide();
                            }
                        }
                    }
                }
            ],
            cls: "pimcore_navigation_flyout"
        });

        pimcore.layout.toolbar.prototype.notificationMenu = this.menu;
    },

    showNotificationTab: function () {
        try {
            pimcore.globalmanager.get("notifications").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("notifications", new pimcore.notification.panel());
        }
    },

    showNotificationModal: function () {
        if (pimcore.globalmanager.get("new_notifications")) {
            pimcore.globalmanager.get("new_notifications").getWindow().destroy();
        }

        pimcore.globalmanager.add("new_notifications", new pimcore.notification.modal());
    },

    pimcoreReady: function (params, broker) {
        if (this.isAllowed()) {
            Ext.get('pimcore_notification').show();
            var toolbar = pimcore.globalmanager.get("layout_toolbar");
            this.navEl.on("mousedown", toolbar.showSubMenu.bind(toolbar.notificationMenu));
            pimcore.plugin.broker.fireEvent("notificationMenuReady", toolbar.notificationMenu);
            this.startAjaxConnection();
        }
    },

    startAjaxConnection: function () {
        function runAjaxConnection () {
            Ext.Ajax.request({
                url: "/admin/notification/find-last-unread?interval=" + 30,
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    pimcore.notification.helper.updateCount(data.unread);
                    pimcore.notification.helper.showNotifications(data.data);
                }
            });
        }

        pimcore["intervals"]["checkNewNotification"] = window.setInterval(function (elt) {
            runAjaxConnection();
        }, 30000);
        runAjaxConnection(); // run at the Pimcore login
    },

    isAllowed: function () {
        var user = pimcore.globalmanager.get("user");
        return user.isAllowed('notifications');
    },

    isAllowedCreateNew: function () {
        var user = pimcore.globalmanager.get("user");
        return user.isAllowed('notifications_send') && user.isAllowed('notifications');
    }
});

var notificationsPlugin = new pimcore.notification.plugin();
