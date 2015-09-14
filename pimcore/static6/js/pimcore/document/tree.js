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

Ext.define('documentreemodel', {
    extend: 'Ext.data.TreeModel',
    idProperty: 'id',
    fields: [{
        name: "id",
        convert: undefined
    }, {
        name: "name",
        convert: undefined
    }]
});


pimcore.registerNS("pimcore.document.tree");
pimcore.document.tree = Class.create({

    treeDataUrl: "/admin/document/tree-get-childs-by-id/",

    initialize: function(config) {

        this.position = "left";

        if (!config) {
            this.config = {
                rootId: 1,
                rootVisible: true,
                loaderBaseParams: {},
                treeId: "pimcore_panel_tree_documents",
                treeIconCls: "pimcore_icon_document",
                treeTitle: t('documents'),
                parentPanel: Ext.getCmp("pimcore_panel_tree_left"),
                index: 1
            };
        }
        else {
            this.config = config;
        }

        pimcore.layout.treepanelmanager.register(this.config.treeId);

        // get root node config
        Ext.Ajax.request({
            url: "/admin/document/tree-get-root",
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
        rootNodeConfig.id = "" +  rootNodeConfig.id;
        rootNodeConfig.allowDrag = true;
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
            //folderSort: true,
        });


        // documents
        this.tree = Ext.create('pimcore.tree.Panel', {
            region: "center",
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            autoScroll:true,
            autoLoad: false,
            animate:true,
            containerScroll: true,
            rootVisible: this.config.rootVisible,
            border: false,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    appendOnly: true,
                    ddGroup: "element"
                },
                listeners: {
                    beforedrop: function (node, data) {
                        console.log("beforedrop");
                    },
                    nodedragover: this.onTreeNodeOver.bind(this)
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
            store: store,
            listeners: this.getTreeNodeListeners()
        });


        //
        //this.tree.on("startdrag", this.onDragStart.bind(this));
        //this.tree.on("enddrag", this.onDragEnd.bind(this));
        //this.tree.on("nodedragover", this.onTreeNodeOver.bind(this));
        //this.tree.on("afterrender", function () {

        this.tree.loadMask = new Ext.LoadMask({
            target: this.tree,
            msg: t("please_wait")
        });

        //    this.tree.loadMask.enable();
        //}.bind(this));
        //
        this.tree.on("itemappend", pimcore.helpers.treeNodeThumbnailPreview.bind(this));

        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.updateLayout();
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick,
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

    onDragStart : function (tree, node, id) {
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
            pimcore.helpers.openDocument(record.data.id, record.data.type);
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

        this.updateDocument(node.data.id, {
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
                    tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                        "error",t(rdata.message));
                    this.refresh(oldParent);
                    this.refresh(newParent);
                }
            } catch(e){
                tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                this.refresh(oldParent);
                this.refresh(newParent);
            }
            tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree));
    },


    onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts ) {
        var tree = node.getOwnerTree();

        // check for locks
        if (node.data.locked && oldParent.data.id != newParent.data.id) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

        // check new parent's permission
        if(!newParent.data.permissions.create){
            Ext.MessageBox.alert(t('missing_permission'), t('element_cannot_be_moved'));
            return false;
        }

        if(this.isDisallowedKey(newParent.id, node.data.text)) {
            return false;
        }

        // check permissions
        if (node.data.permissions.settings) {
            tree.loadMask.show();
            return true;
        }
        return false;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();

        tree.select();


        var pasteMenu = [];
        var pasteInheritanceMenu = [];

        var menu = new Ext.menu.Menu();

        if ((record.data.type == "page" || record.data.type == "email" || record.data.type == "folder"
            || record.data.type == "link" || record.data.type == "hardlink")
            && record.data.permissions.create) {

            var document_types = pimcore.globalmanager.get("document_types_store");

            var documentMenu = {
                page: [],
                snippet: [],
                email : [], //ckogler
                ref: this
            };

            document_types.sort([ { property : 'priority', direction: 'DESC' },
                { property : 'name', direction: 'ASC' } ]);

            document_types.each(function(documentMenu, typeRecord) {
                if (typeRecord.get("type") == "page") {
                    documentMenu.page.push({
                        text: ts(typeRecord.get("name")),
                        iconCls: "pimcore_icon_page_add",
                        handler: this.addDocument.bind(this, tree, record, "page")
                    });
                }
                else if (typeRecord.get("type") == "snippet") {
                    documentMenu.snippet.push({
                        text: ts(typeRecord.get("name")),
                        iconCls: "pimcore_icon_snippet_add",
                        handler: this.addDocument.bind(this, tree, record, "snippet")
                    });
                }else if (typeRecord.get("type") == "email") { //ckogler
                    documentMenu.email.push({
                        text: ts(typeRecord.get("name")),
                        iconCls: "pimcore_icon_email_add",
                        handler: this.addDocument.bind(this, tree, record, "email")
                    });
                }
            }.bind(this, documentMenu), documentMenu);


            // empty page
            documentMenu.page.push({
                text: "&gt; " + t("empty_page"),
                iconCls: "pimcore_icon_page_add",
                handler: this.addDocument.bind(this, tree, record, "page")
            });

            // empty snippet
            documentMenu.snippet.push({
                text: "&gt; " + t("empty_snippet"),
                iconCls: "pimcore_icon_snippet_add",
                handler: this.addDocument.bind(this, tree, record, "snippet")
            });

            // empty email  //ckogler
            documentMenu.email.push({
                text: "&gt; " + t("empty_email"),
                iconCls: "pimcore_icon_email_add",
                handler: this.addDocument.bind(this, tree, record, "email")
            });

            menu.add(new Ext.menu.Item({
                text: t('add_page'),
                iconCls: "pimcore_icon_page_add",
                /*handler: this.attributes.reference.addDocument.bind(this, "page"),*/
                menu: documentMenu.page,
                hideOnClick: false
            }));

            menu.add(new Ext.menu.Item({
                text: t('add_snippet'),
                iconCls: "pimcore_icon_snippet_add",
                /*handler: this.attributes.reference.addDocument.bind(this, "snippet"),*/
                menu: documentMenu.snippet,
                hideOnClick: false
            }));

            //ckogler
            menu.add(new Ext.menu.Item({
                text: t('add_email'),
                iconCls: "pimcore_icon_email_add",
                menu: documentMenu.email,
                hideOnClick: false
            }));

            menu.add(new Ext.menu.Item({
                text: t('add_link'),
                iconCls: "pimcore_icon_link_add",
                handler: this.addDocument.bind(this, tree, record, "link")
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_hardlink'),
                iconCls: "pimcore_icon_hardlink_add",
                handler: this.addDocument.bind(this, tree, record, "hardlink")
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_folder'),
                iconCls: "pimcore_icon_folder_add",
                handler: this.addDocument.bind(this, tree, record, "folder")
            }));

            //paste
            if (this.cacheDocumentId && record.data.permissions.create) {
                pasteMenu.push({
                    text: t("paste_recursive_as_childs"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "recursive")
                });
                pasteMenu.push({
                    text: t("paste_recursive_updating_references"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "recursive-update-references")
                });
                pasteMenu.push({
                    text: t("paste_as_child"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "child")
                });

                pasteInheritanceMenu.push({
                    text: t("paste_recursive_as_childs"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "recursive", true)
                });
                pasteInheritanceMenu.push({
                    text: t("paste_recursive_updating_references"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "recursive-update-references", true)
                });
                pasteInheritanceMenu.push({
                    text: t("paste_as_child"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "child", true)
                });
            }
        }


        //paste
        if (this.cutDocument && record.data.permissions.create) {
            pasteMenu.push({
                text: t("paste_cut_element"),
                iconCls: "pimcore_icon_paste",
                handler: function() {
                    this.pasteCutDocument(this.cutDocument,
                        this.cutParentNode, record, this.tree);
                    this.cutParentNode = null;
                    this.cutDocument = null;
                }.bind(this)
            });
        }
        if (this.cacheDocumentId && record.data.permissions.create) {

            if (record.data.type != "folder") {
                pasteMenu.push({
                    text: t("paste_contents"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.pasteInfo.bind(this, tree, record, "replace")
                });
            }
        }

        if(pasteMenu.length > 0) {
            menu.add(new Ext.menu.Item({
                text: t('paste'),
                iconCls: "pimcore_icon_paste",
                hideOnClick: false,
                menu: pasteMenu
            }));
        }

        if(pasteInheritanceMenu.length > 0) {
            menu.add(new Ext.menu.Item({
                text: t('paste_inheritance'),
                iconCls: "pimcore_icon_paste",
                hideOnClick: false,
                menu: pasteInheritanceMenu
            }));
        }

        if(record.data.permissions.view) {
            menu.add(new Ext.menu.Item({
                text: t('copy'),
                iconCls: "pimcore_icon_copy",
                handler: this.copy.bind(this, tree, record)
            }));
        }

        if (record.data.id != 1 && !record.data.locked && record.data.permissions.rename) {
            menu.add(new Ext.menu.Item({
                text: t('cut'),
                iconCls: "pimcore_icon_cut",
                handler: this.cut.bind(this, tree, record)
            }));
        }

        if (record.data.permissions.rename && record.data.id != 1 && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_edit_key",
                handler: this.editDocumentKey.bind(this, tree, record)
            }));
        }

        // not for the home document
        if(record.data.id != 1 && record.data.permissions.publish && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('convert_to'),
                iconCls: "pimcore_icon_convert",
                hideOnClick: false,
                menu: [{
                    text: t("page"),
                    iconCls: "pimcore_icon_page",
                    handler: this.convert.bind(this, tree, record, "page")
                    //hidden: this.attributes.type == "page"
                }, {
                    text: t("snippet"),
                    iconCls: "pimcore_icon_snippet",
                    handler: this.convert.bind(this, tree, record, "snippet")
                    //hidden: this.attributes.type == "snippet"
                }, {
                    text: t("email"),
                    iconCls: "pimcore_icon_email",
                    handler: this.convert.bind(this, tree, record, "email")
                    //hidden: this.attributes.type == "email"
                },{
                    text: t("link"),
                    iconCls: "pimcore_icon_link",
                    handler: this.convert.bind(this, tree, record, "link")
                    //hidden: this.attributes.type == "link"
                }, {
                    text: t("hardlink"),
                    iconCls: "pimcore_icon_hardlink",
                    handler: this.convert.bind(this, tree, record, "hardlink")
                    //hidden: this.attributes.type == "hardlink"
                }]
            }));
        }

        //publish
        if (record.data.type != "folder" && !record.data.locked) {
            if (record.data.published && record.data.permissions.unpublish) {
                menu.add(new Ext.menu.Item({
                    text: t('unpublish'),
                    iconCls: "pimcore_icon_tree_unpublish",
                    handler: this.publishDocument.bind(this, tree, record, 'unpublish')
                }));
            } else if(!record.data.published && record.data.permissions.publish) {
                menu.add(new Ext.menu.Item({
                    text: t('publish'),
                    iconCls: "pimcore_icon_tree_publish",
                    handler: this.publishDocument.bind(this, tree, record, 'publish')
                }));
            }
        }


        if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.deleteDocument.bind(this, tree, record)
            }));
        }

        if (record.data.permissions.create) {
            menu.add(new Ext.menu.Item({
                text: t('search_and_move'),
                iconCls: "pimcore_icon_search_and_move",
                handler: this.searchAndMove.bind(this, tree, record)
            }));
        }


        // site-mgnt
        var user = pimcore.globalmanager.get("user");

        if (user.admin && record.data.type == "page" && record.data.id != 1) {
            if (!record.data.site) {
                menu.add(new Ext.menu.Item({
                    text: t('advanced'),
                    iconCls: "",
                    hideOnClick: false,
                    menu: [
                        {
                            text: t('use_as_site'),
                            handler: this.addUpdateSite.bind(this, tree, record)
                        }
                    ]
                }));
            }
            else {
                menu.add(new Ext.menu.Item({
                    text: t('advanced'),
                    iconCls: "",
                    hideOnClick: false,
                    menu: [
                        {
                            text: t('edit_site'),
                            handler: this.addUpdateSite.bind(this, tree, record)
                        }, {
                            text: t('remove_site'),
                            handler: this.removeSite.bind(this, tree, record)
                        }
                    ]
                }));
            }
        }

        if (record.data.id != 1) {
            if(user.admin) { // only admins are allowed to change locks in frontend

                var lockMenu = [];
                if(record.data.lockOwner) { // add unlock
                    lockMenu.push({
                        text: t('unlock'),
                        iconCls: "pimcore_icon_lock_delete",
                        handler: function () {
                            this.updateDocument(record.data.id, {locked: null}, function () {
                                this.refresh(this.tree.getRootNode());
                            }.bind(this));
                        }.bind(this)
                    });
                } else {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock_add",
                        handler: function () {
                            this.updateDocument(record.data.id, {locked: "self"}, function () {
                                this.refresh(this.tree.getRootNode());
                            }.bind(this));
                        }.bind(this)
                    });

                    if(record.data.type != "snippet") {
                        lockMenu.push({
                            text: t('lock_and_propagate_to_childs'),
                            iconCls: "pimcore_icon_lock_add_propagate",
                            handler: function () {
                                this.updateDocument(this, tree, record, {locked: "propagate"},
                                    function () {
                                        this.refresh(this.tree.getRootNode());
                                    }.bind(this));
                            }.bind(this)
                        });
                    }
                }

                if(record.data["locked"]) {
                    // add unlock and propagate to children functionality
                    lockMenu.push({
                        text: t('unlock_and_propagate_to_children'),
                        iconCls: "pimcore_icon_lock_delete",
                        handler: function () {
                            Ext.Ajax.request({
                                url: "/admin/element/unlock-propagate",
                                params: {
                                    id: record.id,
                                    type: "document"
                                },
                                success: function () {
                                    this.refresh(this.parentNode);
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

        if ((record.data.type == "page" || record.data.type == "hardlink") && record.data.permissions.view) {
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_openpage",
                handler: function () {
                    window.open(record.data.path);
                }.bind(this)
            }));
        }

        if (!record.data.leaf) {
            menu.add(new Ext.menu.Item({
                text: t('refresh'),
                iconCls: "pimcore_icon_reload",
                handler: this.refresh.bind(this, record)
            }));
        }

        menu.showAt(e.pageX, e.pageY);
    },

    copy: function (tree, record) {
        this.cacheDocumentId = record.data.id;
    },

    cut: function (tree, record) {
        this.cutDocument = record;
        this.cutParentNode = record.parentNode;
    },

    pasteCutDocument: function(document, oldParent, newParent, tree) {
        this.updateDocument(document.id, {
            parentId: newParent.id
        }, function (document, newParent, oldParent, tree, response) {
            try {
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.data.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    document.data.basePath = newBasePath;
                    document.data.path = document.data.basePath + "/" + document.data.text;
                }
                else {
                    tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("error_moving_document"), "error", t(rdata.message));

                }
            } catch(e) {
                pimcore.helpers.showNotification(t("error"), t("error_moving_document"), "error");
            }

            this.refresh(oldParent);
            this.refresh(newParent);

            this.tree.loadMask.hide();

        }.bind(this, document, newParent, oldParent, tree));

    },

    pasteInfo: function (tree, record, type, enableInheritance) {
        pimcore.helpers.addTreeNodeLoadingIndicator("document", this.id);

        if(enableInheritance !== true) {
            enableInheritance = false;
        }

        Ext.Ajax.request({
            url: "/admin/document/copy-info/",
            params: {
                targetId: record.data.id,
                sourceId: this.cacheDocumentId,
                type: type,
                enableInheritance: enableInheritance
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
                    items: [this.pasteProgressBar]
                });

                record.pasteWindow.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function () {

                        try {
                            this.pasteComplete(record);
                        } catch(e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_document"), "error");
                            this.refresh(record);
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if(this.pasteProgressBar) {
                            var status = currentStep / steps;
                            record.pasteProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        record.pasteWindow.close();
                        record.pasteProgressBar = null;

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_document"), "error", t(message));
                        this.refresh(record);
                    }.bind(this),
                    jobs: res.pastejobs
                });
            } else {
                throw "There are no pasting jobs";
            }
        } catch (e) {
            Ext.MessageBox.alert(t('error'), e);
            this.pasteComplete(this);
        }
    },

    pasteComplete: function (node) {
        if(node.pasteWindow) {
            node.pasteWindow.close();
        }

        node.pasteProgressBar = null;
        node.pasteWindow = null;

        //this.tree.loadMask.hide();
        pimcore.helpers.removeTreeNodeLoadingIndicator("document", node.id);
        this.refresh(node);
    },

    removeSite: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/document/remove-site/",
            params: {
                id: record.data.id
            },
            success: function () {
                pimcore.globalmanager.get("sites").reload();
                this.refresh(record.parentNode);
            }.bind(this)
        });

        delete record.data.site;
    },

    addUpdateSite: function (tree, record) {

        var data = {
            "domains": [],
            "mainDomain": "",
            "errorDocument": "",
            "redirectToMainDomain": false
        };

        if(record.data["site"]) {
            data = record.data["site"];
        }

        var win = new Ext.Window({
            width: 600,
            height: 340,
            layout: "fit",
            closeAction: "close",
            items: [{
                xtype: "form",
                bodyStyle: "padding: 10px;",
                defaults: {
                    labelWidth: 250,
                    width: 550
                },
                itemId: "form",
                items: [{
                    xtype: "textfield",
                    name: "mainDomain",
                    fieldLabel: t("main_domain"),
                    value: data["mainDomain"]
                }, {
                    xtype: "textarea",
                    name: "domains",
                    height: 150,
                    style: "word-wrap: normal;",
                    fieldLabel: t("additional_domains") + "<br /><br />RegExp are supported. eg. .*example.com",
                    value: data.domains.join("\n")
                }, {
                    xtype: "textfield",
                    name: "errorDocument",
                    cls: "input_drop_target",
                    fieldLabel: t("error_page"),
                    value: data["errorDocument"],
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function(target, dd, e, data) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop : function (target, dd, e, data) {
                                    data = data.records[0].data;
                                    if (data.elementType == "document") {
                                        this.setValue(data.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                }, {
                    xtype: "checkbox",
                    name: "redirectToMainDomain",
                    fieldLabel: t("redirect_to_main_domain"),
                    checked: data["redirectToMainDomain"]
                }]
            }],
            buttons: [{
                text: t("cancel"),
                iconCls: "pimcore_icon_delete",
                handler: function () {
                    win.close();
                }
            }, {
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    var data = win.getComponent("form").getForm().getFieldValues();
                    data["id"] = this.id;

                    Ext.Ajax.request({
                        url: "/admin/document/update-site/",
                        params: data,
                        success: function (response) {
                            var site = Ext.decode(response.responseText);
                            record.data.site = site;
                            tree.getStore().load({
                                node: record.parentNode
                            });
                            pimcore.globalmanager.get("sites").reload();
                        }.bind(this, tree, record)
                    });

                    win.close();
                }.bind(this)
            }]
        });

        win.show();
    },

    addDocument : function (tree, record, type, docTypeId) {
        var textKeyTitle;
        var textKeyMessage;

        if(!is_numeric(docTypeId)) {
            docTypeId = null; // avoid sending objects or functions to the controller
        }

        if(type == "page") {

            textKeyTitle = "add_document";
            textKeyMessage = "please_enter_the_name_of_the_new_document";

            //create a custom form
            var pageForm = new Ext.form.FormPanel({
                title: t(textKeyMessage),
                border: false,
                bodyStyle: "padding: 10px;",
                items: [{
                    xtype: "textfield",
                    fieldLabel: t('key'),
                    itemId: "key",
                    name: 'key',
                    width: 300,
                    enableKeyEvents: true,
                    listeners: {
                        afterrender: function () {
                            window.setTimeout(function () {
                                this.focus(true);
                            }.bind(this), 100);
                        },
                        keyup: function (el) {
                            pageForm.getComponent("name").setValue(el.getValue());
                        }
                    }
                },{
                    xtype: "textfield",
                    itemId: "name",
                    fieldLabel: t('navigation'),
                    name: 'name',
                    width: 300
                },{
                    xtype: "textfield",
                    itemId: "title",
                    fieldLabel: t('title'),
                    name: 'title',
                    width: 300
                }]
            });

            var submitFunction = function() {
                var params = pageForm.getForm().getFieldValues();
                messageBox.close();
                if(params["key"].length >= 1) {
                    params["type"] = type;
                    params["docTypeId"] = docTypeId;
                    this.addDocumentCreate(tree, record, params);
                } else {
                    return; //ignore
                }
            };

            //create a custom MessageBox
            var messageBox = new Ext.Window({
                modal: true,
                width: 400,
                items: pageForm,
                buttons: [{
                    text: t('OK'),
                    handler: submitFunction.bind(this, tree, record)
                },{
                    text: t('cancel'),
                    handler: function() {
                        messageBox.close();
                    }
                }]
            });

            messageBox.show();

            var map = new Ext.util.KeyMap({
                target: messageBox.getEl(),
                key:  Ext.event.Event.ENTER,
                fn: submitFunction.bind(this)
            });

        } else {

            if (type == "folder") {
                textKeyTitle = "add_folder"
                textKeyMessage = "please_enter_the_name_of_the_new_folder";
            } else {
                textKeyTitle = "add_document";
                textKeyMessage = "please_enter_the_name_of_the_new_document";
            }

            Ext.MessageBox.prompt(t(textKeyTitle), t(textKeyMessage), function (tree, record, type, docTypeId, button, value, object) {
                if (button == "ok") {

                    this.addDocumentCreate(
                        tree, record,
                        {
                            key: value,
                            type: type,
                            docTypeId: docTypeId
                        });
                }
            }.bind(this, tree, record, type, docTypeId));
        }
    },

    publishDocument: function (tree, record, task) {
        var id = record.data.id;
        var type = record.data.type;

        var parameters = {};
        parameters.id = id;

        Ext.Ajax.request({
            url: '/admin/' + type + '/save/task/' + task,
            method: "post",
            params: parameters,
            success: function (task, response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        var view = tree;
                        var nodeEl = Ext.fly(view.getNodeByRecord(record));

                        if (task == 'unpublish') {
                            nodeEl.addCls('pimcore_unpublished');
                            record.data.published = false;
                            if (pimcore.globalmanager.exists("document_" + record.data.id)) {
                                pimcore.globalmanager.get("document_" + record.data.id).toolbarButtons.unpublish.hide();
                            }

                        } else {
                            nodeEl.removeCls('pimcore_unpublished');
                            record.data.published = true;
                            if (pimcore.globalmanager.exists("document_" + record.data.id)) {
                                pimcore.globalmanager.get("document_" + record.data.id).toolbarButtons.unpublish.show();
                            }
                        }

                        if (pimcore.globalmanager.exists("document_" + record.data.id)) {
                            // reload versions
                            if (pimcore.globalmanager.get("document_" + record.data.id).versions) {
                                if (typeof pimcore.globalmanager.get("document_" + record.data.id).versions.reload
                                    == "function") {
                                    pimcore.globalmanager.get("document_" + record.data.id).versions.reload();
                                }
                            }
                        }

                        pimcore.helpers.showNotification(t("success"), t("successful_" + task + "_document"),
                            "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_" + task + "_document"),
                            "error", t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_" + task + "_document"), "error");
                }

            }.bind(this, task)
        });

    },

    addDocumentCreate : function (tree, record, params) {

        if(params["key"]) {
            // check for ident filename in current level
            if(this.isExistingKeyInLevel(record, params["key"])) {
                return;
            }

            if(this.isDisallowedKey(record.id, params["key"])) {
                return;
            }

            params["key"] = pimcore.helpers.getValidFilename(params["key"]);
            params["index"] = record.childNodes.length;
            params["parentId"] = record.id;

            Ext.Ajax.request({
                url: "/admin/document/add/",
                params: params,
                success: this.addDocumentComplete.bind(this, tree, record)
            });
        }
    },


    addDocumentComplete: function (tree, record, response) {
        try {
            response = Ext.decode(response.responseText);
            if (response && response.success) {
                record.data.leaf = false;
                record.expand();
                if(pimcore.globalmanager.get("document_documenttype_store").indexOf(response.type) >= 0) {
                    pimcore.helpers.openDocument(response.id, response.type);
                }
            }
            else {
                pimcore.helpers.showNotification(t("error"), t("error_creating_document"), "error",
                    t(response.message));
            }
        } catch(e) {
            pimcore.helpers.showNotification(t("error"), t("error_creating_document"), "error");
        }
        this.refresh(record);
    },

    editDocumentKey : function (tree, record) {
        Ext.MessageBox.prompt(t('edit_key'), t('please_enter_the_new_key'),
            this.editDocumentKeyComplete.bind(this, tree, record));
    },

    editDocumentKeyComplete: function (tree, record, button, value, object) {
        if (button == "ok") {

            // check for ident filename in current level
            if(this.isExistingKeyInLevel(record.parentNode, value, this)) {
                return;
            }

            if(this.isDisallowedKey(record.parentNode.id, value)) {
                return;
            }

            value = pimcore.helpers.getValidFilename(value);

            record.set("text", value);
            record.data.path = record.data.basePath + value;

            this.tree.loadMask.show();

            this.updateDocument(record.id, {key: value}, function (response) {

                this.tree.loadMask.hide();
                this.refresh(record);

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        if (pimcore.globalmanager.exists("document_" + record.data.id)) {
                            pimcore.helpers.closeDocument(record.data.id);
                            pimcore.helpers.openDocument(record.id, record.data.type);
                        }
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_document"), "error",
                            t(rdata.message));
                        this.refresh(record.parentNode);
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_document"), "error");
                    this.refresh(record.parentNode);
                }
            }.bind(this));
        }
    },

    isExistingKeyInLevel: function (parentNode, key, node) {

        key = pimcore.helpers.getValidFilename(key);
        var parentChilds = parentNode.childNodes;
        for (var i = 0; i < parentChilds.length; i++) {
            if (parentChilds[i].data.text == key && node != parentChilds[i]) {
                Ext.MessageBox.alert(t('edit_key'),
                    t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
                return true;
            }
        }
        return false;
    },

    isDisallowedKey: function (parentNodeId, key) {

        if(parentNodeId === 1) {
            var disallowedKeys = ["admin","install","webservice","plugin"];
            if(in_arrayi(key, disallowedKeys)) {
                Ext.MessageBox.alert(t('name_is_not_allowed'),
                    t('name_is_not_allowed'));
                return true;
            }
        }
        return false;
    },

    updateDocument: function (id, data, callback) {

        if (!callback) {
            callback = function() {
            };
        }

        data.id = id;

        Ext.Ajax.request({
            url: "/admin/document/update/",
            method: "post",
            params: data,
            success: callback
        });
    },

    deleteDocument : function (tree, record) {
        pimcore.helpers.deleteDocument(record.data.id);
    },

    convert: function (tree, record, type) {
        Ext.MessageBox.show({
            title:t('are_you_sure'),
            msg: t("all_content_will_be_lost"),
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: function (type, button) {
                if (button == "ok") {

                    if (pimcore.globalmanager.exists("document_" + record.data.id)) {
                        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                        tabPanel.remove("document_" + record.data.id);
                    }

                    Ext.Ajax.request({
                        url: "/admin/document/convert/",
                        method: "post",
                        params: {
                            id: record.data.id,
                            type: type
                        },
                        success: function () {
                            this.refresh(record.parentNode);
                        }.bind(this)
                    });
                }
            }.bind(this, type)
        });
    },

    searchAndMove: function(tree, record) {
        var parentId = record.data.id;
        pimcore.helpers.searchAndMove(parentId, function() {
            this.reload();
        }.bind(this), "document");
    },


    isKeyValid: function (key) {

        // key must be at least one character, an maximum 30 characters
        if (key.length < 1 && key.length > 30) {
            return false;
        }
    },

    refresh: function (record) {
        var ownerTree = record.getOwnerTree();
        record.data.expanded = true;
        ownerTree.getStore().load({
            node: record
        });
    }



});
