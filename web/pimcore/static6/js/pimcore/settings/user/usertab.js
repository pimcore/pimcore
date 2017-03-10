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


pimcore.registerNS("pimcore.settings.user.usertab");
pimcore.settings.user.usertab = Class.create({

    initialize: function (parentPanel, id) {
        this.parentPanel = parentPanel;
        this.id = id;

        Ext.Ajax.request({
            url: "/admin/user/get",
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
            title: this.data.user.name,
            closable: true,
            iconCls: "pimcore_icon_user",
            buttons: [{
                text: t("save"),
                handler: this.save.bind(this),
                iconCls: "pimcore_icon_accept"
            }]
        });

        this.panel.on("beforedestroy", function () {
            delete this.parentPanel.panels["user_" + this.id];
        }.bind(this));

        this.settings = new pimcore.settings.user.user.settings(this);
        this.workspaces = new pimcore.settings.user.workspaces(this);
        this.objectrelations = new pimcore.settings.user.user.objectrelations(this);


        this.panel.add(this.settings.getPanel());
        this.panel.add(this.workspaces.getPanel());
        this.panel.add(this.objectrelations.getPanel());

        if(this.data.user.admin) {
            this.workspaces.disable();
        }

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);
        this.panel.setActiveTab(0);

    },

    activate: function () {
        this.parentPanel.getEditPanel().setActiveTab(this.panel);
    },

    save: function () {

        var active = null;
        var data = {
            id: this.id
        };
        var contentLanguages;

        try {
            var values = this.settings.getValues();
            if(values.hasOwnProperty("active")) {
                // only if "active" is available (if not available, the checkbox is disabled, eg. when modifying the logged in user)
                active = values["active"];
            }
            contentLanguages = values.contentLanguages;
            data.data = Ext.encode(values);
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
                        pimcore.helpers.showNotification(t("success"), t("user_save_success"), "success");
                        if (this.id == pimcore.currentuser.id && contentLanguages) {
                                pimcore.settings.websiteLanguages = contentLanguages;
                        }

                        var tree = this.parentPanel.tree;
                        var store = tree.getStore();

                        var record = store.getById(this.id);
                        if (record) {
                            var view = tree.getView();
                            var nodeEl = Ext.fly(view.getNodeByRecord(record));
                            if (nodeEl) {
                                var nodeElInner = nodeEl.down(".x-grid-td");
                                if (nodeElInner) {
                                    if (active === true) {
                                        nodeElInner.removeCls("pimcore_unpublished");
                                    } else if (active === false) {
                                        nodeElInner.addCls("pimcore_unpublished");
                                    }
                                }
                            }
                        }


                    } else {
                        pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error");
                }
            }.bind(this)
        });
    }

});
