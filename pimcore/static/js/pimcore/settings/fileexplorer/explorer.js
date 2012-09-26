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
        tabPanel.activate("pimcore_fileexplorer");


        this.panel.on("destroy", function () {
            pimcore.globalmanager.remove("fileexplorer");
        }.bind(this));
    },

    getTreePanel: function () {

        if(!this.treePanel) {
            this.treePanel = new Ext.tree.TreePanel({
                region: "west",
                width: 300,
                rootVisible: true,
                enableDD: false,
                useArrows: true,
                autoScroll: true,
                root: {
                    nodeType: 'async',
                    text: t("document_root"),
                    id: '/fileexplorer/',
                    iconCls: "pimcore_icon_home",
                    expanded: true,
                    type: "folder"
                },
                dataUrl: "/admin/misc/fileexplorer-tree",
                listeners: {
                    click: function(n) {
                        if(n.attributes.type != "folder") {
                            this.openFile(n.id);
                        }
                    }.bind(this),
                    contextmenu: this.onTreeNodeContextmenu.bind(this)
                }
            });

            new Ext.tree.TreeSorter(this.treePanel, {folderSort:true});
        }

        return this.treePanel;
    },

    onTreeNodeContextmenu: function (n) {
        n.select();

        var menu = new Ext.menu.Menu();

        if (n.attributes.type == "folder") {
            menu.add(new Ext.menu.Item({
                text: t('new_file'),
                iconCls: "pimcore_icon_newfile",
                handler: this.addNewFile.bind(this, n),
                disabled: !n.attributes.writeable
            }));

            menu.add(new Ext.menu.Item({
                text: t('new_folder'),
                iconCls: "pimcore_icon_newfolder",
                handler: this.addNewFolder.bind(this, n),
                disabled: !n.attributes.writeable
            }));

            menu.add(new Ext.menu.Item({
                text: t('reload'),
                iconCls: "pimcore_icon_reload",
                handler: function (node) {
                    node.reload();
                }.bind(this, n)
            }));
        } else if (n.attributes.type == "file") {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.deleteFile.bind(this, n),
                disabled: !n.attributes.writeable
            }));
        }

        menu.show(n.ui.getAnchor());
    },

    addNewFile: function (node) {

        Ext.MessageBox.prompt(t('new_file'), t('please_enter_the_name_of_the_new_file'), function (node, button, value) {
            Ext.Ajax.request({
                url: "/admin/misc/fileexplorer-add",
                success: function (node, response) {
                    node.reload();
                }.bind(this, node),
                params: {
                    path: node.id,
                    filename: value
                }
            });
        }.bind(this, node));
    },

    addNewFolder: function (node) {

        Ext.MessageBox.prompt(t('new_folder'), t('please_enter_the_name_of_the_new_folder'), function (node, button, value) {
            Ext.Ajax.request({
                url: "/admin/misc/fileexplorer-add-folder",
                success: function (node, response) {
                    node.reload();
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
                node.parentNode.reload();
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

        if(Ext.isIE8) {
            alert(t("not_supported_by_your_browser"));
            return;
        }

        if(typeof this.openfiles[path] != "undefined") {
            this.openfiles[path].activate();
        } else {
            this.openfiles[path] = new pimcore.settings.fileexplorer.file(path, this);
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_fileexplorer");
    }

});