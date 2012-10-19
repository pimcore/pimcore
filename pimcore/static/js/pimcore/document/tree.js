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

pimcore.registerNS("pimcore.document.tree");
pimcore.document.tree = Class.create({

    treeDataUrl: "/admin/document/tree-get-childs-by-id/",

    initialize: function(config) {

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

        rootNodeConfig.nodeType = "async";
        rootNodeConfig.text = "home";
        rootNodeConfig.draggable = true;
        rootNodeConfig.iconCls = "pimcore_icon_home";
        rootNodeConfig.expanded = true;

        // documents
        this.tree = new Ext.tree.TreePanel({
            region: "center",
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            useArrows:true,
            autoScroll:true,
            animate:true,
            enableDD:true,
            ddGroup: "element",
            containerScroll: true,
            rootVisible: this.config.rootVisible,
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
            })
        });

        this.tree.on("startdrag", this.onDragStart.bind(this));
        this.tree.on("enddrag", this.onDragEnd.bind(this));
        this.tree.on("nodedragover", this.onTreeNodeOver.bind(this));
        this.tree.on("afterrender", function () {
            this.tree.loadMask = new Ext.LoadMask(this.tree.getEl(), {msg: t("please_wait")});
            this.tree.loadMask.enable();
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

    onDragStart : function (tree, node, id) {
        pimcore.helpers.dndMaskFrames();
    },

    onDragEnd : function () {

        pimcore.helpers.dndUnmaskFrames();
    },

    onTreeNodeClick: function () {
        if(this.attributes.permissions.view) {
            pimcore.helpers.openDocument(this.id, this.attributes.type);
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

        this.attributes.reference.updateDocument(this.id, {
            parentId: newParent.id,
            index: index
        }, function (newParent, oldParent, tree, response) {
            try {
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
                    pimcore.helpers.showNotification(t("error"), t("error_moving_document"), "error", t(rdata.message));
                    oldParent.reload();
                    newParent.reload();
                }
            } catch(e) {
                pimcore.helpers.showNotification(t("error"), t("error_moving_document"), "error");
            }
            tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree));
    },

    onTreeNodeBeforeMove: function (tree, element, oldParent, newParent, index) {
        
        // check for locks
        if (element.attributes.locked && oldParent.id != newParent.id) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

        // check new parent's permission
        if(!newParent.attributes.permissions.create){
            Ext.MessageBox.alert(t('missing_permission'), t('element_cannot_be_moved'));
            return false;
        }

        // check permissions
        if (element.attributes.permissions.settings) {
            tree.loadMask.show();
            return true;
        }
        return false;
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var pasteMenu = [];
        var pasteInheritanceMenu = [];

        var menu = new Ext.menu.Menu();
        //ckogler added "email"
        if ((this.attributes.type == "page" || this.attributes.type == "email" || this.attributes.type == "folder" || this.attributes.type == "link" || this.attributes.type == "hardlink") && this.attributes.permissions.create) {

            var document_types = pimcore.globalmanager.get("document_types_store");

            var documentMenu = {
                page: [],
                snippet: [],
                email : [], //ckogler
                ref: this
            };

            document_types.sort([ { field : 'priority', direction: 'DESC' }, { field : 'name', direction: 'ASC' } ],'DESC');

            document_types.each(function(record) {
                if (record.get("type") == "page") {
                    this.page.push({
                        text: ts(record.get("name")),
                        iconCls: "pimcore_icon_page_add",
                        handler: this.ref.attributes.reference.addDocument.bind(this.ref, "page", record.get("id"))
                    });
                }
                else if (record.get("type") == "snippet") {
                    this.snippet.push({
                        text: ts(record.get("name")),
                        iconCls: "pimcore_icon_snippet_add",
                        handler: this.ref.attributes.reference.addDocument.bind(this.ref, "snippet", record.get("id"))
                    });
                }else if (record.get("type") == "email") { //ckogler
                    this.email.push({
                        text: ts(record.get("name")),
                        iconCls: "pimcore_icon_email_add",
                        handler: this.ref.attributes.reference.addDocument.bind(this.ref, "email", record.get("id"))
                    });
                }
            }, documentMenu);


            // empty page
            documentMenu.page.push({
                text: "&gt; " + t("empty_page"),
                iconCls: "pimcore_icon_page_add",
                handler: this.attributes.reference.addDocument.bind(this, "page")
            });

            // empty snippet
            documentMenu.snippet.push({
                text: "&gt; " + t("empty_snippet"),
                iconCls: "pimcore_icon_snippet_add",
                handler: this.attributes.reference.addDocument.bind(this, "snippet")
            });

            // empty email  //ckogler
            documentMenu.email.push({
                text: "&gt; " + t("empty_email"),
                iconCls: "pimcore_icon_email_add",
                handler: this.attributes.reference.addDocument.bind(this, "email")
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
                handler: this.attributes.reference.addDocument.bind(this, "link")
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_hardlink'),
                iconCls: "pimcore_icon_hardlink_add",
                handler: this.attributes.reference.addDocument.bind(this, "hardlink")
            }));
            menu.add(new Ext.menu.Item({
                text: t('add_folder'),
                iconCls: "pimcore_icon_folder_add",
                handler: this.attributes.reference.addDocument.bind(this, "folder")
            }));

            //paste
            if (this.attributes.reference.cacheDocumentId && this.attributes.permissions.create) {
                pasteMenu.push({
                    text: t("paste_recursive_as_childs"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "recursive")
                });
                pasteMenu.push({
                    text: t("paste_recursive_updating_references"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "recursive-update-references")
                });
                pasteMenu.push({
                    text: t("paste_as_child"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "child")
                });

                pasteInheritanceMenu.push({
                    text: t("paste_recursive_as_childs"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "recursive", true)
                });
                pasteInheritanceMenu.push({
                    text: t("paste_recursive_updating_references"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "recursive-update-references", true)
                });
                pasteInheritanceMenu.push({
                    text: t("paste_as_child"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "child", true)
                });
            }
        }


        //paste
        if (this.attributes.reference.cutDocument && this.attributes.permissions.create) {
            pasteMenu.push({
                text: t("paste_cut_element"),
                iconCls: "pimcore_icon_paste",
                handler: function() {
                    this.attributes.reference.pasteCutDocument(this.attributes.reference.cutDocument, this.attributes.reference.cutParentNode, this, this.attributes.reference.tree);
                    this.attributes.reference.cutParentNode = null;
                    this.attributes.reference.cutDocument = null;
                }.bind(this)
            });
        }
        if (this.attributes.reference.cacheDocumentId && this.attributes.permissions.create) {

            if (this.attributes.type != "folder") {
                pasteMenu.push({
                    text: t("paste_contents"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "replace")
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

        menu.add(new Ext.menu.Item({
            text: t('copy'),
            iconCls: "pimcore_icon_copy",
            handler: this.attributes.reference.copy.bind(this)
        }));

        if (this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('cut'),
                iconCls: "pimcore_icon_cut",
                handler: this.attributes.reference.cut.bind(this)
            }));
        }

        if (this.attributes.permissions.rename && this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_edit_key",
                handler: this.attributes.reference.editDocumentKey.bind(this)
            }));
        }

        // not for the home document
        if(this.id != 1) {
            menu.add(new Ext.menu.Item({
                text: t('convert_to'),
                iconCls: "pimcore_icon_convert",
                hideOnClick: false,
                menu: [{
                    text: t("page"),
                    iconCls: "pimcore_icon_page",
                    handler: this.attributes.reference.convert.bind(this, "page"),
                    hidden: this.attributes.type == "page"
                }, {
                    text: t("snippet"),
                    iconCls: "pimcore_icon_snippet",
                    handler: this.attributes.reference.convert.bind(this, "snippet"),
                    hidden: this.attributes.type == "snippet"
                }, {
                    text: t("email"),
                    iconCls: "pimcore_icon_email",
                    handler: this.attributes.reference.convert.bind(this, "email"),
                    hidden: this.attributes.type == "email"
                },{
                    text: t("link"),
                    iconCls: "pimcore_icon_link",
                    handler: this.attributes.reference.convert.bind(this, "link"),
                    hidden: this.attributes.type == "link"
                }, {
                    text: t("hardlink"),
                    iconCls: "pimcore_icon_hardlink",
                    handler: this.attributes.reference.convert.bind(this, "hardlink"),
                    hidden: this.attributes.type == "hardlink"
                }]
            }));
        }

        //publish
        if (this.attributes.permissions.publish && this.attributes.type != "folder") {
            if (this.attributes.published && !this.attributes.locked) {
                menu.add(new Ext.menu.Item({
                    text: t('unpublish'),
                    iconCls: "pimcore_icon_tree_unpublish",
                    handler: this.attributes.reference.publishDocument.bind(this, this.attributes.type, this.attributes.id, 'unpublish')
                }));
            } else {
                menu.add(new Ext.menu.Item({
                    text: t('publish'),
                    iconCls: "pimcore_icon_tree_publish",
                    handler: this.attributes.reference.publishDocument.bind(this, this.attributes.type, this.attributes.id, 'publish')
                }));
            }
        }


        if (this.attributes.permissions.remove && this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.attributes.reference.deleteDocument.bind(this)
            }));
        }


        if (this.attributes.type == "page") {

            if (this.attributes.permissions.settings && this.id != 1) {
                if (!this.attributes.site) {
                    menu.add(new Ext.menu.Item({
                        text: t('advanced'),
                        iconCls: "",
                        hideOnClick: false,
                        menu: [
                            {
                                text: t('use_as_site'),
                                handler: this.attributes.reference.useAsSite.bind(this)
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
                                text: t('remove_site'),
                                handler: this.attributes.reference.removeSite.bind(this)
                            },
                            {
                                text: t('edit_domains'),
                                handler: this.attributes.reference.editSite.bind(this)
                            }
                        ]
                    }));
                }
            }
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
                            this.attributes.reference.updateDocument(this.attributes.id, {locked: null}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
                        }.bind(this)
                    });
                } else {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock_add",
                        handler: function () {
                            this.attributes.reference.updateDocument(this.attributes.id, {locked: "self"}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
                        }.bind(this)
                    });
                    
                    if(this.attributes.type != "snippet") {
                        lockMenu.push({
                            text: t('lock_and_propagate_to_childs'),
                            iconCls: "pimcore_icon_lock_add_propagate",
                            handler: function () {
                                this.attributes.reference.updateDocument(this.attributes.id, {locked: "propagate"}, function () {
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

        if (this.attributes.type == "page") {
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_openpage",
                handler: function () {
                    window.open(this.attributes.path);
                }.bind(this)
            }));
        }

        if (this.reload) {
            menu.add(new Ext.menu.Item({
                text: t('refresh'),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            }));
        }

        menu.show(this.ui.getAnchor());
    },

    copy: function () {
        this.attributes.reference.cacheDocumentId = this.id;
    },

    cut: function () {
        this.attributes.reference.cutDocument = this;
        this.attributes.reference.cutParentNode = this.parentNode;
    },

    pasteCutDocument: function(document, oldParent, newParent, tree) {
        document.attributes.reference.updateDocument(document.id, {
            parentId: newParent.id
        }, function (newParent, oldParent, tree, response) {
            try {
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
                    pimcore.helpers.showNotification(t("error"), t("error_moving_document"), "error", t(rdata.message));

                }
            } catch(e) {
                pimcore.helpers.showNotification(t("error"), t("error_moving_document"), "error");
            }
            oldParent.reload();
            newParent.reload();
            tree.loadMask.hide();

        }.bind(document, newParent, oldParent, tree));

    },

    pasteInfo: function (type, enableInheritance) {
        //this.attributes.reference.tree.loadMask.show();

        pimcore.helpers.addTreeNodeLoadingIndicator("document", this.id);

        if(enableInheritance !== true) {
            enableInheritance = false;
        }

        Ext.Ajax.request({
            url: "/admin/document/copy-info/",
            params: {
                targetId: this.id,
                sourceId: this.attributes.reference.cacheDocumentId,
                type: type,
                enableInheritance: enableInheritance
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
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_document"), "error");
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

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_document"), "error", t(message));
                        this.parentNode.reload();
                    }.bind(this),
                    jobs: res.pastejobs
                });
            } else {
                throw "There are no pasting jobs";
            }
        } catch (e) {
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
        pimcore.helpers.removeTreeNodeLoadingIndicator("document", node.id);
        node.reload();
    },

    useAsSite: function () {
        Ext.MessageBox.prompt(t('use_this_as_document_root_for_new_site'), t('please_enter_the_domains_for_the_new_site'), this.attributes.reference.useAsSiteCreate.bind(this), null, null, "");
    },

    useAsSiteCreate: function (button, value, object) {

        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/document/create-site/",
                params: {
                    id: this.id,
                    domains: value
                },
                success: this.attributes.reference.useAsSiteCreateComplete.bind(this)
            });
        }
    },

    useAsSiteCreateComplete: function (response) {
        var site = Ext.decode(response.responseText);
        this.attributes.site = site;

        this.parentNode.reload();
        pimcore.globalmanager.get("sites").reload();
    },

    removeSite: function () {
        Ext.Ajax.request({
            url: "/admin/document/remove-site/",
            params: {
                id: this.id
            },
            success: function () {
                pimcore.globalmanager.get("sites").reload();
            }
        });

        delete this.attributes.site;
        this.parentNode.reload();
    },

    editSite: function () {
        Ext.MessageBox.prompt(t('edit_site_domains'), t('please_enter_the_domains_for_the_site'), this.attributes.reference.editSiteSave.bind(this), null, null, this.attributes.site.domains.join(","));
    },

    editSiteSave: function (button, value, object) {
        Ext.Ajax.request({
            url: "/admin/document/update-site/",
            params: {
                id: this.id,
                domains: value
            },
            success: this.attributes.reference.editSiteSaveComplete.bind(this)
        });
    },

    editSiteSaveComplete: function (response) {
        var site = Ext.decode(response.responseText);
        this.attributes.site = site;

        pimcore.globalmanager.get("sites").reload();
    },

    addDocument : function (type, docTypeId) {
        Ext.MessageBox.prompt(t('add_document'), t('please_enter_the_name_of_the_new_document'), this.attributes.reference.addDocumentCreate.bind(this, type, docTypeId));
    },

    publishDocument: function (type, id, task) {

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

                        if (task == 'unpublish') {
                            this.setCls('pimcore_unpublished');
                            this.attributes.published = false;
                            if (pimcore.globalmanager.exists("document_" + this.id)) {
                                pimcore.globalmanager.get("document_" + this.id).toolbarButtons.unpublish.hide();
                            }

                        } else {
                            this.setCls('');
                            this.attributes.published = true;
                            if (pimcore.globalmanager.exists("document_" + this.id)) {
                                pimcore.globalmanager.get("document_" + this.id).toolbarButtons.unpublish.show();
                            }
                        }

                        if (pimcore.globalmanager.exists("document_" + this.id)) {
                            // reload versions
                            if (pimcore.globalmanager.get("document_" + this.id).versions) {
                                if (typeof pimcore.globalmanager.get("document_" + this.id).versions.reload == "function") {
                                    pimcore.globalmanager.get("document_" + this.id).versions.reload();
                                }
                            }
                        }

                        pimcore.helpers.showNotification(t("success"), t("successful_" + task + "_document"), "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_" + task + "_document"), "error", t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_" + task + "_document"), "error");
                }

            }.bind(this, task)
        });

    },

    addDocumentCreate : function (type, docTypeId, button, value, object) {
        if (button == "ok") {

            // check for ident filename in current level
            if(this.attributes.reference.isExistingKeyInLevel(this, value)) {
                return;
            }

            Ext.Ajax.request({
                url: "/admin/document/add/",
                params: {
                    parentId: this.id,
                    index: this.childNodes.length,
                    type: type,
                    docTypeId: docTypeId,
                    key: pimcore.helpers.getValidFilename(value)
                },
                success: this.attributes.reference.addDocumentComplete.bind(this)
            });
        }
    },

    addDocumentComplete: function (response) {
        try {
            var response = Ext.decode(response.responseText);
            if (response && response.success) {
                this.leaf = false;
                this.expand();
                if (response.type == "page" || response.type == "snippet" || response.type == "email") {   //ckogler
                    pimcore.helpers.openDocument(response.id, response.type);
                }
            }
            else {
                pimcore.helpers.showNotification(t("error"), t("error_creating_document"), "error", t(response.message));
            }
        } catch(e) {
            pimcore.helpers.showNotification(t("error"), t("error_creating_document"), "error");
        }
        this.reload();
    },

    editDocumentKey : function () {
        Ext.MessageBox.prompt(t('edit_key'), t('please_enter_the_new_key'), this.attributes.reference.editDocumentKeyComplete.bind(this), null, null, this.text);
    },

    editDocumentKeyComplete: function (button, value, object) {
        if (button == "ok") {

            // check for ident filename in current level
            if(this.attributes.reference.isExistingKeyInLevel(this.parentNode, value, this)) {
                return;
            }

            value = pimcore.helpers.getValidFilename(value);

            this.setText(value);
            this.attributes.path = this.attributes.basePath + value;

            this.getOwnerTree().loadMask.show();

            this.attributes.reference.updateDocument(this.id, {key: value}, function (response) {

                this.getOwnerTree().loadMask.hide();
                this.reload();

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        if (pimcore.globalmanager.exists("document_" + this.id)) {
                            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                            var tabId = "document_" + this.id;
                            tabPanel.remove(tabId);
                            pimcore.globalmanager.remove("document_" + this.id);

                            pimcore.helpers.openDocument(this.id, this.attributes.type);
                        }
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_document"), "error", t(rdata.message));
                        this.parentNode.reload();
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_document"), "error");
                    this.parentNode.reload();
                }
            }.bind(this));
        }
    },

    isExistingKeyInLevel: function (parentNode, key, node) {

        key = pimcore.helpers.getValidFilename(key);
        var parentChilds = parentNode.childNodes;
        for (var i = 0; i < parentChilds.length; i++) {
            if (parentChilds[i].text == key && node != parentChilds[i]) {
                Ext.MessageBox.alert(t('edit_key'), t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
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

    deleteDocument : function () {
        pimcore.helpers.deleteDocument(this.id);
    },

    convert: function (type) {
        Ext.MessageBox.show({
            title:t('are_you_sure'),
            msg: t("all_content_will_be_lost"),
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: function (type, button) {

                if (pimcore.globalmanager.exists("document_" + this.id)) {
                    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                    tabPanel.remove("document_" + this.id);
                }

                Ext.Ajax.request({
                    url: "/admin/document/convert/",
                    method: "post",
                    params: {
                        id: this.id,
                        type: type
                    },
                    success: function () {
                        this.parentNode.reload();
                    }.bind(this)
                });
            }.bind(this, type)
        });
    },

    isKeyValid: function (key) {

        // key must be at least one character, an maximum 30 characters
        if (key.length < 1 && key.length > 30) {
            return false;
        }
    }
});