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


pimcore.registerNS("pimcore.settings.user.role.panel");
pimcore.settings.user.role.panel = Class.create(pimcore.settings.user.panels.abstract, {

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_roles",
                title: t("roles"),
                iconCls: "pimcore_icon_roles",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getRoleTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_roles");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("roles");
            }.bind(this));

            this.panel.updateLayout();
            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRoleTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                proxy: {
                    type: 'ajax',
                    url: '/admin/user/role-tree-get-childs-by-id/'
                }
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_roles_tree",
                store: store,
                region: "west",
                autoScroll:true,
                animate:false,
                containerScroll: true,
                border: true,
                split:true,
                width: 180,
                root: {
                    draggable:false,
                    id: '0',
                    text: t("all_roles"),
                    allowChildren: true,
                    iconCls: "pimcore_icon_folder",
                    expanded: true
                },
                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop',
                        appendOnly: true,
                        ddGroup: "roles"
                    },
                    listeners: {
                        drop: function(node, data, overModel) {
                            this.update(data.records[0].id, {parentId: overModel.id})
                        }.bind(this)
                    }
                }
                ,
                listeners: this.getTreeNodeListeners()
            });
        }
        this.tree.getRootNode().expand();

        return this.tree;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {

        if(!record.data.allowChildren && record.data.id > 0) {
            var rolePanelKey = "role_" + record.data.id;
            if(this.panels[rolePanelKey]) {
                this.panels[rolePanelKey].activate();
            } else {
                var rolePanel = new pimcore.settings.user.role.tab(this, record.data.id);
                this.panels[rolePanelKey] = rolePanel;
            }
        }
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();

        if (record.data.allowChildren) {
            menu.add(new Ext.menu.Item({
                text: t('add_folder'),
                iconCls: "pimcore_icon_folder pimcore_icon_overlay_add",
                listeners: {
                    "click": this.add.bind(this, "rolefolder", null, record)
                }
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_role'),
                iconCls: "pimcore_icon_roles pimcore_icon_overlay_add",
                listeners: {
                    "click": this.add.bind(this, "role", null, record)
                }
            }));
        } else if (record.data.elementType == "role") {
            menu.add(new Ext.menu.Item({
                text: t('clone_role'),
                iconCls: "pimcore_icon_roles pimcore_icon_overlay_add",
                listeners: {
                    "click": this.add.bind(this, "role", record, record)
                }
            }));
        }

        if (record.data.id > 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                listeners: {
                    "click": this.remove.bind(this, tree, record)
                }
            }));
        }

        if(typeof menu.items != "undefined" && typeof menu.items.items != "undefined"
                                                                    && menu.items.items.length > 0) {
            menu.showAt(e.pageX, e.pageY);
        }
        e.stopEvent();
    },

    addComplete: function (parentNode, transport) {
        try{
            var data = Ext.decode(transport.responseText);
            if(data && data.success){
                var tree = parentNode.getOwnerTree();
                tree.getStore().reload({
                    node: parentNode
                });
            } else {
                 pimcore.helpers.showNotification(t("error"), t("role_creation_error"), "error",t(data.message));
            }

        } catch(e){
            console.log(e);
            pimcore.helpers.showNotification(t("error"), t("role_creation_error"), "error");
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
                        pimcore.helpers.showNotification(t("success"), t("role_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("role_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("role_save_error"), "error");
                }
            }.bind(this)
        });
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_roles");
    }
});





