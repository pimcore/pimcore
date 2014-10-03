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


pimcore.registerNS("pimcore.settings.user.panel");
pimcore.settings.user.panel = Class.create(pimcore.settings.user.panels.abstract, {

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_users",
                title: t("users"),
                iconCls: "pimcore_icon_users",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getUserTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_users");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("users");
            }.bind(this));

            pimcore.layout.refresh();


        }

        return this.panel;
    },

    getUserTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_users_tree",
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                enableDD:true,
                ddGroup: "users",
                containerScroll: true,
                border: true,
                split:true,
                width: 180,
                minSize: 100,
                maxSize: 350,
                root: {
                    nodeType: 'async',
                    draggable:false,
                    id: '0',
                    text: t("all_users"),
                    allowChildren: true
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/user/tree-get-childs-by-id/',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: true,
                        allowChildren: true,
                        isTarget: true
                    }
                }),
                tbar: ["->", {
                    text: t("search"),
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchPanel.bind(this)
                }]
            });


            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    openSearchPanel: function () {

        var store = new Ext.data.JsonStore({
            url: '/admin/user/search',
            root: 'users',
            fields: ["id", 'name', "email", "firstname", "lastname"]
        });

        var resultTpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item" style="padding: 3px 10px 3px 10px; border: 1px solid #fff; border-bottom: 1px solid #eeeeee; color: #555;">',
            '<img style="float:left; padding-right: 10px; max-height:30px;" src="/admin/user/get-image?id={id}" />',
            '<h3>{name} - {firstname} {lastname}</h3>',
            '{email} <b>ID: </b> {id}',
            '</div></tpl>'
        );

        var win = new Ext.Window({
            title: t("search"),
            iconCls: "pimcore_icon_search",
            width: 320,
            height: 110,
            modal: true,
            bodyStyle:"padding:10px",
            items: [{
                xtype: "combo",
                store: store,
                displayField:'name',
                valueField: "id",
                typeAhead: false,
                loadingText: t('searching'),
                width: 285,
                minChars: 1,
                queryDelay: 100,
                hideTrigger:true,
                tpl: resultTpl,
                itemSelector: 'div.search-item',
                triggerAction: "all",
                listeners: {
                    select: function(combo, record, index){
                        this.openUser(record.get("id"));
                        win.close();
                    }.bind(this),
                    afterrender: function () {
                        this.focus(true,500);
                    }
                }
            }],
            buttons: [{
                text: t("close"),
                iconCls: "pimcore_icon_delete",
                handler: function () {
                    win.close();
                }
            }]
        });

        win.show();
    },

    openUser: function(userId) {
        var userPanelKey = "user_" + userId;
        if(this.panels[userPanelKey]) {
            this.panels[userPanelKey].activate();
        } else {
            var userPanel = new pimcore.settings.user.usertab(this, userId);
            this.panels[userPanelKey] = userPanel;
        }

    },

    onTreeNodeClick: function (node) {

        var user = pimcore.globalmanager.get("user");
        if(node.attributes["admin"] && !user.admin) {
            Ext.MessageBox.alert(t("error"), t("you_are_not_allowed_to_manage_admin_users"));
            return;
        }

        if(!node.attributes.allowChildren && node.id > 0) {
            this.openUser(node.id);
        }
    },

    onTreeNodeContextmenu: function () {

        var user = pimcore.globalmanager.get("user");

        if(this.attributes.admin && !user.admin) {
            // only admin users are allowed to manage admin users
            return;
        }

        this.select();
        var menu = new Ext.menu.Menu();

        if (this.allowChildren) {
            menu.add(new Ext.menu.Item({
                text: t('add_folder'),
                iconCls: "pimcore_icon_folder_add",
                listeners: {
                    "click": this.attributes.reference.add.bind(this, "userfolder", 0)
                }
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_user'),
                iconCls: "pimcore_icon_user_add",
                listeners: {
                    "click": this.attributes.reference.add.bind(this, "user", 0)
                }
            }));
        } else if (this.attributes.elementType == "user") {
            menu.add(new Ext.menu.Item({
                text: t('clone_user'),
                iconCls: "pimcore_icon_user_add",
                listeners: {
                    "click": this.attributes.reference.add.bind(this, "user", this.attributes.id)
                }
            }));
        }

        if (this.id != user.id && (this.attributes.type != "userfolder" || user.admin)) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                listeners: {
                    "click": this.attributes.reference.remove.bind(this)
                }
            }));
        }

        if(typeof menu.items != "undefined" && typeof menu.items.items != "undefined"
            && menu.items.items.length > 0) {
            menu.show(this.ui.getAnchor());
        }

    },

    addComplete: function (parentId, transport) {
        try{
            var data = Ext.decode(transport.responseText);
            if(data && data.success){
                this.tree.getNodeById(parentId).reload();
            } else {
                pimcore.helpers.showNotification(t("error"), t("user_creation_error"), "error",t(data.message));
            }

        } catch(e){
            pimcore.helpers.showNotification(t("error"), t("user_creation_error"), "error");
        }
    },

    update: function (userId, values) {

        Ext.Ajax.request({
            url: "/admin/user/update",
            method: "post",
            params: {
                id: userId,
                data: Ext.encode(values)
            },
            success: function (transport) {
                try{
                    var res = Ext.decode(transport.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("user_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error");
                }
            }.bind(this)
        });
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_users");
    }
});





