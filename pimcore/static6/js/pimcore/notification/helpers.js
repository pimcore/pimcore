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
 *
 * @author Piotr Ćwięcek <pcwiecek@divante.pl>
 * @author Kamil Karkus <kkarkus@divante.pl>
 */
pimcore.registerNS("pimcore.notification.helpers.x");
pimcore.notification.helpers.updateUnreadCount = function () {
    Ext.Ajax.request({
        url: "/admin/notification/unread-count",
        success: function (response) {
            var data = Ext.decode(response.responseText);

            if (data.success && data.total > 0) {
                Ext.get("notification_value").show();
                Ext.fly('notification_value').update(data.total);
            } else {
                Ext.get("notification_value").hide();
            }
        }
    });
};
pimcore.notification.helpers.unreadPopup = function (interval) {
    Ext.Ajax.request({
        url: "/admin/notification/unread?interval=" + interval,
        success: function (response) {
            var data = Ext.decode(response.responseText);
            if (!(data.success && data.total > 0)) {
                return;
            }
            for (var i = 0; i < data.total; i++) {
                var row = data.data[i];
                var notification = Ext.create('Ext.window.Toast', {
                    iconCls: 'pimcore_icon_' + row.type,
                    title: row.title,
                    html: row.message,
                    autoShow: true,
                    width: 'auto',
                    maxWidth: 350,
                    closable: true,
                    autoClose: false,
                    tools: [
                        {
                            id: 'save',
                            tooltip: t('mark_as_read'),
                            handler: function () {
                                notification.close();
                                pimcore.notification.helpers.markAsRead(row.id);
                            }
                        },
                        {
                            id: 'right',
                            tooltip: t('open'),
                            handler: function () {
                                notification.close();
                                pimcore.notification.helpers.openDetails(row.id);
                            }
                        }
                    ]
                });
                notification.show(document);
            }
        }
    });
};

pimcore.notification.helpers.delete = function (id, callback) {
    Ext.Ajax.request({
        url: "/admin/notification/delete?id=" + id,
        success: function (response) {
            if (callback) {
            callback();
            }
        }
    });
};

pimcore.notification.helpers.markAsRead = function (id, callback) {
    Ext.Ajax.request({
        url: "/admin/notification/mark-as-read?id=" + id,
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

pimcore.notification.helpers.deleteAll = function (callback) {
    Ext.Ajax.request({
        url: "/admin/notification/delete-all",
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};


pimcore.notification.helpers.openDetails = function (id, callback) {
    Ext.Ajax.request({
        url: "/admin/notification/details?id=" + id,
        success: function (response) {
            response = Ext.decode(response.responseText);
            if (!response.success) {
                return;
            }
            pimcore.notification.helpers.openDetailsWindow(
                response.data.id,
                response.data.title,
                response.data.message,
                response.data.type,
                callback
            );
        }
    });
};

pimcore.notification.helpers.openDetailsWindow = function (id, title, message, type, callback) {
    var notification = new Ext.Window({
        modal: true,
        iconCls: 'pimcore_icon_' + type,
        title: title,
        html: message,
        autoShow: true,
        width: 'auto',
        maxWidth: 700,
        closable: true,
        bodyStyle: "padding: 10px; background:#fff;",
        autoClose: false,
        listeners: {
            focusleave: function () {
                this.close();
            },
            afterrender: function () {
                pimcore.notification.helpers.markAsRead(id, callback);
            }
        }
    });
    notification.show(document);
    notification.focus();
};
