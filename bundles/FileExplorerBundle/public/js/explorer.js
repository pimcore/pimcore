/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.fileexplorer.explorer");
/**
 * @private
 */
pimcore.bundle.fileexplorer.explorer = Class.create({

    initialize: function () {

        this.openfiles = {};

        this.panel = new Ext.Panel({
            id: "pimcore_fileexplorer",
            title: t("pimcore_file_explorer_bundle_server_file_explorer"),
            iconCls: "pimcore_icon_folder pimcore_icon_overlay_search",
            border: false,
            layout: "border",
            closable: true,
            items: [this.getTreePanel(), this.getEditorPanel()]
        });

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.panel);
        tabPanel.setActiveItem("pimcore_fileexplorer");


        this.panel.on("destroy", function () {
            pimcore.globalmanager.remove("bundle_file_explorer");
        }.bind(this));
    },

    getTreePanel: function () {

        if (!this.treePanel) {

            var store = Ext.create('Ext.data.TreeStore', {
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_bundle_file_explorer_settings_tree')
                },
                folderSort: true,
                sorters: [{
                    property: 'text',
                    direction: 'ASC'
                }]
            });

            this.treePanel = Ext.create('Ext.tree.Panel', {
                store: store,
                region: "west",
                width: 300,
                rootVisible: true,
                enableDD: false,
                scrollable: true,
                folderSort: true,
                split: true,
                root: {
                    iconCls: "pimcore_icon_home",
                    type: "folder",
                    expanded: true,
                    id: '/fileexplorer/',
                    text: t("pimcore_file_explorer_bundle_document_root"),
                    writeable: true
                },
                listeners: {
                    itemclick: function (tree, record, item, index, e, eOpts) {
                        if (record.data.type != "folder") {
                            this.openFile(record.data.id);
                        } else {
                            record.expand();
                        }
                    }.bind(this),
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this)
                }
            });
        }

        return this.treePanel;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();
        var menu = new Ext.menu.Menu();

        if (record.data.type == "folder") {
            menu.add(new Ext.menu.Item({
                text: t('pimcore_file_explorer_bundle_new_file'),
                iconCls: "pimcore_icon_file pimcore_icon_overlay_add",
                handler: this.addNewFile.bind(this, record),
                disabled: !record.data.writeable
            }));

            menu.add(new Ext.menu.Item({
                text: t('pimcore_file_explorer_bundle_new_folder'),
                iconCls: "pimcore_icon_folder pimcore_icon_overlay_add",
                handler: this.addNewFolder.bind(this, record),
                disabled: !record.data.writeable
            }));

            menu.add(new Ext.menu.Item({
                text: t('pimcore_file_explorer_bundle_reload'),
                iconCls: "pimcore_icon_reload",
                handler: function (node) {
                    this.treePanel.getStore().load({
                        node: node
                    });
                }.bind(this, record)
            }));
        } else if (record.data.type == "file") {
            menu.add(new Ext.menu.Item({
                text: t('pimcore_file_explorer_bundle_delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.deleteFile.bind(this, record),
                disabled: !record.data.writeable
            }));
        }

        if (record.parentNode) {
            menu.add(new Ext.menu.Item({
                text: t('pimcore_file_explorer_bundle_rename'),
                iconCls: "pimcore_icon_key",
                handler: this.rename.bind(this, record),
                disabled: !record.data.writeable
            }));
        }

        menu.showAt(e.pageX, e.pageY);
    },

    addNewFile: function (node) {

        Ext.MessageBox.prompt(t('pimcore_file_explorer_bundle_new_file'), t('pimcore_file_explorer_bundle_enter_name'),
            function (node, button, value) {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_bundle_file_explorer_settings_add'),
                    method: "POST",
                    success: function (node, response) {
                        node.data.loaded = false;

                        this.treePanel.getStore().load({
                            node: node,
                            callback: function () {
                                node.expand();
                            }
                        });
                    }.bind(this, node),
                    params: {
                        path: node.id,
                        filename: value
                    }
                });
            }.bind(this, node));
    },

    addNewFolder: function (node) {

        Ext.MessageBox.prompt(t('pimcore_file_explorer_bundle_new_folder'), t('pimcore_file_explorer_bundle_enter_name'),
            function (node, button, value) {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_bundle_file_explorer_settings_addfolder'),
                    method: "POST",
                    success: function (node, response) {
                        node.data.loaded = false;

                        this.treePanel.getStore().load({
                            node: node,
                            callback: function () {
                                node.expand();
                            }
                        });

                    }.bind(this, node),
                    params: {
                        path: node.id,
                        filename: value
                    }
                });
            }.bind(this, node));
    },

    rename: function (node) {

        Ext.MessageBox.prompt(t('pimcore_file_explorer_bundle_rename'), t('pimcore_file_explorer_bundle_enter_new_name'),
            function (node, button, value) {
                if (button == "ok") {
                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_bundle_file_explorer_settings_rename'),
                        method: 'PUT',
                        success: function (node, response) {
                            if (this.openfiles[node.id]) {
                                this.openfiles[node.id].updatePath(node.parentNode.id + value);
                            }
                            this.treePanel.getStore().load({
                                node: node.parentNode,
                                callback: function (parentNode) {
                                    if (parentNode) {
                                        parentNode.expand();
                                    }
                                }.bind(this, node.parentNode)
                            });
                        }.bind(this, node),
                        params: {
                            path: node.id,
                            newPath: node.parentNode.id + "/" + value
                        }
                    });
                }
            }.bind(this, node),
            this,
            false,
            node.data.text);
    },

    deleteFile: function (node) {

        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_file_explorer_settings_delete'),
            method: 'DELETE',
            success: function (node, response) {
                this.treePanel.getStore().load({
                    node: node.parentNode
                });
            }.bind(this, node),
            params: {
                path: node.id
            }
        });
    },

    getEditorPanel: function () {

        if (!this.editorPanel) {
            this.editorPanel = new Ext.TabPanel({
                region: "center",
                enableTabScroll: true
            });
        }

        return this.editorPanel;
    },

    openFile: function (path) {

        if (typeof this.openfiles[path] != "undefined") {
            this.openfiles[path].activate();
        } else {
            this.openfiles[path] = new pimcore.bundle.fileexplorer.file(path, this);
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_fileexplorer");
    }

});
