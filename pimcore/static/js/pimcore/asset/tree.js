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

pimcore.registerNS("pimcore.asset.tree");
pimcore.asset.tree = Class.create({

    treeDataUrl: "/admin/asset/tree-get-childs-by-id/",

    initialize: function(config) {

        if (!config) {
            this.config = {
                rootId: 1,
                rootVisible: true,
                loaderBaseParams: {},
                treeId: "pimcore_panel_tree_assets",
                treeIconCls: "pimcore_icon_asset",
                treeTitle: t('assets'),
                parentPanel: Ext.getCmp("pimcore_panel_tree_left"),
                index: 2
            };
        }
        else {
            this.config = config;
        }
        
        pimcore.layout.treepanelmanager.register(this.config.treeId);
        
        // get root node config
        Ext.Ajax.request({
            url: "/admin/asset/tree-get-root",
            params: {
                id: this.config.rootId
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

        rootNodeConfig.nodeType = "async";
        rootNodeConfig.text = "home";
        rootNodeConfig.draggable = true;
        rootNodeConfig.iconCls = "pimcore_icon_home";
        rootNodeConfig.listeners = this.getTreeNodeListeners();

        // assets
        this.tree = new Ext.tree.TreePanel({
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            useArrows:true,
            autoScroll:true,
            animate:true,
            enableDD:true,
            ddGroup: "element",
            containerScroll: true,
            ddAppendOnly: true,
            rootVisible: this.config.rootVisible,
            forceLayout: true,            
            border: false,
            tools: [{
                id: "right",
                handler: pimcore.layout.treepanelmanager.toRight.bind(this)
            },{
                id: "left",
                handler: pimcore.layout.treepanelmanager.toLeft.bind(this),
                hidden: true
            }],
            root: rootNodeConfig,
            plugins: new Ext.ux.tree.TreeNodeMouseoverPlugin(),
            loader: new Ext.ux.tree.PagingTreeLoader({
                dataUrl:this.treeDataUrl,
                pageSize:50,
                enableTextPaging:false,
                pagingModel:'remote',
                requestMethod: "GET",
                baseAttrs: {
                    listeners: this.getTreeNodeListeners(),
                    reference: this,
                    allowDrop: true,
                    allowChildren: true,
                    isTarget: true,
                    nodeType: "async"
                },
                baseParams: this.config.loaderBaseParams
            }),
            listeners: {
                "append": this.enableHtml5Upload.bind(this)
            }
        });

        new Ext.tree.TreeSorter(this.tree, {folderSort:true});
        
        this.tree.on("startdrag", this.onDragStart.bind(this));
        this.tree.on("enddrag", this.onDragEnd.bind(this));
        this.tree.on("render", function () {
            this.getRootNode().expand();
        });
        this.tree.on("nodedragover", this.onTreeNodeOver.bind(this));
        this.tree.on("afterrender", function () {
            this.tree.loadMask = new Ext.LoadMask(this.tree.getEl(), {msg: t("please_wait")});
            this.tree.loadMask.enable();

            // hadd listener to root node -> other nodes are added om the "append" event -> see this.enableHtml5Upload()
            this.addHtml5DragListener(this.tree.getRootNode());

            // html5 upload
            if (window["FileList"]) {
                this.tree.getEl().dom.addEventListener("drop", function (e) {

                    e.stopPropagation();
                    e.preventDefault();

                    try {
                        if(!this.tree.getSelectionModel().getSelectedNode()) {
                            return true;
                        }
                    }catch (e) {
                        return true;
                    }

                    var node = this.tree.getSelectionModel().getSelectedNode();

                    var dt = e.dataTransfer;
                    var files = dt.files;
                    var file;
                    this.activeUploads = 0;

                    if(files.length < 1) {
                        return;
                    }

                    var pbar = new Ext.ProgressBar({
                        id:'pbar3',
                        width:500
                    });
                    var win = new Ext.Window({
                        items: [pbar],
                        modal: true,
                        closable: false,
                        bodyStyle: "padding:10px;"
                    });
                    pbar.wait({
                        interval:2000,
                        duration:3600000,
                        increment:5
                    });
                    win.show();

                    for (var i=0; i<files.length; i++) {
                        file = files[i];

                        if (window.FileList && file.type && file.name) {

                            this.activeUploads++;

                            var reader = new FileReader();
                            reader.onload = function(file, node, win, e) {

                                var boundary = '------multipartformboundary' + (new Date).getTime();
                                var dashdash = '--';
                                var crlf     = '\r\n';

                                var builder = '';

                                builder += dashdash;
                                builder += boundary;
                                builder += crlf;

                                var xhr = new XMLHttpRequest();

                                builder += 'Content-Disposition: form-data; name="Filedata"';
                                if (file.name) {
                                    builder += '; filename="' + file.name + '"';
                                }
                                builder += crlf;

                                builder += 'Content-Type: ' + file.type;
                                builder += crlf;
                                builder += crlf;



                                builder += e.target.result;
                                builder += crlf;

                                builder += dashdash;
                                builder += boundary;
                                builder += crlf;

                                builder += dashdash;
                                builder += boundary;
                                builder += dashdash;
                                builder += crlf;

                                xhr.open("POST", "/admin/asset/add-asset/?pimcore_admin_sid=" + pimcore.settings.sessionId + "&parentId=" + node.id, true);
                                xhr.setRequestHeader('content-type', 'multipart/form-data; boundary='
                                    + boundary);
                                xhr.sendAsBinary(builder);

                                xhr.onload = function () {
                                    this.activeUploads--;
                                    if(this.activeUploads < 1) {
                                        win.close();
                                        node.reload();
                                    }
                                }.bind(this,node,win);

                            }.bind(this, file, node, win);

                            reader.readAsBinaryString(file);
                        }
                    }
                }.bind(this), true);
            }
        }.bind(this));

        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.doLayout();
        
    },
    
    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'click' : this.onTreeNodeClick,
            "contextmenu": this.onTreeNodeContextmenu,
            "move": this.onTreeNodeMove,
            "beforemove": this.onTreeNodeBeforeMove
        };

        return treeNodeListeners;
    },

    onDragStart : function () {
        pimcore.helpers.dndMaskFrames();
    },

    onDragEnd : function () {
        pimcore.helpers.dndUnmaskFrames();
    },

    onTreeNodeClick: function () {
        if(this.attributes.permissions.view) {
            pimcore.helpers.openAsset(this.id, this.attributes.type);
        }
    },

    onTreeNodeOver: function (event) {

        // check for permission
        try {
            if (event.data.node.attributes.permissions.settings) {
                return true;
            }
        }
        catch (e) {
        }

        return false;
    },

    onTreeNodeMove: function (tree, element, oldParent, newParent, index) {

        this.attributes.reference.updateAsset(this.id, {
            parentId: newParent.id
        }, function (newParent, oldParent, tree, response) {
            try{
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.attributes.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    this.attributes.basePath = newBasePath;
                    this.attributes.path = this.attributes.basePath + "/" + this.attributes.text;
                }
                else {
                    tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error",t(rdata.message));
                    oldParent.reload();
                    newParent.reload();
                }
            } catch(e){
                 tree.loadMask.hide();
                 pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                 oldParent.reload();
                 newParent.reload();
            }
            tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree));
    },

    onTreeNodeBeforeMove: function (tree, element, oldParent, newParent, index) {
        
        // check for locks
        if (element.attributes.locked) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

         // check new parent's permission
        if(!newParent.attributes.permissions.create){
            Ext.MessageBox.alert(t('missing_permission'), t('element_cannot_be_moved'));
            return false;
        }

        // check for permission
        if (element.attributes.permissions.settings) {
            tree.loadMask.show();
            return true;
        }
        return false;
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var menu = new Ext.menu.Menu();

        if (this.attributes.type == "folder") {
            if (this.attributes.permissions.create) {
                menu.add(new Ext.menu.Item({
                    text: t('add_assets'),
                    iconCls: "pimcore_icon_asset_add",
                    hideOnClick: false,
                    menu: [{
                        text: t("upload_files"),
                        handler: this.attributes.reference.addAssets.bind(this),
                        iconCls: "pimcore_icon_upload_multiple"
                    },{
                        text: t("upload_compatibility_mode"),
                        handler: this.attributes.reference.addSingleAsset.bind(this),
                        iconCls: "pimcore_icon_upload_single"
                    },{
                        text: t("upload_zip"),
                        handler: this.attributes.reference.uploadZip.bind(this),
                        iconCls: "pimcore_icon_upload_zip"
                    },{
                        text: t("import_from_server"),
                        handler: this.attributes.reference.importFromServer.bind(this),
                        iconCls: "pimcore_icon_import_server"
                    },{
                        text: t("import_from_url"),
                        handler: this.attributes.reference.importFromUrl.bind(this),
                        iconCls: "pimcore_icon_import_url"
                    }]
                }));

                menu.add(new Ext.menu.Item({
                    text: t('add_folder'),
                    iconCls: "pimcore_icon_folder_add",
                    handler: this.attributes.reference.addFolder.bind(this)
                }));

            }

            menu.add(new Ext.menu.Item({
                text: t('refresh'),
                iconCls: "pimcore_icon_reload",
                handler: this.attributes.reference.refresh.bind(this)
            }));
        }

        if (this.attributes.permissions.rename && this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('edit_filename'),
                iconCls: "pimcore_icon_edit_key",
                handler: this.attributes.reference.editAssetFilename.bind(this)
            }));
        }

        if (this.id != 1) {
            menu.add(new Ext.menu.Item({
                text: t('copy'),
                iconCls: "pimcore_icon_copy",
                handler: this.attributes.reference.copy.bind(this)
            }));
        }

        //cut
        if (this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('cut'),
                iconCls: "pimcore_icon_cut",
                handler: this.attributes.reference.cut.bind(this)
            }));
        }


        //paste
        if (this.attributes.reference.cacheDocumentId && (this.attributes.permissions.create || this.attributes.permissions.publish)) {
            var pasteMenu = [];

            if (this.attributes.type == "folder") {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "recursive")
                }));
            }
            else {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "replace")
                }));
            }
        }

        if (this.attributes.type == "folder" && this.attributes.reference.cutAsset && (this.attributes.permissions.create || this.attributes.permissions.publish)) {
            menu.add(new Ext.menu.Item({
                text: t('paste_cut_element'),
                iconCls: "pimcore_icon_paste",
                handler: function() {
                    this.attributes.reference.pasteCutAsset(this.attributes.reference.cutAsset, this.attributes.reference.cutParentNode, this, this.attributes.reference.tree);
                    this.attributes.reference.cutParentNode = null;
                    this.attributes.reference.cutAsset = null;
                }.bind(this)
            }));
        }

        if (this.attributes.permissions.remove && this.attributes.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.attributes.reference.deleteAsset.bind(this)
            }));
        }
        
        if (this.id != 1) {
            var user = pimcore.globalmanager.get("user");
            if(user.admin) { // only admins are allowed to change locks in frontend
                
                var lockMenu = [];
                if(this.attributes.lockOwner) { // add unlock
                    lockMenu.push({
                        text: t('unlock'),
                        iconCls: "pimcore_icon_lock_delete",
                        handler: function () {
                            this.attributes.reference.updateAsset(this.attributes.id, {locked: null}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
                        }.bind(this)
                    });
                } else {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock_add",
                        handler: function () {
                            this.attributes.reference.updateAsset(this.attributes.id, {locked: "self"}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
                        }.bind(this)
                    });
                    
                    if(this.attributes.type == "folder") {
                        lockMenu.push({
                            text: t('lock_and_propagate_to_childs'),
                            iconCls: "pimcore_icon_lock_add_propagate",
                            handler: function () {
                                this.attributes.reference.updateAsset(this.attributes.id, {locked: "propagate"}, function () {
                                    this.attributes.reference.tree.getRootNode().reload();
                                }.bind(this))
                            }.bind(this)
                        });
                    }
                }
                
                menu.add(new Ext.menu.Item({
                    text: t('lock'),
                    iconCls: "pimcore_icon_lock",
                    hideOnClick: false,
                    menu:lockMenu
                }));
            }
        }

        menu.show(this.ui.getAnchor());
    },


    copy: function () {
        this.attributes.reference.cacheDocumentId = this.id;
    },

    cut: function () {
        this.attributes.reference.cutAsset = this;
        this.attributes.reference.cutParentNode = this.parentNode;
    },

    pasteCutAsset: function(asset, oldParent, newParent, tree) {
        asset.attributes.reference.updateAsset(asset.id, {
            parentId: newParent.id
        }, function (newParent, oldParent, tree, response) {
            try{
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.attributes.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    this.attributes.basePath = newBasePath;
                    this.attributes.path = this.attributes.basePath + "/" + this.attributes.text;
                }
                else {
                    tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error",t(rdata.message));
                }
            } catch(e){
                 tree.loadMask.hide();
                 pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
            }
            tree.loadMask.hide();
             oldParent.reload();
             newParent.reload();
        }.bind(asset, newParent, oldParent, tree));

    },

    pasteInfo: function (type) {
        //this.attributes.reference.tree.loadMask.show();

        pimcore.helpers.addTreeNodeLoadingIndicator("asset", this.id);

        Ext.Ajax.request({
            url: "/admin/asset/copy-info/",
            params: {
                targetId: this.id,
                sourceId: this.attributes.reference.cacheDocumentId,
                type: type
            },
            success: this.attributes.reference.paste.bind(this)
        });
    },

    paste: function (response) {

        try {
            var res = Ext.decode(response.responseText);

            if (res.pastejobs) {

                this.pasteProgressBar = new Ext.ProgressBar({
                    text: t('initializing')
                });

                this.pasteWindow = new Ext.Window({
                    title: t("paste"),
                    layout:'fit',
                    width:500,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    modal: true,
                    items: [this.pasteProgressBar]
                });

                this.pasteWindow.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function () {

                        try {
                            this.attributes.reference.pasteComplete(this);
                        } catch(e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_asset"), "error");
                            this.parentNode.reload();
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if(this.pasteProgressBar) {
                            var status = currentStep / steps;
                            this.pasteProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        this.pasteWindow.close();
                        this.pasteProgressBar = null;

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_asset"), "error", t(message));
                        this.parentNode.reload();
                    }.bind(this),
                    jobs: res.pastejobs
                });
            } else {
                throw "There are no pasting jobs";
            }
        } catch (e) {
            console.log(e);
            Ext.MessageBox.alert(t('error'), e);
            this.attributes.reference.pasteComplete(this);
        }
    },

    pasteComplete: function (node) {
        if(node.pasteWindow) {
            node.pasteWindow.close();
        }

        node.pasteProgressBar = null;
        node.pasteWindow = null;

        //this.tree.loadMask.hide();
        pimcore.helpers.removeTreeNodeLoadingIndicator("asset", node.id);
        node.reload();
    },



    refresh: function () {
        this.reload();
    },

    addFolder : function () {
        Ext.MessageBox.prompt(t('add_folder'), t('please_enter_the_name_of_the_folder'), this.attributes.reference.addFolderCreate.bind(this));
    },

    addFolderCreate: function (button, value, object) {

        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/asset/add-folder/",
                params: {
                    parentId: this.id,
                    name: pimcore.helpers.getValidFilename(value)
                },
                success: this.attributes.reference.addFolderComplete.bind(this)
            });
        }
    },

    addFolderComplete: function (response) {
        try{
            var rdata = Ext.decode(response.responseText);
            if (rdata && rdata.success) {
                this.leaf = false;
                this.renderIndent();
                this.expand();
            }
            else {
                pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_creating_a_folder"), "error",t(rdata.message));
            }
        } catch(e){
            pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_creating_a_folder"), "error");
        }
        this.reload();
    },

    addSingleAsset: function () {
        pimcore.helpers.assetSingleUploadDialog(this.attributes.id, "id", function (res) {
            var f = this.attributes.reference.addAssetComplete.bind(this);
            f();
        }.bind(this), function (res) {
            var f = this.attributes.reference.addAssetComplete.bind(this);
            f();
        }.bind(this));
    },

    addAssets : function () {

        this.uploadWindow = new Ext.Window({
            layout: 'fit',
            title: t('add_assets'),
            closeAction: 'close',
            width:400,
            height:400,
            modal: true
        });

        var uploadPanel = new Ext.ux.SwfUploadPanel({
            title: t('upload_your_files_press_shift_to_select_multiple_files'),
            border: false,
            upload_url: '/admin/asset/add-asset/?pimcore_admin_sid=' + pimcore.settings.sessionId,
            post_params: { parentId: this.id },
            debug: pimcore.settings.devmode,
            file_size_limit: (pimcore.settings.upload_max_filesize/1000),
            flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
            confirm_delete: false,
            remove_completed: true,
            listeners: {
                "fileUploadComplete": function (win) {
                    win.hide();

                    var f = this.attributes.reference.addAssetComplete.bind(this);
                    f();
                }.bind(this, this.uploadWindow)
            }
        });

        this.uploadWindow.add(uploadPanel);
        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();
    },

    uploadZip: function () {

        this.uploadWindow = new Ext.Window({
            layout: 'fit',
            title: t('add_assets'),
            closeAction: 'close',
            width:400,
            height:140,
            modal: true
        });

        var uploadPanel = new Ext.ux.SwfUploadPanel({
            border: false,
            upload_url: '/admin/asset/import-zip/?pimcore_admin_sid=' + pimcore.settings.sessionId,
            post_params: { parentId: this.id },
            debug: false,
            file_size_limit: (pimcore.settings.upload_max_filesize/1000),
            flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
            confirm_delete: false,
            remove_completed: true,
            file_queue_limit: 1,
            single_file_select: true,
            file_types: "*.zip",
            listeners: {
                "fileUploadComplete": function (win) {
                    win.hide();

                    var f = this.attributes.reference.addAssetComplete.bind(this);
                    f();
                }.bind(this, this.uploadWindow)
            }
        });

        this.uploadWindow.add(uploadPanel);
        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();
    },
    
    enableHtml5Upload: function (tree, parent, node, index) {

        if (!window["FileList"]) {
            return;
        }
        
        // only for folders
        if(node.attributes.type != "folder") {
            return;
        }
        
        // timeout because there is no afterrender function
        window.setTimeout(this.addHtml5DragListener.bind(this, node),2000)
    },

    addHtml5DragListener: function (node) {

        var el = Ext.get(node.getUI().getEl()).dom;
        try {
            el.addEventListener("dragover", function (e) {
                //e.stopPropagation();
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';

                node.select();

                return false;
            }.bind(node),true);
        }
        catch (e) {
            console.log(e);
        }
    },

    importFromServer: function () {

        this.treePanel = new Ext.tree.TreePanel({
            region: "west",
            id: "pimcore_asset_server_explorer",
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
                    Ext.getCmp("pimcore_asset_server_import_button").disable();
                    if(n.attributes.type == "folder") {
                        Ext.getCmp("pimcore_asset_server_import_button").enable();
                    }
                }.bind(this)
            }
        });

        new Ext.tree.TreeSorter(this.treePanel, {folderSort:true});

        this.uploadWindow = new Ext.Window({
            layout: 'fit',
            title: t('add_assets'),
            closeAction: 'close',
            width:400,
            height:400,
            modal: true,
            items: [this.treePanel],
            buttons: [{
                text: t("import"),
                disabled: true,
                id: "pimcore_asset_server_import_button",
                handler: function () {

                    try {
                        Ext.getCmp("pimcore_asset_server_import_button").disable();
                        var selectedNode = this.treePanel.getSelectionModel().getSelectedNode();
                        this.uploadWindow.removeAll();

                        this.uploadWindow.add({
                            xtype: "panel",
                            html: t("please_wait"),
                            bodyStyle: "padding:10px;"
                        });
                        this.uploadWindow.doLayout();

                        Ext.Ajax.request({
                            url: "/admin/asset/import-server",
                            params: {
                                parentId: this.id,
                                serverPath: selectedNode.id
                            },
                            success: function () {
                                this.uploadWindow.hide();
                                var f = this.attributes.reference.addAssetComplete.bind(this);
                                f();
                            }.bind(this)
                        });
                    } catch (e) { }
                }.bind(this)
            }]
        });

        this.uploadWindow.show();
    },

    importFromUrl: function () {

        Ext.MessageBox.prompt(t("import_from_url"), t("url_incl_http"), function (button, value, object) {
            if (button == "ok") {
                var win = new Ext.Window({
                    html: t("please_wait"),
                    closable: false,
                    bodyStyle: "padding: 10px;",
                    modal: true
                });
                win.show();

                Ext.Ajax.request({
                    url: "/admin/asset/import-url/",
                    method: "get",
                    params: {
                        id: this.attributes.id,
                        url: value
                    },
                    success: function () {
                        win.close();
                        this.reload();
                    }.bind(this)
                });
            }
        }.bind(this));
    },

    addAssetComplete: function (config, file, response) {

        this.leaf = false;
        this.renderIndent();
        this.expand();

        this.reload();
    },

    editAssetFilename: function () {
        Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'), this.attributes.reference.editAssetFilenameComplete.bind(this), null, null, this.text);
    },

    editAssetFilenameComplete: function (button, value, object) {
        if (button == "ok") {

            // check for ident filename in current level
            var parentChilds = this.parentNode.childNodes;
            for (var i = 0; i < parentChilds.length; i++) {
                if (parentChilds[i].text == value && this != parentChilds[i].text) {
                    Ext.MessageBox.alert(t('rename'), t('the_filename_is_already_in_use'));
                    return;
                }
            }

            value = pimcore.helpers.getValidFilename(value);

            this.setText(value);
            this.attributes.path = this.attributes.basePath + value;
            
            this.getOwnerTree().loadMask.show();
            
            this.attributes.reference.updateAsset(this.id, {filename: value}, function (response) {
                
                this.getOwnerTree().loadMask.hide();
                this.reload();
                                
                if (pimcore.globalmanager.exists("asset_" + this.id)) {
                    try{
                        var rdata = Ext.decode(response.responseText);

                        if (rdata && rdata.success) {
                            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                            var tabId = "asset_" + this.id;
                            tabPanel.remove(tabId);
                            pimcore.globalmanager.remove("asset_" + this.id);

                            pimcore.helpers.openAsset(this.id, this.attributes.type);
                        }
                        else {
                            pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_renaming_a_folder"), "error",t(rdata.message));
                        }
                    } catch (e){
                        pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_renaming_a_folder"), "error");   
                    }
                }
            }.bind(this));
        }
    },

    updateAsset: function (id, data, callback) {

        if (!callback) {
            callback = function() {
            };
        }

        data.id = id;
        
        Ext.Ajax.request({
            url: "/admin/asset/update/",
            method: "post",
            params: data,
            success: callback
        });
    },


    deleteAsset : function () {
        pimcore.helpers.deleteAsset(this.id);
    }
});