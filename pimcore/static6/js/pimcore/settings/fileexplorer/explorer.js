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

pimcore.registerNS("pimcore.settings.fileexplorer.explorer");
pimcore.settings.fileexplorer.explorer = Class.create({

    initialize: function () {

        this.openfiles = {};

        this.panel = new Ext.Panel({
            id: "pimcore_fileexplorer",
            title: t("server_fileexplorer"),
            iconCls: "pimcore_icon_fileexplorer",
            border: false,
            layout: "border",
            closable:true,
            items: [this.getTreePanel(), this.getEditorPanel()]
        });

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.panel);
        tabPanel.setActiveItem("pimcore_fileexplorer");


        this.panel.on("destroy", function () {
            pimcore.globalmanager.remove("fileexplorer");
        }.bind(this));
    },

    getTreePanel: function () {

        if(!this.treePanel) {

            var store = Ext.create('Ext.data.TreeStore', {
                proxy: {
                    type: 'ajax',
                    url: '/admin/misc/fileexplorer-tree'
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
                useArrows: true,
                autoScroll: true,
                folderSort:true,
                root: {
                    iconCls: "pimcore_icon_home",
                    type: "folder",
                    expanded: true,
                    id: '/fileexplorer/',
                    text: t("document_root")

                },
                listeners: {
                    itemclick: function (tree, record, item, index, e, eOpts ) {
                        if(record.data.type != "folder") {
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

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        //record.select();

        e.stopEvent();
        var menu = new Ext.menu.Menu();

        if (record.data.type == "folder") {
            menu.add(new Ext.menu.Item({
                text: t('new_file'),
                iconCls: "pimcore_icon_newfile",
                handler: this.addNewFile.bind(this, record),
                disabled: !record.data.writeable
            }));

            menu.add(new Ext.menu.Item({
                text: t('new_folder'),
                iconCls: "pimcore_icon_newfolder",
                handler: this.addNewFolder.bind(this, record),
                disabled: !record.data.writeable
            }));

            menu.add(new Ext.menu.Item({
                text: t('reload'),
                iconCls: "pimcore_icon_reload",
                handler: function (node) {
                    this.treePanel.getStore().load({
                        node: node
                    });
                }.bind(this, record)
            }));
        } else if (record.data.type == "file") {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.deleteFile.bind(this, record),
                disabled: !record.data.writeable
            }));
        }


        menu.showAt(e.pageX, e.pageY);
    },

    addNewFile: function (node) {

        Ext.MessageBox.prompt(t('new_file'), t('please_enter_the_name_of_the_new_file'),
                            function (node, button, value) {
                                Ext.Ajax.request({
                                    url: "/admin/misc/fileexplorer-add",
                                    success: function (node, response) {
                                        this.treePanel.getStore().load({
                                            node: node
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

        Ext.MessageBox.prompt(t('new_folder'), t('please_enter_the_name_of_the_new_folder'),
                            function (node, button, value) {
                                Ext.Ajax.request({
                                    url: "/admin/misc/fileexplorer-add-folder",
                                    success: function (node, response) {
                                        this.treePanel.getStore().load({
                                            node: node
                                        });
                                    }.bind(this, node),
                                    params: {
                                        path: node.id,
                                        filename: value
                                    }
                                });
                            }.bind(this, node));
    },

    deleteFile: function (node) {

        Ext.Ajax.request({
            url: "/admin/misc/fileexplorer-delete",
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

        if(!this.editorPanel) {
            this.editorPanel = new Ext.TabPanel({
                region: "center",
                enableTabScroll:true
            });
        }

        return this.editorPanel;
    },

    openFile: function (path) {

        if(typeof this.openfiles[path] != "undefined") {
            this.openfiles[path].activate();
        } else {
            this.openfiles[path] = new pimcore.settings.fileexplorer.file(path, this);
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_fileexplorer");
    }

});