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

pimcore.registerNS("pimcore.asset.tree");
pimcore.asset.tree = Class.create({

    treeDataUrl: "/admin/asset/tree-get-childs-by-id/",

    initialize: function(config) {

        this.position = "left";

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

        var itemsPerPage = 30;

        rootNodeConfig.text = t("home");
        rootNodeConfig.allowDrag = true;
        rootNodeConfig.id = "" +  rootNodeConfig.id;
        rootNodeConfig.iconCls = "pimcore_icon_home";
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
                    limit: itemsPerPage
                }
            },
            pageSize: itemsPerPage,
            root: rootNodeConfig
        });

        // assets
        this.tree = Ext.create('pimcore.tree.Panel', {
            store: store,
            autoLoad: false,
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            autoScroll:true,
            animate:true,
            containerScroll: true,
            ddAppendOnly: true,
            rootVisible: this.config.rootVisible,
            forceLayout: true,
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
                handler: pimcore.layout.treepanelmanager.toRight.bind(this)
            },{
                type: "left",
                handler: pimcore.layout.treepanelmanager.toLeft.bind(this),
                hidden: true
            }],
            root: rootNodeConfig,
            //TODO removed
            // plugins: new Ext.ux.tree.TreeNodeMouseoverPlugin(),
            //loader: new Ext.ux.tree.PagingTreeLoader({
            //    dataUrl:this.treeDataUrl,
            //    pageSize:50,
            //    enableTextPaging:false,
            //    pagingModel:'remote',
            //    requestMethod: "GET",
            //    baseAttrs: {
            //        listeners: this.getTreeNodeListeners(),
            //        reference: this,
            //        allowDrop: true,
            //        allowChildren: true,
            //        isTarget: true,
            //        nodeType: "async"
            //    },
            //    baseParams: this.config.loaderBaseParams
            //}),
            listeners: this.getTreeNodeListeners()
        });

        //TODO
        this.tree.on("append",this.enableHtml5Upload.bind(this));
        this.tree.on("startdrag", this.onDragStart.bind(this));
        this.tree.on("enddrag", this.onDragEnd.bind(this));
        this.tree.on("render", function () {
            this.getRootNode().expand();
        });
        this.tree.on("afterrender", function () {
            try {
                this.tree.loadMask = new Ext.LoadMask(
                    {
                        target: this.tree,
                        msg: t("please_wait"),
                        hidden: true
                    });

                // hadd listener to root node -> other nodes are added om the "append" event -> see this.enableHtml5Upload()
                this.addHtml5DragListener(this.tree.getRootNode());

                // html5 upload
                if (window["FileList"]) {
                    this.tree.getEl().dom.addEventListener("drop", function (e) {

                        e.stopPropagation();
                        e.preventDefault();

                        pimcore.helpers.treeNodeThumbnailPreviewHide();

                        try {
                            if (!this.tree.getSelectionModel().getSelectedNode()) {
                                return true;
                            }
                        } catch (e2) {
                            return true;
                        }

                        var node = this.tree.getSelectionModel().getSelectedNode();

                        var dt = e.dataTransfer;

                        var files = dt.files;

                        // if a folder is dropped (currently only Chrome) pass the dataTransfer object instead of the FileList object
                        if (dt["items"]) {
                            files = dt;
                        }

                        this.uploadFileList(files, node);

                    }.bind(this), true);
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));

        this.tree.on("itemappend", pimcore.helpers.treeNodeThumbnailPreview.bind(this));

        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.updateLayout();

    },

    uploadFileList: function (files, parentNode) {

        var file;
        this.activeUploads = 0;

        if(files.length < 1) {
            return;
        }

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

            var finishedErrorHandler = function () {
                // success
                this.activeUploads--;

                win.remove(pbar);

                if(this.activeUploads < 1) {
                    win.close();
                    this.refresh(parentNode);
                }
            }.bind(this);

            pimcore.helpers.uploadAssetFromFileObject(file,
                "/admin/asset/add-asset/?pimcore_admin_sid="
                + pimcore.settings.sessionId + "&parentId=" + parentNode.id + "&dir=" + path,
                finishedErrorHandler,
                function (evt) {
                    //progress
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        var progressText = file.name + " ( " + Math.floor(percentComplete*100) + "% )";
                        if(percentComplete == 1) {
                            progressText = file.name + " " + t("converting") + "... ";
                        }

                        pbar.updateProgress(percentComplete, progressText);
                    }
                },
                finishedErrorHandler
            );
        }.bind(this);



        // this is for browser that support folders (currently: Chrome)
        // in this case not a FileList object is given but a dataTransfer object (from DnD Event)
        if(files["items"]) {
            var traverseFileTree = function (item, path) {
                path = path || "";
                if (item.isFile) {
                    // Get file
                    item.file(function(file) {
                        doFileUpload(file, path);
                    }.bind(this));
                } else if (item.isDirectory) {
                    // Get folder contents
                    var dirReader = item.createReader();
                    dirReader.readEntries(function(entries) {
                        for (var i=0; i<entries.length; i++) {
                            traverseFileTree(entries[i], path + item.name + "/");
                        }
                    });
                }
            }.bind(this);

            for (var i=0; i<files.items.length; i++) {
                // webkitGetAsEntry is where the magic happens
                var item = files.items[i].webkitGetAsEntry();
                if (item) {
                    traverseFileTree(item);
                }
            }
        } else {
            // default filelist upload
            for (var i=0; i<files.length; i++) {
                file = files[i];

                if (window.FileList && file.name && file.type) { // check for type (folder has no type)
                    doFileUpload(file);
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
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                //TODO temporary, until changed on server side
                if (newChildNode.data.qtipCfg) {
                    if (newChildNode.data.qtipCfg.title) {
                        newChildNode.data.qtitle = newChildNode.data.qtipCfg.title;
                    }
                    if (newChildNode.data.qtipCfg.text) {
                        newChildNode.data.qtip = newChildNode.data.qtipCfg.text;
                    } else {
                        newChildNode.data.qtip = t("type") + ": "+ t(newChildNode.data.type);
                    }
                }
            }
        };

        return treeNodeListeners;
    },

    onDragStart : function () {
        pimcore.helpers.treeNodeThumbnailPreviewHide();
    },

    onDragEnd : function () {
        // nothing to do
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        if (record.data.expandable && !record.data.expanded) {
            record.expand();
        }
        if (record.data.permissions.view) {
            pimcore.helpers.treeNodeThumbnailPreviewHide();
            pimcore.helpers.openAsset(record.data.id, record.data.type);
        }
    },


    onTreeNodeOver: function (targetNode, position, dragData, e, eOpts ) {
        var node = dragData.records[0];
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

        this.updateAsset(node.data.id, {
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
                }
                else {
                    this.tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                        "error",t(rdata.message));
                    this.refresh(oldParent);
                    this.refresh(newParent);
                }
            } catch(e){
                this.tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                this.refresh(oldParent);
                this.refresh(newParent);
            }
            this.tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree));
    },

    onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts ) {
        // check for locks
        if (node.data.locked) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

        // check new parent's permission
        if(!newParent.data.permissions.create){
            Ext.MessageBox.alert(t('missing_permission'), t('element_cannot_be_moved'));
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

        tree.select();

        var menu = new Ext.menu.Menu();

        if (record.data.type == "folder") {
            if (record.data.permissions.create) {
                menu.add(new Ext.menu.Item({
                    text: t('add_assets'),
                    iconCls: "pimcore_icon_asset_add",
                    hideOnClick: false,
                    menu: [{
                        text: t("upload_files"),
                        handler: this.addAssets.bind(this, tree, record),
                        iconCls: "pimcore_icon_upload_multiple"
                    },{
                        text: t("upload_compatibility_mode"),
                        handler: this.addSingleAsset.bind(this, tree, record),
                        iconCls: "pimcore_icon_upload_single"
                    },{
                        text: t("upload_zip"),
                        handler: this.uploadZip.bind(this, tree, record),
                        iconCls: "pimcore_icon_upload_zip"
                    },{
                        text: t("import_from_server"),
                        handler: this.importFromServer.bind(this, tree, record),
                        iconCls: "pimcore_icon_import_server"
                    },{
                        text: t("import_from_url"),
                        handler: this.importFromUrl.bind(this, tree, record),
                        iconCls: "pimcore_icon_import_url"
                    }]
                }));

                menu.add(new Ext.menu.Item({
                    text: t('add_folder'),
                    iconCls: "pimcore_icon_folder_add",
                    handler: this.addFolder.bind(this, tree, record)
                }));

            }
        }

        if (record.data.permissions.rename && this.id != 1 && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('edit_filename'),
                iconCls: "pimcore_icon_edit_key",
                handler: this.editAssetFilename.bind(this, tree, record)
            }));
        }

        if (this.id != 1 && record.data.permissions.view) {
            menu.add(new Ext.menu.Item({
                text: t('copy'),
                iconCls: "pimcore_icon_copy",
                handler: this.copy.bind(this, tree, record)
            }));
        }

        //cut
        if (record.data.id != 1 && !record.data.locked && record.data.permissions.rename) {
            menu.add(new Ext.menu.Item({
                text: t('cut'),
                iconCls: "pimcore_icon_cut",
                handler: this.cut.bind(this, tree, record)
            }));
        }


        //paste
        if (this.cacheDocumentId
            && (record.data.permissions.create ||record.data.permissions.publish)) {
            var pasteMenu = [];

            if (record.data.type == "folder") {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "recursive")
                }));
            }
            else {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "replace")
                }));
            }
        }

        if (record.data.type == "folder" && this.cutAsset
            && (record.data.permissions.create || record.data.permissions.publish)) {
            menu.add(new Ext.menu.Item({
                text: t('paste_cut_element'),
                iconCls: "pimcore_icon_paste",
                handler: function() {
                    this.pasteCutAsset(this.cutAsset,
                        this.cutParentNode, record, this.tree);
                    this.cutParentNode = null;
                    this.cutAsset = null;
                }.bind(this)
            }));
        }

        if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.deleteAsset.bind(this, tree, record)
            }));
        }

        if (record.data.permissions.create && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('search_and_move'),
                iconCls: "pimcore_icon_search_and_move",
                handler: this.searchAndMove.bind(this, tree, record)
            }));
        }

        if (record.data.id != 1) {
            var user = pimcore.globalmanager.get("user");
            if(user.admin) { // only admins are allowed to change locks in frontend

                var lockMenu = [];
                if(record.data.lockOwner) { // add unlock
                    lockMenu.push({
                        text: t('unlock'),
                        iconCls: "pimcore_icon_lock_delete",
                        handler: function () {
                            this.updateAsset(record.data.id, {locked: null}, function () {
                                this.refresh(this.tree.getRootNode());
                            }.bind(this));
                        }.bind(this)
                    });
                } else {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock_add",
                        handler: function () {
                            this.updateAsset(record.data.id, {locked: "self"}, function () {
                                this.refresh(this.tree.getRootNode());
                            }.bind(this));
                        }.bind(this)
                    });

                    if(record.data.type == "folder") {
                        lockMenu.push({
                            text: t('lock_and_propagate_to_childs'),
                            iconCls: "pimcore_icon_lock_add_propagate",
                            handler: function () {
                                this.updateAsset(tree, record, {locked: "propagate"},
                                    function () {
                                        this.refresh(this.tree.getRootNode());
                                    }.bind(this));
                            }.bind(this)
                        });
                    }
                }

                if(record.data.locked) {
                    // add unlock and propagate to children functionality
                    lockMenu.push({
                        text: t('unlock_and_propagate_to_children'),
                        iconCls: "pimcore_icon_lock_delete",
                        handler: function () {
                            Ext.Ajax.request({
                                url: "/admin/element/unlock-propagate",
                                params: {
                                    id: record.data.id,
                                    type: "asset"
                                },
                                success: function () {
                                    this.refresh(this.tree.getRootNode());
                                }.bind(this)
                            });
                        }.bind(this)
                    });
                }

                menu.add(new Ext.menu.Item({
                    text: t('lock'),
                    iconCls: "pimcore_icon_lock",
                    hideOnClick: false,
                    menu:lockMenu
                }));
            }
        }

        if (record.data.type == "folder") {
            menu.add(new Ext.menu.Item({
                text: t('refresh'),
                iconCls: "pimcore_icon_reload",
                handler: this.refresh.bind(this, record)
            }));
        }

        menu.showAt(e.pageX, e.pageY);
    },


    copy: function (tree, record) {
        this.cacheDocumentId = record.id;
    },

    cut: function (tree, record) {
        this.cutAsset = record;
        this.cutParentNode = record.parentNode;
    },

    pasteCutAsset: function(asset, oldParent, newParent, tree) {
        this.updateAsset(asset.id, {
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
            this.refresh(oldParent);
            this.refresh(newParent);
        }.bind(this, asset, newParent, oldParent, tree));

    },

    pasteInfo: function (tree, record, type) {
        //this.attributes.reference.tree.loadMask.show();

        pimcore.helpers.addTreeNodeLoadingIndicator("asset", record.id);

        Ext.Ajax.request({
            url: "/admin/asset/copy-info/",
            params: {
                targetId: record.id,
                sourceId: this.cacheDocumentId,
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
                    width:500,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    modal: true,
                    items: [record.pasteProgressBar]
                });

                record.pasteWindow.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function () {

                        try {
                            this.pasteComplete(tree, record);
                        } catch(e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_asset"), "error");
                            this.refresh(record.parentNode);
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
                        this.refresh(record.parentNode);
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

        //this.tree.loadMask.hide();
        pimcore.helpers.removeTreeNodeLoadingIndicator("asset", record.id);
        this.refresh(record);
    },



    refresh: function (record) {
        var ownerTree = record.getOwnerTree();

        record.data.expanded = true;
        ownerTree.getStore().load({
            node: record
        });
    },

    addFolder : function (tree, record) {
        Ext.MessageBox.prompt(t('add_folder'), t('please_enter_the_name_of_the_folder'),
            this.addFolderCreate.bind(this, tree, record));
    },

    addFolderCreate: function (tree, record, button, value, object) {

        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/asset/add-folder/",
                params: {
                    parentId: record.data.id,
                    name: pimcore.helpers.getValidFilename(value)
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
                pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_creating_a_folder"),
                    "error",t(rdata.message));
            }
        } catch(e){
            pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_creating_a_folder"), "error");
        }
        this.refresh(record);
    },

    addSingleAsset: function (tree, record) {
        pimcore.helpers.assetSingleUploadDialog(record.data.id, "id", function (res) {
            var f = this.addAssetComplete.bind(this, tree, record);
            f();
        }.bind(this), function (res) {
            var f = this.addAssetComplete.bind(this, tree, record);
            f();
        }.bind(this));
    },

    addAssets : function (tree, record) {
        // check if multiupload fields exists in dom
        if(!Ext.get("multiUploadField")) {
            // we have to do the following in jQuery :(
            jQuery("body").append('<input type="file" name="multiUploadField" id="multiUploadField" multiple>');
        }

        // this is the tree node
        jQuery("#multiUploadField").unbind("change");
        jQuery("#multiUploadField").on("change", function (e) {
            if(e.target.files.length) {
                this.uploadFileList(e.target.files, record);
            }
        }.bind(this));

        jQuery("#multiUploadField").trigger("click");
    },

    uploadZip: function (tree, record) {
        pimcore.helpers.uploadDialog("/admin/asset/import-zip?parentId=" + record.id, "Filedata", function (response) {
            // this.attributes.reference
            var res = Ext.decode(response.response.responseText);

            this.downloadProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.downloadProgressWin = new Ext.Window({
                title: t("upload_zip"),
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.downloadProgressBar]
            });

            this.downloadProgressWin.show();

            var pj = new pimcore.tool.paralleljobs({
                success: function (jobId) {
                    if(this.downloadProgressWin) {
                        this.downloadProgressWin.close();
                    }

                    this.downloadProgressBar = null;
                    this.downloadProgressWin = null;

                    this.refresh(record);
                }.bind(this, res.jobId),
                update: function (currentStep, steps, percent) {
                    if(this.downloadProgressBar) {
                        var status = currentStep / steps;
                        this.downloadProgressBar.updateProgress(status, percent + "%");
                    }
                }.bind(this),
                failure: function (message) {
                    this.downloadProgressWin.close();
                    pimcore.helpers.showNotification(t("error"), t("error"),
                        "error", t(message));
                }.bind(this),
                jobs: res.jobs
            });
        }.bind(this), function (res) {
            // failed
            this.refresh(record.parentNode);
            console.log("failed");
        }.bind(this));
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
        window.setTimeout(this.addHtml5DragListener.bind(this, node),2000);
    },

    addHtml5DragListener: function (node) {
        //TODO EXTJS6
        //
        //
        //try {
        //    var el = Ext.get(node.getUI().getEl()).dom;
        //    var fn = function (e) {
        //        //e.stopPropagation();
        //        e.preventDefault();
        //        node.select();
        //
        //        e.dataTransfer.dropEffect = 'copy';
        //
        //        return false;
        //    };
        //
        //    el.addEventListener("dragenter", fn, true);
        //    el.addEventListener("dragover", fn,true);
        //}
        //catch (e) {
        //    console.log(e);
        //}
    },

    importFromServer: function (tree, record) {

        var store = Ext.create('Ext.data.TreeStore', {
            proxy: {
                type: 'ajax',
                url: "/admin/misc/fileexplorer-tree"
            },
            folderSort: true,
            sorters: [{
                property: 'text',
                direction: 'ASC'
            }]
        });

        this.treePanel = new Ext.tree.TreePanel({
            region: "west",
            id: "pimcore_asset_server_explorer",
            width: 300,
            rootVisible: true,
            enableDD: false,
            useArrows: true,
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
            closeAction: 'close',
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
                            url: "/admin/asset/import-server",
                            params: {
                                parentId: record.id,
                                serverPath: selectedNode.id
                            },
                            success: function (tree, record, response) {
                                this.uploadWindow.close();
                                this.uploadWindow = null;

                                var res = Ext.decode(response.responseText);

                                this.downloadProgressBar = new Ext.ProgressBar({
                                    text: t('initializing')
                                });

                                this.downloadProgressWin = new Ext.Window({
                                    title: t("import_from_server"),
                                    layout:'fit',
                                    width:500,
                                    bodyStyle: "padding: 10px;",
                                    closable:false,
                                    plain: true,
                                    modal: true,
                                    items: [this.downloadProgressBar]
                                });

                                this.downloadProgressWin.show();

                                var pj = new pimcore.tool.paralleljobs({
                                    success: function () {
                                        if(this.downloadProgressWin) {
                                            this.downloadProgressWin.close();
                                        }

                                        this.downloadProgressBar = null;
                                        this.downloadProgressWin = null;

                                        this.refresh(record);
                                    }.bind(this),
                                    update: function (currentStep, steps, percent) {
                                        if(this.downloadProgressBar) {
                                            var status = currentStep / steps;
                                            this.downloadProgressBar.updateProgress(status, percent + "%");
                                        }
                                    }.bind(this),
                                    failure: function (message) {
                                        this.downloadProgressWin.close();
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
                    params: {
                        id: record.data.id,
                        url: value
                    },
                    success: function () {
                        win.close();
                        this.refresh(record);

                    }.bind(this),
                    failure: function() {
                        win.close();
                        this.refresh(record);
                    }
                });
            }
        }.bind(this));
    },

    addAssetComplete: function (tree, record, config, file, response) {

        this.leaf = false;
        //this.renderIndent();
        record.expand();
        this.refresh(record);
    },

    editAssetFilename: function (tree, record) {
        Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'),
            this.editAssetFilenameComplete.bind(this, tree, record), window, false, record.data.text);
    },

    editAssetFilenameComplete: function (tree, record, button, value, object) {
        try {
            if (button == "ok") {

                // check for ident filename in current level
                var parentChilds = record.parentNode.childNodes;
                for (var i = 0; i < parentChilds.length; i++) {
                    if (parentChilds[i].data.text == value && this != parentChilds[i].data.text) {
                        Ext.MessageBox.alert(t('rename'), t('the_filename_is_already_in_use'));
                        return;
                    }
                }

                value = pimcore.helpers.getValidFilename(value);

                record.set("text", value);
                record.set("path", record.data.basePath + value);

                record.getOwnerTree().loadMask.enable();

                this.updateAsset(record.data.id, {filename: value}, function (response) {

                    //record.getOwnerTree().loadMask.disable();
                    this.refresh(record);

                    if (pimcore.globalmanager.exists("asset_" + record.data.id)) {
                        try {
                            var rdata = Ext.decode(response.responseText);

                            if (rdata && rdata.success) {
                                pimcore.helpers.closeAsset(record.data.id);
                                pimcore.helpers.openAsset(record.data.id, record.data.type);
                            }
                            else {
                                pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_renaming_a_folder"),
                                    "error", t(rdata.message));
                            }
                        } catch (e) {
                            pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_renaming_a_folder"),
                                "error");
                        }
                    }
                }.bind(this));
            }
        } catch (e) {
            console.log(e);
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

    searchAndMove: function(parentId) {
        pimcore.helpers.searchAndMove(parentId, function() {
            this.reload();
        }.bind(this), "asset");
    },



    deleteAsset : function (tree, record) {
        pimcore.helpers.deleteAsset(record.data.id);
    }
});