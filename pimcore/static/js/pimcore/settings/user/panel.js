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


pimcore.registerNS("pimcore.settings.user.panel");
pimcore.settings.user.panel = Class.create({

    documentTreeDataUrl: "/admin/document/get-tree-permissions/",
    assetTreeDataUrl: "/admin/asset/get-tree-permissions/",
    objectTreeDataUrl: "/admin/object/get-tree-permissions/",

    initialize: function () {
        /*Ext.Ajax.request({
            url: "/admin/user/get-available-permissions",
            success: function (transport) {
                this.availablePermissions = Ext.decode(transport.responseText);
            }.bind(this)
        });*/

        this.getTabPanel();
    },

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
                width: 150,
                minSize: 100,
                maxSize: 350,
                root: {
                    nodeType: 'async',
                    draggable:false,
                    id: '0',
                    text: t("all_users"),
                    iconCls: "pimcore_icon_menu_users"
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/user/tree-get-list/',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: true,
                        allowChildren: true,
                        isTarget: true
                    }
                })
            });


            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                activeTab: 0,
                items: [],
                region: 'center'
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'click' : this.onTreeNodeClick,
            "contextmenu": this.onTreeNodeContextmenu,
            "move": this.onTreeNodeMove
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function () {


    },

    onTreeNodeMove: function (tree, element, oldParent, newParent, index) {
        this.attributes.reference.updateUser(this.id, {
            parentId: newParent.id
        });
    },

    onTreeNodeContextmenu: function () {

        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("users")) {

            this.select();
            var menu = new Ext.menu.Menu();

            if (this.allowChildren) {
                menu.add(new Ext.menu.Item({
                    text: t('add_user_group'),
                    iconCls: "pimcore_icon_usergroup_add",
                    listeners: {
                        "click": this.attributes.reference.addUserGroup.bind(this)
                    }
                }));
                menu.add(new Ext.menu.Item({
                    text: t('add_user'),
                    iconCls: "pimcore_icon_user_add",
                    listeners: {
                        "click": this.attributes.reference.addUser.bind(this)
                    }
                }));
            }


            if (this.childNodes == 0 && !this.allowChildren && this.id != user.id) {
                // users
                menu.add(new Ext.menu.Item({
                    text: t('delete_user'),
                    iconCls: "pimcore_icon_user_delete",
                    listeners: {
                        "click": this.attributes.reference.deleteUser.bind(this)
                    }
                }));
            } else if(this.allowChildren) {
                // groups
                var isEnabled = true;
                if (this.childNodes == 0) {
                    isEnabled = false;
                }
                menu.add(new Ext.menu.Item({
                    text: t('delete_user_group'),
                    iconCls: "pimcore_icon_usergroup_delete",
                    listeners: {
                        "click": this.attributes.reference.deleteUser.bind(this)
                    },
                    disabled: isEnabled
                }));
            }

            if(typeof menu.items != "undefined" && typeof menu.items.items != "undefined" && menu.items.items.length > 0) {
                menu.show(this.ui.getAnchor());
            }
        }
    },

    addUser: function () {

        Ext.MessageBox.prompt(t('add_user'), t('please_enter_the_username'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: this.id,
                        //parentId: 0,
                        username: value,
                        hasCredentials: 1,
                        active: 1
                    },
                    success: this.attributes.reference.addUserComplete.bind(this.attributes.reference)
                });
            }
        }.bind(this));
    },

    addUserGroup: function () {
        Ext.MessageBox.prompt(t('add_user_group'), t('please_enter_the_usergroupname'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: this.id,
                        //parentId: 0,
                        username: value,
                        hasCredentials: 0,
                        active: 1
                    },
                    success: this.attributes.reference.addUserComplete.bind(this.attributes.reference)
                });
            }
        }.bind(this));
    },

    addUserComplete: function (transport) {
        try{
            var data = Ext.decode(transport.responseText);
            if(data && data.success){
                var icon = "pimcore_icon_user";
                if (!data.hasCredentials) {
                    icon = "pimcore_icon_usergroup"
                }
                var node = new Ext.tree.TreeNode({
                    listeners: this.getTreeNodeListeners(),
                    reference: this,
                    allowDrop: true,
                    allowChildren: !data.hasCredentials,
                    isTarget: true,
                    text: data.username,
                    id: data.id,
                    iconCls: icon
                });
                var insertedNode = this.tree.getNodeById(data.parentId).appendChild(node);
                try{
                    insertedNode.fireEvent("click");
                } catch (e){}
            } else {

                 pimcore.helpers.showNotification(t("error"), t("user_creation_error"), "error",t(data.message));
            }

        } catch(e){

             pimcore.helpers.showNotification(t("error"), t("user_creation_error"), "error")
        }
    },

    deleteUser: function () {
        Ext.Ajax.request({
            url: "/admin/user/delete",
            params: {
                id: this.id
            }
        });

        this.remove();
    },

    updateUser: function (userId, values) {

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





