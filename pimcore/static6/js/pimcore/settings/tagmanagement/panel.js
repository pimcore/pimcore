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

pimcore.registerNS("pimcore.settings.tagmanagement.panel");
pimcore.settings.tagmanagement.panel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_tagmanagement");
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
            tabPanel.setActiveItem("pimcore_tagmanagement");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("tagmanagement");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },
    
    getTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/settings/tag-management-tree",
                    reader: {
                        type: 'json'
                    }
                },
                root: {
                    iconCls: "pimcore_icon_thumbnails"
                }
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_tagmanagement_tree",
                store: store,
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                width: 250,
                split: true,
                root: {
                    id: '0'
                },
                rootVisible: false,
                tbar: Ext.create('Ext.Toolbar', {
                    cls: 'main-toolbar',
                    items: [
                        {
                            text: t("add_tag"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                }),
                listeners: this.getTreeNodeListeners()
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center",
                plugins:
                    [
                        Ext.create('Ext.ux.TabCloseMenu', {
                            showCloseAll: true,
                            showCloseOthers: true
                        }),
                        Ext.create('Ext.ux.TabReorderer', {})
                    ]
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            'render': function () {
                this.getRootNode().expand();
            },
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                //newChildNode.data.expanded = true;
                newChildNode.data.leaf = true;
                newChildNode.data.iconCls = "pimcore_icon_tag";
            }
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openTag(record.data.id);
    },

    openTag: function (id) {

        var existingPanel = Ext.getCmp("pimcore_tagmanagement_panel_" + id);
        if(existingPanel) {
            this.editPanel.setActiveItem(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/settings/tag-management-get",
            params: {
                name: id
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                var fieldPanel = new pimcore.settings.tagmanagement.item(data, this);
                pimcore.layout.refresh();
            }.bind(this)
        });
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteField.bind(this, tree, record)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    addField: function () {
        Ext.MessageBox.prompt(t('add_tag'), t('enter_the_name_of_the_new_tag') + "(a-zA-Z-_)",
                                                        this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 0 && regresult == value) {

            var tags = this.tree.getRootNode().childNodes;
            for (var i = 0; i < tags.length; i++) {
                if (tags[i].text == value) {
                    Ext.MessageBox.alert(t('add_tag'),
                                         t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
                    return;
                }
            }

            Ext.Ajax.request({
                url: "/admin/settings/tag-management-add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_tag'), t('problem_creating_new_tag'));
                    } else {
                        this.openTag(data.id);
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

    deleteField: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/settings/tag-management-delete",
            params: {
                name: record.data.id
            }
        });

        this.getEditPanel().removeAll();
        record.remove();
    }
});

