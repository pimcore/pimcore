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
pimcore.registerNS("pimcore.notification.helper.x");

pimcore.notification.helper.updateCount = function (count) {

    var currentValue = Ext.get("notification_value").getHtml();
    if(currentValue > count) {
        return;
    }

    if (count > 0) {
        Ext.get("notification_value").show();
        Ext.fly('notification_value').update(count);
    } else {
        Ext.get("notification_value").hide();
    }
};

pimcore.notification.helper.incrementCount = function () {
    var value = Ext.get("notification_value").getHtml();
    if(value) {
        value++;
    } else {
        value = 1;
    }

    pimcore.notification.helper.updateCount(value);
};

pimcore.notification.helper.showNotifications = function (notifications) {
    for (var i = 0; i < notifications.length; i++) {
        var row = notifications[i];
        var tools = [];
        tools.push({
            type: 'save',
            tooltip: t('mark_as_read'),
            handler: (function (row) {
                return function () {
                    this.up('window').close();
                    pimcore.notification.helper.markAsRead(row.id);
                }
            }(row))
        });
        if (row.linkedElementId) {
            tools.push({
                type: 'right',
                tooltip: t('open_linked_element'),
                handler: (function (row) {
                    return function () {
                        this.up('window').close();
                        pimcore.notification.helper.openLinkedElement(row);
                    }
                }(row))
            });
        }
        tools.push({
            type: 'maximize',
            tooltip: t('open'),
            handler: (function (row) {
                return function () {
                    this.up('window').close();
                    pimcore.notification.helper.openDetails(row.id);
                }
            }(row))
        });
        var notification = Ext.create('Ext.window.Toast', {
            iconCls: 'pimcore_icon_' + row.type,
            title: row.title,
            html: row.message,
            autoShow: true,
            width: 400,
            height: 150,
            closable: true,
            autoClose: false,
            tools: tools
        });
        notification.show();
    }
};

pimcore.notification.helper.markAsRead = function (id, callback) {
    Ext.Ajax.request({
        url: Routing.generate('pimcore_admin_notification_markasread', {id: id}),
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

pimcore.notification.helper.openLinkedElement = function (row) {
    if ('document' == row['linkedElementType']) {
        pimcore.helpers.openElement(row['linkedElementId'], 'document');
    } else if ('asset' == row['linkedElementType']) {
        pimcore.helpers.openElement(row['linkedElementId'], 'asset');
    } else if ('object' == row['linkedElementType']) {
        pimcore.helpers.openElement(row['linkedElementId'], 'object');
    }
};

pimcore.notification.helper.openDetails = function (id, callback) {
    Ext.Ajax.request({
        url: Routing.generate('pimcore_admin_notification_find', {id: id}),
        success: function (response) {
            response = Ext.decode(response.responseText);
            if (!response.success) {
                Ext.MessageBox.alert(t("error"), t("element_not_found"));
                return;
            }
            pimcore.notification.helper.openDetailsWindow(
                response.data.id,
                response.data.title,
                response.data.message,
                response.data.type,
                callback
            );
        }
    });
};

pimcore.notification.helper.openDetailsWindow = function (id, title, message, type, callback) {
    var notification = new Ext.Window({
        modal: true,
        iconCls: 'pimcore_icon_' + type,
        title: title,
        html: message,
        autoShow: true,
        width: 700,
        height: 350,
        scrollable: true,
        closable: true,
        maximizable: true,
        bodyPadding: "10px",
        autoClose: false,
        listeners: {
            afterrender: function () {
                pimcore.notification.helper.markAsRead(id, callback);
            }
        }
    });
    notification.show(document);
    notification.focus();
};

pimcore.notification.helper.delete = function (id, callback) {
    Ext.Ajax.request({
        url: Routing.generate('pimcore_admin_notification_delete', {id: id}),
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

pimcore.notification.helper.deleteAll = function (callback) {
    Ext.Ajax.request({
        url: Routing.generate('pimcore_admin_notification_deleteall'),
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

pimcore.notification.helper.setLastUpdateTimestamp = function () {
    this.lastUpdateTimestamp = parseInt(new Date().getTime() / 1000, 10);
};
pimcore.notification.helper.setLastUpdateTimestamp();

pimcore.notification.helper.updateFromServer = function () {
    var user = pimcore.globalmanager.get("user");
    if (!document.hidden && user.isAllowed("notifications")) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_notification_findlastunread'),
            params: {
                lastUpdate: this.lastUpdateTimestamp
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);
                pimcore.notification.helper.updateCount(data.unread);
                pimcore.notification.helper.showNotifications(data.data);
            }
        });

        pimcore.notification.helper.setLastUpdateTimestamp();
    }
};
