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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */


pimcore.registerNS("pimcore.settings.user.role.tab");
pimcore.settings.user.role.tab = Class.create({

    initialize: function (parentPanel, id) {
        this.parentPanel = parentPanel;
        this.id = id;

        Ext.Ajax.request({
            url: "/admin/user/role-get",
            success: this.loadComplete.bind(this),
            params: {
                id: this.id
            }
        });
    },

    loadComplete: function (transport) {
        var response = Ext.decode(transport.responseText);
        if(response && response.success) {
            this.data = response;
            this.initPanel();
        }
    },

    initPanel: function () {

        this.panel = new Ext.TabPanel({
            title: this.data.role.name,
            closable: true,
            activeTab: 0,
            iconCls: "pimcore_icon_roles",
            buttons: [{
                text: t("save"),
                handler: this.save.bind(this),
                iconCls: "pimcore_icon_accept"
            }]
        });

        this.panel.on("beforedestroy", function () {
            delete this.parentPanel.panels["role_" + this.id];
        }.bind(this));

        this.settings = new pimcore.settings.user.role.settings(this);
        this.workspaces = new pimcore.settings.user.workspaces(this);

        this.panel.add(this.settings.getPanel());
        this.panel.add(this.workspaces.getPanel());

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);
    },

    activate: function () {
        this.parentPanel.getEditPanel().activate(this.panel);
    },

    save: function () {

        var data = {
            id: this.id
        };

        try {
            data.data = Ext.encode(this.settings.getValues());
        } catch (e) {
            console.log(e);
        }

        try {
            data.workspaces = Ext.encode(this.workspaces.getValues());
        } catch (e2) {
            console.log(e2);
        }

        Ext.Ajax.request({
            url: "/admin/user/update",
            method: "post",
            params: data,
            success: function (transport) {
                try{
                    var res = Ext.decode(transport.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("role_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("role_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("role_save_error"), "error");
                }
            }.bind(this)
        });
    }

});
