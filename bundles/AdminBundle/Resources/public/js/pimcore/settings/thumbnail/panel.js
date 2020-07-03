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

pimcore.registerNS("pimcore.settings.thumbnail.panel");
pimcore.settings.thumbnail.panel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_thumbnails");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_thumbnails",
                title: t("image_thumbnails"),
                iconCls: "pimcore_icon_thumbnails",
                border: false,
                layout: "border",
                closable: true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_thumbnails");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("thumbnails");
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
                    url: Routing.generate('pimcore_admin_settings_thumbnailtree'),
                    reader: {
                        type: 'json'
                    }
                },
                root: {
                    iconCls: "pimcore_icon_thumbnails"
                },
                sorters: ['text']
            });


            this.tree = Ext.create('Ext.tree.Panel', {
                store: store,
                id: "pimcore_panel_thumbnail_tree",
                region: "west",
                autoScroll: true,
                animate: false,
                containerScroll: true,
                width: 200,
                split: true,
                root: {
                    id: '0',
                    expanded: true,
                    iconCls: "pimcore_icon_thumbnails"

                },
                listeners: this.getTreeNodeListeners(),
                rootVisible: false,
                tbar: {
                    cls: 'pimcore_toolbar_border_bottom',
                    items: [
                        {
                            text: t("add"),
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
            'itemclick': this.onTreeNodeClick.bind(this),
            'itemcontextmenu': this.onTreeNodeContextmenu.bind(this)
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts) {
        if (!record.isLeaf()) {
            return;
        }

        this.openThumbnail(record.data.id);
    },

    openThumbnail: function (id) {

        var existingPanel = Ext.getCmp("pimcore_thumbnail_panel_" + id);
        if (existingPanel) {
            this.editPanel.setActiveItem(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_thumbnailget'),
            params: {
                name: id
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                var fieldPanel = new pimcore.settings.thumbnail.item(data, this);
                pimcore.layout.refresh();
            }.bind(this)
        });
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        if (!record.isLeaf()) {
            return;
        }

        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteField.bind(this, tree, record)
        }));


        menu.showAt(e.pageX, e.pageY);
    },

    addField: function () {
        Ext.MessageBox.prompt(' ', t('enter_the_name_of_the_new_item'),
            this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);

        if (button == "ok" && value.length > 2 && regresult == value) {

            var thumbnails = this.tree.getRootNode().childNodes;
            for (var i = 0; i < thumbnails.length; i++) {
                if (thumbnails[i].text == value) {
                    Ext.MessageBox.alert(' ', t('name_already_in_use'));
                    return;
                }
            }

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_settings_thumbnailadd'),
                method: "POST",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load();

                    if (!data || !data.success) {
                        Ext.Msg.alert(' ', t('failed_to_create_new_item'));
                    } else {
                        this.openThumbnail(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(' ', t('failed_to_create_new_item'));
        }
    },

    deleteField: function (tree, record) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_thumbnaildelete'),
            method: 'DELETE',
            params: {
                name: record.data.id
            }
        });

        this.getEditPanel().removeAll();
        record.remove();
    }
});

