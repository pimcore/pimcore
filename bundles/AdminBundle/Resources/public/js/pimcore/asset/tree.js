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

pimcore.registerNS("pimcore.asset.tree");
pimcore.asset.tree = Class.create({

    treeDataUrl: null,

    initialize: function(config, perspectiveCfg) {
        this.treeDataUrl = Routing.generate('pimcore_admin_asset_treegetchildsbyid');
        this.perspectiveCfg = perspectiveCfg;
        if (!perspectiveCfg) {
            this.perspectiveCfg = {
                position: "left"
            };
        }

        this.perspectiveCfg = new pimcore.perspective(this.perspectiveCfg);
        this.position = this.perspectiveCfg.position ? this.perspectiveCfg.position : "left";

        if (!config) {
            this.config = {
                rootId: 1,
                rootVisible: true,
                loaderBaseParams: {},
                treeId: "pimcore_panel_tree_assets",
                treeIconCls: "pimcore_icon_main_tree_asset pimcore_icon_material",
                treeTitle: t('assets'),
                parentPanel: Ext.getCmp("pimcore_panel_tree_" + this.position),
            };
        }
        else {
            this.config = config;
        }

        pimcore.layout.treepanelmanager.register(this.config.treeId);

        // get root node config
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_asset_treegetroot'),
            params: {
                id: this.config.rootId,
                view: this.config.customViewId,
                elementType: "asset"
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var callback = function () {};
                if(res["id"]) {
                    callback = this.init.bind(this, res);
                }
                pimcore.layout.treepanelmanager.initPanel(this.config.treeId, callback);
            }.bind(this)
        });
    },

    init: function(rootNodeConfig) {

        var itemsPerPage = pimcore.settings['asset_tree_paging_limit'];

        rootNodeConfig.text = t("home");
        rootNodeConfig.allowDrag = true;
        rootNodeConfig.id = "" +  rootNodeConfig.id;
        rootNodeConfig.iconCls = "pimcore_icon_home";
        rootNodeConfig.cls = "pimcore_tree_node_root";
        rootNodeConfig.expanded = true;

        var store = Ext.create('pimcore.data.PagingTreeStore', {
            autoLoad: false,
            autoSync: false,
            proxy: {
                type: 'ajax',
                url: this.treeDataUrl,
                reader: {
                    type: 'json',
                    totalProperty : 'total',
                    rootProperty: 'nodes'

                },
                extraParams: {
                    limit: itemsPerPage,
                    view: this.config.customViewId
                },
                timeout: 60000
            },
            pageSize: itemsPerPage,
            root: rootNodeConfig
        });

        // assets
        this.tree = Ext.create('pimcore.tree.Panel', {
            selModel : {
                mode : 'MULTI'
            },
            store: store,
            autoLoad: false,
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            cls: this.config['rootVisible'] ? '' : 'pimcore_tree_no_root_node',
            autoScroll:true,
            animate:false,
            containerScroll: true,
            ddAppendOnly: true,
            rootVisible: this.config.rootVisible,
            forceLayout: true,
            bufferedRenderer: false,
            border: false,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    appendOnly: true,
                    ddGroup: "element"
                },
                listeners: {
                    beforedrop: function (node, data) {
                    },
                    nodedragover: this.onTreeNodeOver.bind(this),
                    startdrag: function() {
                    }
                },
                xtype: 'pimcoretreeview'

            },
            tools: [{
                type: "right",
                handler: pimcore.layout.treepanelmanager.toRight.bind(this),
                hidden: this.position == "right"
            },{
                type: "left",
                handler: pimcore.layout.treepanelmanager.toLeft.bind(this),
                hidden: this.position == "left"
            }],
            root: rootNodeConfig,
            listeners: this.getTreeNodeListeners()
        });

        //TODO
        this.tree.getView().on("itemafterrender",this.enableHtml5Upload.bind(this));
        this.tree.on("render", function () {
            this.getRootNode().expand();
        });
        this.tree.on("afterrender", function () {
            try {
                this.tree.loadMask = new Ext.LoadMask({
                    target: this.tree,
                    msg: t("please_wait"),
                    hidden: true
                });

                // add listener to root node -> other nodes are added om the "append" event -> see this.enableHtml5Upload()
                this.addHtml5DragListener(this.tree.getRootNode());

                // html5 upload
                if (window["FileList"]) {
                    this.tree.getEl().dom.addEventListener("drop", function (e) {

                        e.stopPropagation();
                        e.preventDefault();

                        pimcore.helpers.treeNodeThumbnailPreviewHide();

                        try {
                            var selection = this.tree.getSelection();
                            if (!selection) {
                                return true;
                            }
                            if (selection.length < 1) {
                                return true;
                            }
                        } catch (e2) {
                            return true;
                        }

                        var node = selection[0];
                        this.uploadFileList(e.dataTransfer, node);

                    }.bind(this), true);
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));

        if(!pimcore.settings.asset_disable_tree_preview) {
            this.tree.on("itemmouseenter", pimcore.helpers.treeNodeThumbnailPreview.bind(this));
            this.tree.on("itemmouseleave", pimcore.helpers.treeNodeThumbnailPreviewHide.bind(this));
        }

        store.on("nodebeforeexpand", function (node) {
            pimcore.helpers.addTreeNodeLoadingIndicator("asset", node.data.id, false);
        });

        store.on("nodeexpand", function (node, index, item, eOpts) {
            pimcore.helpers.removeTreeNodeLoadingIndicator("asset", node.data.id);
        });

        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.updateLayout();

        if (!this.config.parentPanel.alreadyExpanded && this.perspectiveCfg.expanded) {
            this.config.parentPanel.alreadyExpanded = true;
            this.tree.expand();
        }
    },

    uploadFileList: function (dataTransfer, parentNode) {

        var file;
        this.activeUploads = 0;


        var win = new Ext.Window({
            items: [],
            modal: true,
            closable: false,
            bodyStyle: "padding:10px;",
            width: 500,
            autoHeight: true,
            autoScroll: true
        });
        win.show();

        var doFileUpload = function (file, path) {

            if(typeof path == "undefined") {
                path = "";
            }

            this.activeUploads++;

            var pbar = new Ext.ProgressBar({
                width:465,
                text: file.name,
                style: "margin-bottom: 5px"
            });

            win.add(pbar);
            win.updateLayout();

            var finishedErrorHandler = function (e) {
                this.activeUploads--;
                win.remove(pbar);

                if(this.activeUploads < 1) {
                    win.close();
                    pimcore.elementservice.refreshNodeAllTrees("asset", parentNode.get("id"));
                }
            }.bind(this);

            var errorHandler = function (e) {
                var res = Ext.decode(e["responseText"]);
                pimcore.helpers.showNotification(t("error"), res.message ? res.message : t("error"), "error", e["responseText"]);
                finishedErrorHandler();
            }.bind(this);

            pimcore.helpers.uploadAssetFromFileObject(file,
                Routing.generate('pimcore_admin_asset_addasset', {parentId: parentNode.id, dir: path}),
                finishedErrorHandler,
                function (evt) {
                    //progress
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        var progressText = file.name + " ( " + Math.floor(percentComplete*100) + "% )";
                        if(percentComplete == 1) {
                            progressText = file.name + " " + t("please_wait");
                        }

                        pbar.updateProgress(percentComplete, progressText);
                    }
                },
                errorHandler
            );
        }.bind(this);

        if(dataTransfer["items"] && dataTransfer.items[0] && dataTransfer.items[0].webkitGetAsEntry) {
            // chrome
            var traverseFileTree = function (item, path) {
                path = path || "";
                if (item.isFile) {
                    // Get file
                    item.file(function (file) {
                        doFileUpload(file, path);
                    }.bind(this));
                } else if (item.isDirectory) {
                    // Get folder contents
                    var dirReader = item.createReader();
                    dirReader.readEntries(function (entries) {
                        for (var i = 0; i < entries.length; i++) {
                            traverseFileTree(entries[i], path + item.name + "/");
                        }
                    });
                }
            }.bind(this);

            for (var i = 0; i < dataTransfer.items.length; i++) {
                // webkitGetAsEntry is where the magic happens
                var item = dataTransfer.items[i].webkitGetAsEntry();
                if (item) {
                    traverseFileTree(item);
                }
            }
        } else if(dataTransfer["files"]) {
            // default filelist upload
            for (var i=0; i<dataTransfer["files"].length; i++) {
                file = dataTransfer["files"][i];

                if (window.FileList && file.name && file.size) { // check for size (folder has size=0)
                    doFileUpload(file);
                } else if (!empty(file.type) && file.size < 1) { //throw error for 0 byte file
                    Ext.MessageBox.alert(t('error'), t('error_empty_file_upload'));
                    win.close();
                }
            }

            // if no files are uploaded (doesn't match criteria, ...) close the progress win immediately
            if(!this.activeUploads) {
                win.close();
            }
        }

        // check in 5 sec. if there're active uploads
        // if not, close the progressbar
        // this is necessary since the folder upload is async, so we don't know if the progress is
        // necessary or not, not really perfect solution, but works as it should
        window.setTimeout(function () {
            if(!this.activeUploads) {
                win.close();
            }
        }.bind(this), 5000);
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick,
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            "itemmove": this.onTreeNodeMove.bind(this),
            "beforeitemmove": this.onTreeNodeBeforeMove.bind(this),
            "itemmouseenter": function (el, record, item, index, e, eOpts) {
                pimcore.helpers.treeToolTipShow(el, record, item);
            },
            "itemmouseleave": function () {
                pimcore.helpers.treeToolTipHide();
            }
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, event, eOpts ) {
        if (event.ctrlKey === false && event.shiftKey === false && event.altKey === false) {
            if (record.data.permissions.view) {
                pimcore.helpers.treeNodeThumbnailPreviewHide();
                pimcore.helpers.openAsset(record.data.id, record.data.type);
            }
        }
    },


    onTreeNodeOver: function (targetNode, position, dragData, e, eOpts ) {
        var node = dragData.records[0];
        if (node.getOwnerTree() != targetNode.getOwnerTree()) {
            return false;
        }
        // check for permission
        try {
            if (node.data.permissions.settings) {
                return true;
            }
        }
        catch (e) {
            console.log(e);
        }

        return false;
    },

    onTreeNodeMove: function (node, oldParent, newParent, index, eOpts ) {

        var tree = node.getOwnerTree();

        pimcore.elementservice.updateAsset(node.data.id, {
            parentId: newParent.data.id
        }, function (newParent, oldParent, tree, response) {
            try{
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.data.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    node.data.basePath = newBasePath;
                    node.data.path = node.data.basePath + "/" + node.data.text;
                    pimcore.elementservice.nodeMoved("asset", oldParent, newParent);
                }
                else {
                    this.tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                        "error",t(rdata.message));
                    // we have to delay refresh between two nodes,
                    // as there could be parent child relationship leading to race condition
                    window.setTimeout(function () {
                        pimcore.elementservice.refreshNode(oldParent);
                    }, 500);
                    pimcore.elementservice.refreshNode(newParent);
                }
            } catch(e){
                this.tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                // we have to delay refresh between two nodes,
                // as there could be parent child relationship leading to race condition
                window.setTimeout(function () {
                    pimcore.elementservice.refreshNode(oldParent);
                }, 500);
                pimcore.elementservice.refreshNode(newParent);
            }
            this.tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree));
    },

    onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts ) {
        if (oldParent.getOwnerTree().getId() != newParent.getOwnerTree().getId()) {
            Ext.MessageBox.alert(t('error'), t('cross_tree_moves_not_supported'));
            return false;
        }

        // check for locks
        if (node.data.locked) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

        // check new parent's permission
        if(!newParent.data.permissions.create){
            Ext.MessageBox.alert(' ', t('element_cannot_be_moved'));
            return false;
        }

        // check for permission
        if (node.data.permissions.settings) {
            this.tree.loadMask.show();
            return true;
        }
        return false;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();

        if(pimcore.helpers.hasTreeNodeLoadingIndicator("asset", record.id)) {
            return;
        }

        var menu = new Ext.menu.Menu();
        var perspectiveCfg = this.perspectiveCfg;

        if(tree.getSelectionModel().getSelected().length > 1) {
            var selectedIds = [];
            tree.getSelectionModel().getSelected().each(function (item) {
                selectedIds.push(item.id);
            });

            if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("asset.delete")) {
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: this.deleteAsset.bind(this, selectedIds.join(','))
                }));
            }
        } else {
            if (record.data.type == "folder") {
                if (record.data.permissions.create) {

                    var menuItems = [];

                    if (perspectiveCfg.inTreeContextMenu("asset.add")) {
                        if (perspectiveCfg.inTreeContextMenu("asset.add.upload")) {
                            menuItems.push({
                                text: t("upload_files"),
                                iconCls: "pimcore_icon_upload",
                                listeners: {
                                    "afterrender": function (el, eOpts) {
                                        // we need to do this vanilla javascript and directly after finishing rendering
                                        // otherwise this will cause issues when used with hybrid touch devices, see also:
                                        // https://github.com/pimcore/pimcore/issues/1836
                                        var fileElemId = 'assetMultiUploadField';
                                        if (!document.getElementById(fileElemId)) {
                                            document.body.insertAdjacentHTML('beforeend', '<input type="file" id="' + fileElemId + '" multiple>');
                                        }

                                        var fileSelect = el.getEl().down('a', true),
                                            fileElem = document.getElementById(fileElemId);

                                        if (fileElem['onChangeListener']) {
                                            fileElem.removeEventListener('change', fileElem['onChangeListener']);
                                        }

                                        fileElem['onChangeListener'] = function (e) {
                                            if (e.target.files.length) {
                                                this.uploadFileList(e.target, record);
                                            }
                                        }.bind(this);

                                        fileElem.addEventListener("change", fileElem['onChangeListener']);

                                        fileSelect.addEventListener("click", function (e) {
                                            if (fileElem) {
                                                fileElem.value = fileElem.defaultValue;
                                                fileElem.click();
                                            }
                                            e.preventDefault();
                                        }, false);
                                    }.bind(this)
                                }
                            });
                        }

                        if (perspectiveCfg.inTreeContextMenu("asset.add.uploadCompatibility")) {
                            menuItems.push({
                                text: t("upload_compatibility_mode"),
                                handler: this.addSingleAsset.bind(this, tree, record),
                                iconCls: "pimcore_icon_upload"
                            });
                        }

                        if (perspectiveCfg.inTreeContextMenu("asset.add.uploadZip")) {
                            menuItems.push({
                                text: t("upload_zip"),
                                handler: this.uploadZip.bind(this, tree, record),
                                iconCls: "pimcore_icon_zip pimcore_icon_overlay_upload"
                            });
                        }

                        if (perspectiveCfg.inTreeContextMenu("asset.add.importFromServer")) {
                            menuItems.push({
                                text: t("import_from_server"),
                                handler: this.importFromServer.bind(this, tree, record),
                                iconCls: "pimcore_icon_import_server"
                            });
                        }

                        if (perspectiveCfg.inTreeContextMenu("asset.add.uploadFromUrl")) {
                            menuItems.push({
                                text: t("import_from_url"),
                                handler: this.importFromUrl.bind(this, tree, record),
                                iconCls: "pimcore_icon_world pimcore_icon_overlay_add"
                            });
                        }

                        if (menuItems.length > 0) {
                            menu.add(new Ext.menu.Item({
                                text: t('add_assets'),
                                iconCls: "pimcore_icon_asset pimcore_icon_overlay_add",
                                hideOnClick: false,
                                menu: menuItems
                            }));
                        }
                    }

                    if (perspectiveCfg.inTreeContextMenu("asset.addFolder")) {
                        menu.add(new Ext.menu.Item({
                            text: t('create_folder'),
                            iconCls: "pimcore_icon_folder pimcore_icon_overlay_add",
                            handler: this.addFolder.bind(this, tree, record)
                        }));
                    }

                    menu.add("-");

                }
            }

            if (record.data.permissions.rename && record.data.id != 1 && !record.data.locked) {
                if (perspectiveCfg.inTreeContextMenu("asset.rename")) {
                    menu.add(new Ext.menu.Item({
                        text: t('rename'),
                        iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                        handler: this.editAssetKey.bind(this, tree, record)
                    }));
                }
            }

            if (this.id != 1 && record.data.permissions.view) {
                if (perspectiveCfg.inTreeContextMenu("asset.copy")) {
                    menu.add(new Ext.menu.Item({
                        text: t('copy'),
                        iconCls: "pimcore_icon_copy",
                        handler: this.copy.bind(this, tree, record)
                    }));
                }
            }

            //cut
            if (record.data.id != 1 && !record.data.locked && record.data.permissions.rename) {
                if (perspectiveCfg.inTreeContextMenu("asset.cut")) {
                    menu.add(new Ext.menu.Item({
                        text: t('cut'),
                        iconCls: "pimcore_icon_cut",
                        handler: this.cut.bind(this, tree, record)
                    }));
                }
            }


            //paste
            if (pimcore.cachedAssetId
                && (record.data.permissions.create || record.data.permissions.publish)
                && perspectiveCfg.inTreeContextMenu("asset.paste")) {

                if (record.data.type == "folder") {
                    menu.add(new Ext.menu.Item({
                        text: t('paste'),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteInfo.bind(this, tree, record, "recursive")
                    }));
                } else {
                    menu.add(new Ext.menu.Item({
                        text: t('paste'),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteInfo.bind(this, tree, record, "replace")
                    }));
                }
            }

            if (record.data.type == "folder" && pimcore.cutAsset
                && (record.data.permissions.create || record.data.permissions.publish)
                && perspectiveCfg.inTreeContextMenu("asset.pasteCut")) {
                menu.add(new Ext.menu.Item({
                    text: t('paste_cut_element'),
                    iconCls: "pimcore_icon_paste",
                    handler: function () {
                        this.pasteCutAsset(pimcore.cutAsset,
                            pimcore.cutAssetParentNode, record, this.tree);
                        pimcore.cutAssetParentNode = null;
                        pimcore.cutAsset = null;
                    }.bind(this)
                }));
            }

            if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("asset.delete")) {
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: this.deleteAsset.bind(this, record.data.id)
                }));
            }

            // upload & download
            if (record.data.permissions.view) {
                menu.add("-");

                if (record.data.type == "folder") {
                    menu.add({
                        text: t("download_as_zip"),
                        iconCls: "pimcore_icon_zip pimcore_icon_overlay_download",
                        handler: function () {
                            pimcore.elementservice.downloadAssetFolderAsZip(record.data.id)
                        }
                    });
                } else {
                    if (record.data.permissions.publish) {
                        menu.add(new Ext.menu.Item({
                            text: t('upload_new_version'),
                            iconCls: "pimcore_icon_upload",
                            handler: function () {
                                pimcore.elementservice.replaceAsset(record.data.id, function () {
                                    pimcore.elementservice.refreshNodeAllTrees("asset", record.parentNode.id);
                                });
                            }
                        }));
                    }

                    menu.add(new Ext.menu.Item({
                        text: t('download'),
                        iconCls: "pimcore_icon_download",
                        handler: function () {
                            pimcore.helpers.download(Routing.generate('pimcore_admin_asset_download', {id: record.data.id}));
                        }
                    }));
                }
            }

            // advanced menu
            var advancedMenuItems = [];
            var user = pimcore.globalmanager.get("user");

            if (record.data.permissions.create && !record.data.locked && perspectiveCfg.inTreeContextMenu("asset.searchAndMove")) {
                advancedMenuItems.push({
                    text: t('search_and_move'),
                    iconCls: "pimcore_icon_search pimcore_icon_overlay_go",
                    handler: this.searchAndMove.bind(this, tree, record)
                });
            }

            if (record.data.id != 1 && user.admin) {
                var lockMenu = [];
                if (record.data.lockOwner && perspectiveCfg.inTreeContextMenu("asset.unlock")) { // add unlock
                    lockMenu.push({
                        text: t('unlock'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_delete",
                        handler: function () {
                            pimcore.elementservice.lockElement({
                                elementType: "asset",
                                id: record.data.id,
                                mode: null
                            });
                        }.bind(this)
                    });
                } else if (perspectiveCfg.inTreeContextMenu("asset.lock")) {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_add",
                        handler: function () {
                            pimcore.elementservice.lockElement({
                                elementType: "asset",
                                id: record.data.id,
                                mode: "self"
                            });
                        }.bind(this)
                    });

                    if (record.data.type == "folder" && perspectiveCfg.inTreeContextMenu("asset.lockAndPropagate")) {
                        lockMenu.push({
                            text: t('lock_and_propagate_to_childs'),
                            iconCls: "pimcore_icon_lock pimcore_icon_overlay_go",
                            handler: function () {
                                pimcore.elementservice.lockElement({
                                    elementType: "asset",
                                    id: record.data.id,
                                    mode: "propagate"
                                });
                            }.bind(this)
                        });
                    }
                }

                if (record.data.locked && perspectiveCfg.inTreeContextMenu("asset.unlockAndPropagate")) {
                    // add unlock and propagate to children functionality
                    lockMenu.push({
                        text: t('unlock_and_propagate_to_children'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_delete",
                        handler: function () {
                            pimcore.elementservice.unlockElement({
                                elementType: "asset",
                                id: record.data.id
                            });
                        }.bind(this)
                    });
                }

                if (lockMenu.length > 0) {
                    advancedMenuItems.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock",
                        hideOnClick: false,
                        menu: lockMenu
                    });
                }
            }

            menu.add("-");

            if (advancedMenuItems.length) {
                menu.add({
                    text: t('advanced'),
                    iconCls: "pimcore_icon_more",
                    hideOnClick: false,
                    menu: advancedMenuItems
                });
            }

            if (record.data.type == "folder" && perspectiveCfg.inTreeContextMenu("asset.reload")) {
                menu.add(new Ext.menu.Item({
                    text: t('refresh'),
                    iconCls: "pimcore_icon_reload",
                    handler: pimcore.elementservice.refreshNode.bind(this, record)
                }));
            }
        }

        pimcore.helpers.hideRedundantSeparators(menu);
        pimcore.plugin.broker.fireEvent("prepareAssetTreeContextMenu", menu, this, record);

        menu.showAt(e.pageX+1, e.pageY+1);
    },


    copy: function (tree, record) {
        pimcore.cachedAssetId = record.id;
    },

    cut: function (tree, record) {
        pimcore.cutAsset = record;
        pimcore.cutAssetParentNode = record.parentNode;
    },

    pasteCutAsset: function(asset, oldParent, newParent, tree) {
        pimcore.elementservice.updateAsset(asset.id, {
            parentId: newParent.id
        }, function (asset, newParent, oldParent, tree, response) {
            try{
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.data.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    asset.data.basePath = newBasePath;
                    asset.data.path = asset.data.basePath + "/" + asset.data.text;
                }
                else {
                    this.tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                        "error",t(rdata.message));
                }
            } catch(e){
                this.tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
            }
            this.tree.loadMask.hide();
            pimcore.elementservice.refreshNodeAllTrees("asset", oldParent.id);
            pimcore.elementservice.refreshNodeAllTrees("asset", newParent.id);
            newParent.expand();
        }.bind(this, asset, newParent, oldParent, tree));

    },

    pasteInfo: function (tree, record, type) {
        pimcore.helpers.addTreeNodeLoadingIndicator("asset", record.id);

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_asset_copyinfo'),
            params: {
                targetId: record.id,
                sourceId: pimcore.cachedAssetId,
                type: type
            },
            success: this.paste.bind(this, tree, record)
        });
    },

    paste: function (tree, record, response) {

        try {
            var res = Ext.decode(response.responseText);

            if (res.pastejobs) {

                record.pasteProgressBar = new Ext.ProgressBar({
                    text: t('initializing')
                });

                record.pasteWindow = new Ext.Window({
                    title: t("paste"),
                    layout:'fit',
                    width:200,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    items: [record.pasteProgressBar],
                    listeners: pimcore.helpers.getProgressWindowListeners()
                });

                record.pasteWindow.show();

                var pj = new pimcore.tool.paralleljobs({
                    success: function () {

                        try {
                            this.pasteComplete(tree, record);
                        } catch(e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_item"), "error");
                            pimcore.elementservice.refreshNodeAllTrees("asset", record.parentNode.id);
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if(record.pasteProgressBar) {
                            var status = currentStep / steps;
                            record.pasteProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        this.pasteWindow.close();
                        record.pasteProgressBar = null;

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_item"), "error", t(message));
                        pimcore.elementservice.refreshNodeAllTrees("asset", record.parentNode.id);
                    }.bind(this),
                    jobs: res.pastejobs
                });
            } else {
                throw "There are no pasting jobs";
            }
        } catch (e) {
            console.log(e);
            Ext.MessageBox.alert(t('error'), e);
            this.pasteComplete(this, tree, record);
        }
    },

    pasteComplete: function (tree, record) {
        if(record.pasteWindow) {
            record.pasteWindow.close();
        }

        record.pasteProgressBar = null;
        record.pasteWindow = null;

        pimcore.elementservice.refreshNodeAllTrees("asset", record.id);
    },

    addFolder : function (tree, record) {
        Ext.MessageBox.prompt(t('create_folder'), t('enter_the_name_of_the_new_item'),
            this.addFolderCreate.bind(this, tree, record));
    },

    addFolderCreate: function (tree, record, button, value, object) {

        if (button == "ok") {

            // check for identical folder name in current level
            if (pimcore.elementservice.isKeyExistingInLevel(record, value)) {
                return;
            }

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_asset_addfolder'),
                method: "POST",
                params: {
                    parentId: record.data.id,
                    name: pimcore.helpers.getValidFilename(value, "asset")
                },
                success: this.addFolderComplete.bind(this, tree, record)
            });
        }
    },

    addFolderComplete: function (tree, record, response) {
        try{
            var rdata = Ext.decode(response.responseText);
            if (rdata && rdata.success) {
                record.data.leaf = false;
                //this.renderIndent();
                record.expand();
            }
            else {
                pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"),
                    "error",t(rdata.message));
            }
        } catch(e){
            pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
        }
        pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));
    },

    addSingleAsset: function (tree, record) {
        pimcore.helpers.assetSingleUploadDialog(record.data.id, "id", function (res) {
            var f = this.addAssetComplete.bind(this, tree, record);
            f();
        }.bind(this), function (res) {
            var response = Ext.decode(res.response.responseText);
            if(response.success === false) {
                pimcore.helpers.showNotification(t("error"), response.message, "error",
                    res.response.responseText);
            }
            var f = this.addAssetComplete.bind(this, tree, record);
            f();
        }.bind(this));
    },

    uploadZip: function (tree, record) {

        pimcore.helpers.uploadDialog(Routing.generate('pimcore_admin_asset_importzip', {parentId: record.id}), "Filedata", function (response) {
            // this.attributes.reference
            var res = Ext.decode(response.response.responseText);
            pimcore.helpers.addTreeNodeLoadingIndicator("asset", record.get("id"));

            this.downloadProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.downloadProgressWin = new Ext.Window({
                title: t("upload_zip"),
                layout:'fit',
                width:200,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                items: [this.downloadProgressBar],
                listeners: pimcore.helpers.getProgressWindowListeners()
            });

            this.downloadProgressWin.show();

            var pj = new pimcore.tool.paralleljobs({
                success: function (jobId) {
                    if(this.downloadProgressWin) {
                        this.downloadProgressWin.close();
                    }

                    this.downloadProgressBar = null;
                    this.downloadProgressWin = null;

                    pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));
                }.bind(this, res.jobId),
                update: function (currentStep, steps, percent) {
                    if(this.downloadProgressBar) {
                        var status = currentStep / steps;
                        this.downloadProgressBar.updateProgress(status, percent + "%");
                    }
                }.bind(this),
                failure: function (message) {
                    this.downloadProgressWin.close();
                    pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));
                    pimcore.helpers.showNotification(t("error"), t("error"),
                        "error", t(message));
                }.bind(this),
                jobs: res.jobs
            });
        }.bind(this), function (res) {
            var response = Ext.decode(res.response.responseText);
            if (response && response.success === false) {
                pimcore.helpers.showNotification(t("error"), response.message, "error",
                    res.response.responseText);
            } else {
                pimcore.helpers.showNotification(t("error"), res, "error",
                    res.response.responseText);
            }

            pimcore.elementservice.refreshNodeAllTrees("asset", record.parentNode.get("id"));
        }.bind(this));
    },

    enableHtml5Upload: function (node, rowIdx, out) {

        if (!window["FileList"]) {
            return;
        }

        // only for folders
        if (node.data.type != "folder") {
            return;
        }

        // timeout because there is no afterrender function
        window.setTimeout(this.addHtml5DragListener.bind(this, node), 2000);
    },

    addHtml5DragListener: function (node) {

        try {
            var tree = this.tree;
            var el = Ext.fly(tree.getView().getNodeByRecord(node));
            if(el) {
                el = el.dom;
                var fn = function (e) {
                    //e.stopPropagation();
                    e.preventDefault();
                    tree.setSelection(node);

                    e.dataTransfer.dropEffect = 'copy';

                    return false;
                };

                el.addEventListener("dragenter", fn, true);
                el.addEventListener("dragover", fn, true);
            }
        }
        catch (e) {
            console.log(e);
        }
    },

    importFromServer: function (tree, record) {

        var store = Ext.create('Ext.data.TreeStore', {
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_misc_fileexplorertree')
            },
            folderSort: true,
            sorters: [{
                property: 'text',
                direction: 'ASC'
            }]
        });

        this.treePanel = new Ext.tree.TreePanel({
            region: "west",
            width: 300,
            rootVisible: true,
            enableDD: false,
            autoScroll: true,
            store: store,
            root: {
                nodeType: 'async',
                text: t("document_root"),
                id: '/fileexplorer/',
                iconCls: "pimcore_icon_home",
                expanded: true,
                type: "folder"
            },
            listeners: {
                itemclick: function(tree, record, item, index, e, eOpts ) {
                    Ext.getCmp("pimcore_asset_server_import_button").setDisabled(record.data.type != "folder");
                }.bind(this)
            }
        });

        this.uploadWindow = new Ext.Window({
            layout: 'fit',
            title: t('add_assets'),
            closeAction: 'destroy',
            width:400,
            height:400,
            modal: true,
            items: [this.treePanel],
            buttons: [{
                text: t("import"),
                disabled: true,
                id: "pimcore_asset_server_import_button",
                handler: function (tree, record) {

                    try {
                        Ext.getCmp("pimcore_asset_server_import_button").disable();
                        var selModel =  this.treePanel.getSelectionModel();
                        var selectedNode = selModel.getSelected().getAt(0);
                        this.uploadWindow.removeAll();

                        this.uploadWindow.add({
                            xtype: "panel",
                            html: t("please_wait"),
                            bodyStyle: "padding:10px;"
                        });
                        this.uploadWindow.updateLayout();

                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_asset_importserver'),
                            method: 'POST',
                            params: {
                                parentId: record.id,
                                serverPath: selectedNode.id
                            },
                            success: function (tree, record, response) {
                                this.uploadWindow.close();
                                this.uploadWindow = null;

                                pimcore.helpers.addTreeNodeLoadingIndicator("asset", record.get("id"));

                                var res = Ext.decode(response.responseText);

                                this.downloadProgressBar = new Ext.ProgressBar({
                                    text: t('initializing')
                                });

                                this.downloadProgressWin = new Ext.Window({
                                    title: t("import_from_server"),
                                    layout:'fit',
                                    width:200,
                                    bodyStyle: "padding: 10px;",
                                    closable:false,
                                    plain: true,
                                    items: [this.downloadProgressBar],
                                    listeners: pimcore.helpers.getProgressWindowListeners()
                                });

                                this.downloadProgressWin.show();

                                var pj = new pimcore.tool.paralleljobs({
                                    success: function () {
                                        if(this.downloadProgressWin) {
                                            this.downloadProgressWin.close();
                                        }

                                        this.downloadProgressBar = null;
                                        this.downloadProgressWin = null;

                                        pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));
                                    }.bind(this),
                                    update: function (currentStep, steps, percent) {
                                        if(this.downloadProgressBar) {
                                            var status = currentStep / steps;
                                            this.downloadProgressBar.updateProgress(status, percent + "%");
                                        }
                                    }.bind(this),
                                    failure: function (message) {
                                        this.downloadProgressWin.close();
                                        pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));

                                        pimcore.helpers.showNotification(t("error"), t("error"),
                                            "error", t(message));
                                    }.bind(this),
                                    jobs: res.jobs
                                });
                            }.bind(this, tree, record)
                        });


                    } catch (e) {
                        console.log(e)
                    }
                }.bind(this, tree, record)
            }]
        });

        this.uploadWindow.show();
    },

    importFromUrl: function (tree, record) {

        Ext.MessageBox.prompt(t("import_from_url"), ' ', function (button, value, object) {
            if (button == "ok") {
                var win = new Ext.Window({
                    html: t("please_wait"),
                    closable: false,
                    bodyStyle: "padding: 10px;",
                    modal: true
                });
                win.show();

                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_asset_importurl'),
                    method: 'POST',
                    params: {
                        id: record.data.id,
                        url: value
                    },
                    success: function () {
                        win.close();
                        pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));

                    }.bind(this),
                    failure: function() {
                        win.close();
                        pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));
                    }
                });
            }
        }.bind(this), null, false, 'https://');
    },

    addAssetComplete: function (tree, record, config, file, response) {

        record.data.leaf = false;
        record.expand();
        pimcore.elementservice.refreshNodeAllTrees("asset", record.get("id"));
    },

    editAssetKey: function (tree, record) {
        var options = {
            sourceTree: tree,
            elementType: "asset",
            elementSubType: record.data.type,
            id: record.data.id,
            default: Ext.util.Format.htmlDecode(record.data.text)
        };
        pimcore.elementservice.editElementKey(options);
    },


    searchAndMove: function(tree, record) {
        pimcore.helpers.searchAndMove(record.data.id, function() {
            pimcore.elementservice.refreshNode(record);
        }.bind(this), "asset");
    },



    deleteAsset : function (ids) {
        var options = {
            "elementType" : "asset",
            "id": ids
        };

        pimcore.elementservice.deleteElement(options);
    }
});
