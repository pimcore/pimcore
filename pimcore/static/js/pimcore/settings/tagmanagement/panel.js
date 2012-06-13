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

pimcore.registerNS("pimcore.settings.tagmanagement.panel");
pimcore.settings.tagmanagement.panel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_tagmanagement");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_tagmanagement",
                title: t("tag_snippet_management"),
                iconCls: "pimcore_icon_tag",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_tagmanagement");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("tagmanagement");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },
    
    getTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_tagmanagement_tree",
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                border: true,
                width: 250,
                split: true,
                root: {
                    nodeType: 'async',
                    id: '0'
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/settings/tag-management-tree',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: false,
                        allowChildren: false,
                        isTarget: false,
                        iconCls: "pimcore_icon_tag",
                        leaf: true
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_tag"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                }
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
                region: "center"
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'click' : this.onTreeNodeClick,
            "contextmenu": this.onTreeNodeContextmenu
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function () {

        Ext.Ajax.request({
            url: "/admin/settings/tag-management-get",
            params: {
                name: this.id
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                var fieldPanel = new pimcore.settings.tagmanagement.item(data, this);
                pimcore.layout.refresh();
            }.bind(this.attributes.reference)
        });
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.attributes.reference.deleteField.bind(this)
        }));

        menu.show(this.ui.getAnchor());
    },

    addField: function () {
        Ext.MessageBox.prompt(t('add_tag'), t('enter_the_name_of_the_new_tag') + "(a-zA-Z-_)", this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {
            Ext.Ajax.request({
                url: "/admin/settings/tag-management-add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getRootNode().reload();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_tag'), t('problem_creating_new_tag'));
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_tag'), t('problem_creating_new_tag'));
        }
    },

    deleteField: function () {
        Ext.Ajax.request({
            url: "/admin/settings/tag-management-delete",
            params: {
                name: this.id
            }
        });

        this.attributes.reference.getEditPanel().removeAll();
        this.remove();
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_tagmanagement");
    }

});

