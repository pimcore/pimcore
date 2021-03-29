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

    treeDataUrl: null,
    nodesToMove: [],

    initialize: function(config, perspectiveCfg) {
        this.treeDataUrl = Routing.generate('pimcore_admin_document_document_treegetchildsbyid');
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
                treeId: "pimcore_panel_tree_documents",
                treeIconCls: "pimcore_icon_main_tree_document pimcore_icon_material",
                treeTitle: t('documents'),
                parentPanel: Ext.getCmp("pimcore_panel_tree_" + this.position)
            };
        }
        else {
            this.config = config;
        }

        pimcore.layout.treepanelmanager.register(this.config.treeId);

        // get root node config
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_treegetroot'),
            params: {
                id: this.config.rootId,
                view: this.config.customViewId,
                elementType: "document"
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

        var itemsPerPage = pimcore.settings['document_tree_paging_limit'];

        rootNodeConfig.text = t("home");
        rootNodeConfig.id = "" +  rootNodeConfig.id;
        rootNodeConfig.allowDrag = true;
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
                }
            },
            pageSize: itemsPerPage,
            root: rootNodeConfig
        });


        // documents
        this.tree = Ext.create('pimcore.tree.Panel', {
            selModel : {
                mode : 'MULTI'
            },
            region: "center",
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            cls: this.config['rootVisible'] ? '' : 'pimcore_tree_no_root_node',
            autoScroll:true,
            autoLoad: false,
            animate: false,
            containerScroll: true,
            rootVisible: this.config.rootVisible,
            bufferedRenderer: false,
            border: false,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    appendOnly: false,
                    ddGroup: "element"
                },
                listeners: {
                    nodedragover: this.onTreeNodeOver.bind(this),
                    beforedrop: function (node, data, overModel, dropPosition, dropHandlers, eOpts) {
                        this.nodesToMove = [];
                    }.bind(this)
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
            store: store,
            listeners: this.getTreeNodeListeners()
        });

        this.tree.loadMask = new Ext.LoadMask({
            target: this.tree,
            msg: t("please_wait")
        });

        this.tree.on("itemmouseenter", pimcore.helpers.treeNodeThumbnailPreview.bind(this));
        this.tree.on("itemmouseleave", pimcore.helpers.treeNodeThumbnailPreviewHide.bind(this));

        store.on("nodebeforeexpand", function (node) {
            pimcore.helpers.addTreeNodeLoadingIndicator("document", node.data.id, false);
        });

        store.on("nodeexpand", function (node, index, item, eOpts) {
            pimcore.helpers.removeTreeNodeLoadingIndicator("document", node.data.id);
        });


        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.updateLayout();

        if (!this.config.parentPanel.alreadyExpanded && this.perspectiveCfg.expanded) {
            this.config.parentPanel.alreadyExpanded = true;
            this.tree.expand();
        }


    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick,
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
                pimcore.helpers.openDocument(record.data.id, record.data.type);
            }
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

        if (newParent.pagingData) {
            index += newParent.pagingData.offset;
        }

        var moveCallback = function (newParent, oldParent, tree, response) {
            try{
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new paths
                    var newBasePath = newParent.data.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    node.data.basePath = newBasePath;
                    node.data.path = node.data.basePath + "/" + node.data.text;

                    if (!node.data.published) {
                        node.data.cls = "pimcore_unpublished";
                        var view = tree.getView();
                        var nodeEl = Ext.fly(view.getNodeByRecord(node));
                        var nodeElInner = nodeEl.down(".x-grid-td");
                        if (nodeElInner) {
                            nodeElInner.addCls("pimcore_unpublished");
                        }
                    } else {
                        delete node.data.cls;
                    }
                    pimcore.elementservice.nodeMoved("document", oldParent, newParent);
                    this.updateOpenDocumentPaths(node);

                }
                else {
                    tree.loadMask.hide();
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
                tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                // we have to delay refresh between two nodes,
                // as there could be parent child relationship leading to race condition
                window.setTimeout(function () {
                    pimcore.elementservice.refreshNode(oldParent);
                }, 500);
                pimcore.elementservice.refreshNode(newParent);
            }
            tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree);

        var params = {
            parentId: newParent.data.id,
            index: index
        };

        if(
            newParent.data.id !== oldParent.data.id &&
            (node.data.type === 'page' || node.data.type === 'hardlink') &&
            pimcore.globalmanager.get("user").isAllowed('redirects')
        ) {
            this.nodesToMove.push({
                "id": node.data.id,
                "params": params,
                "moveCallback": moveCallback,
            });

            // ask the user if redirects should be created, if node was moved to a new parent
            Ext.MessageBox.confirm("", t("create_redirects"), function (buttonValue) {
                for (let nodeIdx in this.nodesToMove) {
                    this.nodesToMove[nodeIdx]['params']['create_redirects'] = (buttonValue == "yes");
                    pimcore.elementservice.updateDocument(this.nodesToMove[nodeIdx].id, this.nodesToMove[nodeIdx].params, this.nodesToMove[nodeIdx].moveCallback);
                }
            }.bind(this));
        } else {
            pimcore.elementservice.updateDocument(node.data.id, params, moveCallback);
        }
    },


    onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts ) {
        var tree = node.getOwnerTree();

        if (oldParent.getOwnerTree().getId() != newParent.getOwnerTree().getId()) {
            Ext.MessageBox.alert(t('error'), t('cross_tree_moves_not_supported'));
            return false;
        }


        // check for locks
        if (node.data.locked && oldParent.data.id != newParent.data.id) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

        // check new parent's permission
        if(!newParent.data.permissions.create){
            Ext.MessageBox.alert(' ', t('element_cannot_be_moved'));
            return false;
        }

        if(pimcore.elementservice.isDisallowedDocumentKey(newParent.id, node.data.text)) {
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

        if(pimcore.helpers.hasTreeNodeLoadingIndicator("document", record.data.id)) {
            return;
        }

        var menu = new Ext.menu.Menu();
        var perspectiveCfg = this.perspectiveCfg;

        if(tree.getSelectionModel().getSelected().length > 1) {
            var selectedIds = [];
            tree.getSelectionModel().getSelected().each(function (item) {
                selectedIds.push(item.id);
            });

            if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("document.delete")) {
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: this.deleteDocument.bind(this, selectedIds.join(','))
                }));
            }
        } else {
            var pasteMenu = [];
            var pasteInheritanceMenu = [];
            var childSupportedDocument = (record.data.type == "page" || record.data.type == "folder"
                || record.data.type == "link" || record.data.type == "hardlink"
                || record.data.type == "printcontainer");

            if (childSupportedDocument && record.data.permissions.create) {


                var addDocuments = perspectiveCfg.inTreeContextMenu("document.add");
                var addPrintDocuments = perspectiveCfg.inTreeContextMenu("document.addPrintPage");
                var addEmail = perspectiveCfg.inTreeContextMenu("document.addEmail");
                var addSnippet = perspectiveCfg.inTreeContextMenu("document.addSnippet");
                var addLink = perspectiveCfg.inTreeContextMenu("document.addLink");
                var addNewsletter = perspectiveCfg.inTreeContextMenu("document.addNewsletter");
                var addHardlink = perspectiveCfg.inTreeContextMenu("document.addHardlink");

                var addBlankDocument = perspectiveCfg.inTreeContextMenu("document.addBlankDocument");
                var addBlankPrintDocuments = perspectiveCfg.inTreeContextMenu("document.addBlankPrintPage");
                var addBlankEmail = perspectiveCfg.inTreeContextMenu("document.addBlankEmail");
                var addBlankSnippet = perspectiveCfg.inTreeContextMenu("document.addBlankSnippet");
                var addBlankNewsletter = perspectiveCfg.inTreeContextMenu("document.addBlankNewsletter");

                if (addDocuments || addPrintDocuments) {

                    var documentMenu = {
                        page: [],
                        snippet: [],
                        email: [],
                        newsletter: [],
                        printPage: [],
                        ref: this
                    };

                    documentMenu = this.populatePredefinedDocumentTypes(documentMenu, tree, record);

                    if (addBlankDocument) {
                        // empty page
                        documentMenu.page.push({
                            text: "&gt; " + t("blank"),
                            iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "page")
                        });
                    }

                    if (addBlankPrintDocuments) {
                        // empty print pages
                        documentMenu.printPage.push({
                            text: "&gt; " + t("add_printpage"),
                            iconCls: "pimcore_icon_printpage pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "printpage")
                        });
                        documentMenu.printPage.push({
                            text: "&gt; " + t("add_printcontainer"),
                            iconCls: "pimcore_icon_printcontainer pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "printcontainer")
                        });
                    }

                    if (addBlankSnippet) {
                        // empty snippet
                        documentMenu.snippet.push({
                            text: "&gt; " + t("blank"),
                            iconCls: "pimcore_icon_snippet pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "snippet")
                        });
                    }

                    if (addBlankEmail) {
                        // empty email
                        documentMenu.email.push({
                            text: "&gt; " + t("blank"),
                            iconCls: "pimcore_icon_email pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "email")
                        });
                    }

                    if (addBlankNewsletter) {
                        // empty newsletter
                        documentMenu.newsletter.push({
                            text: "&gt; " + t("blank"),
                            iconCls: "pimcore_icon_newsletter pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "newsletter")
                        });
                    }

                    //don't add pages below print containers - makes no sense
                    if (addDocuments && record.data.type != "printcontainer") {
                        menu.add(new Ext.menu.Item({
                            text: t('add_page'),
                            iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                            menu: documentMenu.page,
                            hideOnClick: false
                        }));
                    }

                    if (addPrintDocuments && record.data.type != "email" && record.data.type != "newsletter" && record.data.type != "link") {
                        menu.add(new Ext.menu.Item({
                            text: t('add_printpage'),
                            iconCls: "pimcore_icon_printpage pimcore_icon_overlay_add",
                            menu: documentMenu.printPage,
                            hideOnClick: false
                        }));

                    }

                    if (addSnippet) {
                        menu.add(new Ext.menu.Item({
                            text: t('add_snippet'),
                            iconCls: "pimcore_icon_snippet pimcore_icon_overlay_add",
                            menu: documentMenu.snippet,
                            hideOnClick: false
                        }));
                    }

                    //don't add emails, newsletters and links below print containers - makes no sense
                    if (addDocuments && record.data.type != "printcontainer") {
                        if (addLink) {
                            menu.add(new Ext.menu.Item({
                                text: t('add_link'),
                                iconCls: "pimcore_icon_link pimcore_icon_overlay_add",
                                handler: this.addDocument.bind(this, tree, record, "link")
                            }));
                        }

                        if (addEmail) {
                            menu.add(new Ext.menu.Item({
                                text: t('add_email'),
                                iconCls: "pimcore_icon_email pimcore_icon_overlay_add",
                                menu: documentMenu.email,
                                hideOnClick: false
                            }));
                        }

                        if (addNewsletter) {
                            menu.add(new Ext.menu.Item({
                                text: t('add_newsletter'),
                                iconCls: "pimcore_icon_newsletter pimcore_icon_overlay_add",
                                menu: documentMenu.newsletter,
                                hideOnClick: false
                            }));
                        }
                    }

                    if (addHardlink) {
                        menu.add(new Ext.menu.Item({
                            text: t('add_hardlink'),
                            iconCls: "pimcore_icon_hardlink pimcore_icon_overlay_add",
                            handler: this.addDocument.bind(this, tree, record, "hardlink")
                        }));
                    }
                }

                if (perspectiveCfg.inTreeContextMenu("document.addFolder")) {

                    menu.add(new Ext.menu.Item({
                        text: t('create_folder'),
                        iconCls: "pimcore_icon_folder pimcore_icon_overlay_add",
                        handler: this.addDocument.bind(this, tree, record, "folder")
                    }));
                }

                menu.add("-");


                //paste
                if (pimcore.cachedDocumentId && record.data.permissions.create && perspectiveCfg.inTreeContextMenu("document.paste")) {
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

                    pasteMenu.push({
                        text: t("paste_as_language_variant"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteLanguageDocument.bind(this, tree, record, "child")
                    });

                    pasteMenu.push({
                        text: t("paste_recursive_as_language_variant"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteLanguageDocument.bind(this, tree, record, "recursive")
                    });

                    pasteMenu.push({
                        text: t("paste_recursive_as_language_variant_updating_references"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteLanguageDocument.bind(this, tree, record, "recursive-update-references")
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

                    pasteInheritanceMenu.push({
                        text: t("paste_as_language_variant"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteLanguageDocument.bind(this, tree, record, "child", true)
                    });

                    pasteInheritanceMenu.push({
                        text: t("paste_recursive_as_language_variant"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteLanguageDocument.bind(this, tree, record, "recursive", true)
                    });

                    pasteInheritanceMenu.push({
                        text: t("paste_recursive_as_language_variant_updating_references"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteLanguageDocument.bind(this, tree, record, "recursive-update-references", true)
                    });
                }
            }


            //paste
            if (childSupportedDocument && pimcore.cutDocument && record.data.permissions.create && perspectiveCfg.inTreeContextMenu("document.pasteCut")) {
                pasteMenu.push({
                    text: t("paste_cut_element"),
                    iconCls: "pimcore_icon_paste",
                    handler: function () {
                        this.pasteCutDocument(pimcore.cutDocument,
                            pimcore.cutDocumentParentNode, record, this.tree);
                        pimcore.cutDocumentParentNode = null;
                        pimcore.cutDocument = null;
                    }.bind(this)
                });
            }

            if (pimcore.cachedDocumentId && record.data.permissions.create && perspectiveCfg.inTreeContextMenu("document.paste")) {

                if (record.data.type != "folder") {
                    pasteMenu.push({
                        text: t("paste_contents"),
                        iconCls: "pimcore_icon_paste",
                        handler: this.pasteInfo.bind(this, tree, record, "replace")
                    });
                }
            }

            if (pasteMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: "pimcore_icon_paste",
                    hideOnClick: false,
                    menu: pasteMenu
                }));
            }

            if (pasteInheritanceMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('paste_inheritance'),
                    iconCls: "pimcore_icon_paste",
                    hideOnClick: false,
                    menu: pasteInheritanceMenu
                }));
            }

            if (record.data.permissions.view && perspectiveCfg.inTreeContextMenu("document.copy")) {
                menu.add(new Ext.menu.Item({
                    text: t('copy'),
                    iconCls: "pimcore_icon_copy",
                    handler: this.copy.bind(this, tree, record)
                }));
            }

            if (record.data.id != 1 && !record.data.locked && record.data.permissions.rename && perspectiveCfg.inTreeContextMenu("document.cut")) {
                menu.add(new Ext.menu.Item({
                    text: t('cut'),
                    iconCls: "pimcore_icon_cut",
                    handler: this.cut.bind(this, tree, record)
                }));
            }

            if (record.data.permissions.rename && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("document.rename")) {
                menu.add(new Ext.menu.Item({
                    text: t('rename'),
                    iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                    handler: this.editDocumentKey.bind(this, tree, record)
                }));
            }

            //publish
            if (record.data.type != "folder" && !record.data.locked) {
                if (record.data.published && record.data.permissions.unpublish && perspectiveCfg.inTreeContextMenu("document.unpublish")) {
                    menu.add(new Ext.menu.Item({
                        text: t('unpublish'),
                        iconCls: "pimcore_icon_unpublish",
                        handler: this.publishDocument.bind(this, tree, record, 'unpublish')
                    }));
                } else if (!record.data.published && record.data.permissions.publish && perspectiveCfg.inTreeContextMenu("document.publish")) {
                    menu.add(new Ext.menu.Item({
                        text: t('publish'),
                        iconCls: "pimcore_icon_publish",
                        handler: this.publishDocument.bind(this, tree, record, 'publish')
                    }));
                }
            }


            if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("document.delete")) {
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: this.deleteDocument.bind(this, record.data.id)
                }));
            }

            if ((record.data.type == "page" || record.data.type == "hardlink") && record.data.permissions.view && perspectiveCfg.inTreeContextMenu("document.open")) {
                menu.add(new Ext.menu.Item({
                    text: t('open_in_new_window'),
                    iconCls: "pimcore_icon_open_window",
                    handler: function () {
                        window.open(record.data.url);
                    }.bind(this)
                }));
            }

            // advanced menu
            var advancedMenuItems = [];
            var user = pimcore.globalmanager.get("user");

            if (record.data.id != 1 && record.data.permissions.publish && !record.data.locked && perspectiveCfg.inTreeContextMenu("document.convert")) {
                advancedMenuItems.push(new Ext.menu.Item({
                    text: t('convert_to'),
                    iconCls: "pimcore_icon_convert",
                    hideOnClick: false,
                    menu: [{
                        text: t("page"),
                        iconCls: "pimcore_icon_page",
                        handler: this.convert.bind(this, tree, record, "page"),
                        hidden: record.data.type == "page"
                    }, {
                        text: t("snippet"),
                        iconCls: "pimcore_icon_snippet",
                        handler: this.convert.bind(this, tree, record, "snippet"),
                        hidden: record.data.type == "snippet" || !addSnippet
                    }, {
                        text: t("email"),
                        iconCls: "pimcore_icon_email",
                        handler: this.convert.bind(this, tree, record, "email"),
                        hidden: record.data.type == "email" || !addEmail
                    }, {
                        text: t("newsletter"),
                        iconCls: "pimcore_icon_newsletter",
                        handler: this.convert.bind(this, tree, record, "newsletter"),
                        hidden: record.data.type == "newsletter" || !addNewsletter
                    }, {
                        text: t("link"),
                        iconCls: "pimcore_icon_link",
                        handler: this.convert.bind(this, tree, record, "link"),
                        hidden: record.data.type == "link" || !addLink
                    }, {
                        text: t("hardlink"),
                        iconCls: "pimcore_icon_hardlink",
                        handler: this.convert.bind(this, tree, record, "hardlink"),
                        hidden: record.data.type == "hardlink" || !addHardlink
                    }]
                }));
            }

            if (childSupportedDocument && record.data.permissions.create && perspectiveCfg.inTreeContextMenu("document.searchAndMove")) {
                advancedMenuItems.push({
                    text: t('search_and_move'),
                    iconCls: "pimcore_icon_search pimcore_icon_overlay_go",
                    handler: this.searchAndMove.bind(this, tree, record)
                });
            }

            if (record.data.id != 1 && user.admin && record.data.type == "page") {
                if (!record.data.site) {
                    if (perspectiveCfg.inTreeContextMenu("document.useAsSite")) {
                        advancedMenuItems.push({
                            iconCls: "pimcore_icon_site",
                            text: t('use_as_site'),
                            handler: this.addUpdateSite.bind(this, tree, record)
                        });
                    }
                } else {
                    if (perspectiveCfg.inTreeContextMenu("document.editSite")) {
                        advancedMenuItems.push({
                            text: t('edit_site'),
                            handler: this.addUpdateSite.bind(this, tree, record),
                            iconCls: "pimcore_icon_edit",
                        });
                    }

                    if (perspectiveCfg.inTreeContextMenu("document.removeSite")) {
                        advancedMenuItems.push({
                            text: t('remove_site'),
                            handler: this.removeSite.bind(this, tree, record),
                            iconCls: "pimcore_icon_delete",
                        });
                    }
                }

            }

            if (record.data.id != 1 && user.admin) { // only admins are allowed to change locks in frontend
                var lockMenu = [];
                if (record.data.lockOwner) { // add unlock
                    if (perspectiveCfg.inTreeContextMenu("document.unlock")) {
                        lockMenu.push({
                            text: t('unlock'),
                            iconCls: "pimcore_icon_lock pimcore_icon_overlay_delete",
                            handler: function () {
                                pimcore.elementservice.lockElement({
                                    elementType: "document",
                                    id: record.data.id,
                                    mode: null
                                });
                            }.bind(this)
                        });
                    }
                } else {
                    if (perspectiveCfg.inTreeContextMenu("document.lock")) {
                        lockMenu.push({
                            text: t('lock'),
                            iconCls: "pimcore_icon_lock pimcore_icon_overlay_add",
                            handler: function () {
                                pimcore.elementservice.lockElement({
                                    elementType: "document",
                                    id: record.data.id,
                                    mode: "self"
                                });
                            }.bind(this)
                        });
                    }

                    if (perspectiveCfg.inTreeContextMenu("document.lockAndPropagate")) {
                        if (record.data.type != "snippet") {
                            lockMenu.push({
                                text: t('lock_and_propagate_to_childs'),
                                iconCls: "pimcore_icon_lock pimcore_icon_overlay_go",
                                handler: function () {
                                    pimcore.elementservice.lockElement({
                                        elementType: "document",
                                        id: record.data.id,
                                        mode: "propagate"
                                    });
                                }.bind(this)
                            });
                        }
                    }
                }

                if (record.data["locked"] && perspectiveCfg.inTreeContextMenu("document.unlockAndPropagate")) {
                    // add unlock and propagate to children functionality
                    lockMenu.push({
                        text: t('unlock_and_propagate_to_children'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_delete",
                        handler: function () {
                            pimcore.elementservice.unlockElement({
                                elementType: "document",
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
                menu.add(new Ext.menu.Item({
                    text: t('advanced'),
                    iconCls: "pimcore_icon_more",
                    hideOnClick: false,
                    menu: advancedMenuItems
                }));
            }

            if (!record.data.leaf && perspectiveCfg.inTreeContextMenu("document.reload")) {
                menu.add(new Ext.menu.Item({
                    text: t('refresh'),
                    iconCls: "pimcore_icon_reload",
                    handler: pimcore.elementservice.refreshNode.bind(this, record)
                }));
            }
        }

        pimcore.helpers.hideRedundantSeparators(menu);

        pimcore.plugin.broker.fireEvent("prepareDocumentTreeContextMenu", menu, this, record);

        menu.showAt(e.pageX+1, e.pageY+1);
    },

    pasteLanguageDocument: function (tree, record, type, enableInheritance) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_translationchecklanguage'),
            params: {
                path: pimcore.cachedDocument.data.path
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                if (data.language === "") {
                    pimcore.helpers.showNotification(t("error"), t("source_document_language_missing"), "error");
                    return false;
                }

                var languagestore = [];
                var websiteLanguages = pimcore.settings.websiteLanguages;
                var selectContent = "";

                for (var i=0; i<websiteLanguages.length; i++) {
                    if(data.language != websiteLanguages[i] && !in_array(websiteLanguages[i], data.translationLinks)) {
                        selectContent = pimcore.available_languages[websiteLanguages[i]] + " [" + websiteLanguages[i] + "]";
                        languagestore.push([websiteLanguages[i], selectContent]);
                    }
                }

                if (languagestore.length < 1) {
                    pimcore.helpers.showNotification(t("error"), t("paste_no_new_language_error"), "error");
                    return false;
                }

                var pageForm = new Ext.form.FormPanel({
                    title: t("select_language_for_new_document"),
                    border: false,
                    bodyStyle: "padding: 10px;",
                    defaults: {
                        labelWidth: 100
                    },
                    items: [{
                        xtype: "combo",
                        name: "language",
                        fieldLabel: t('language'),
                        store: languagestore,
                        editable: false,
                        triggerAction: 'all',
                        mode: "local",
                    }]
                });

                var win = new Ext.Window({
                    width: 350,
                    bodyStyle: "padding: 0px 0px 10px 0px",
                    items: [pageForm],
                    title: t("paste_as_language_variant"),
                    buttons: [{
                        text: t("cancel"),
                        iconCls: "pimcore_icon_cancel",
                        handler: function () {
                            win.close();
                        }
                    }, {
                        text: t("apply"),
                        iconCls: "pimcore_icon_apply",
                        handler: function () {
                            var params = pageForm.getForm().getFieldValues();

                            win.close();

                            this.pasteInfo(tree, record, type, enableInheritance, params.language);
                        }.bind(this)
                    }]
                });

                win.show();
            }.bind(this)
        });

    },

    populatePredefinedDocumentTypes: function(documentMenu, tree, record) {
        var document_types = pimcore.globalmanager.get("document_types_store");

        var groups = {
            page: {},
            snippet: {},
            email: {},
            newsletter: {},
            printPage: {}
        };

        document_types.sort([
            {property: 'priority', direction: 'DESC'},
            {property: 'translatedGroup', direction: 'ASC'},
            {property: 'translatedName', direction: 'ASC'}
        ]);

        document_types.each(function (documentMenu, typeRecord) {
            var text = Ext.util.Format.htmlEncode(typeRecord.get("translatedName"));
            if (typeRecord.get("type") == "page") {
                docTypeMenu = {
                    text: text,
                    iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                    handler: this.addDocument.bind(this, tree, record, "page", typeRecord.get("id"))
                };
                menuOption = "page";
            }
            else if (typeRecord.get("type") == "snippet") {
                docTypeMenu = {
                    text: text,
                    iconCls: "pimcore_icon_snippet pimcore_icon_overlay_add",
                    handler: this.addDocument.bind(this, tree, record, "snippet", typeRecord.get("id"))
                };
                menuOption = "snippet";
            } else if (typeRecord.get("type") == "email") {
                docTypeMenu = {
                    text: text,
                    iconCls: "pimcore_icon_email pimcore_icon_overlay_add",
                    handler: this.addDocument.bind(this, tree, record, "email", typeRecord.get("id"))
                };
                menuOption = "email";
            } else if (typeRecord.get("type") == "newsletter") {
                docTypeMenu = {
                    text: text,
                    iconCls: "pimcore_icon_newsletter pimcore_icon_overlay_add",
                    handler: this.addDocument.bind(this, tree, record, "newsletter", typeRecord.get("id"))
                };
                menuOption = "newsletter";
            } else if (typeRecord.get("type") == "printpage") {
                docTypeMenu = {
                    text: text,
                    iconCls: "pimcore_icon_printpage pimcore_icon_overlay_add",
                    handler: this.addDocument.bind(this, tree, record, "printpage", typeRecord.get("id"))
                };
                menuOption = "printPage";
            } else if (typeRecord.get("type") == "printcontainer") {
                docTypeMenu = {
                    text: text,
                    iconCls: "pimcore_icon_printcontainer pimcore_icon_overlay_add",
                    handler: this.addDocument.bind(this, tree, record, "printcontainer", typeRecord.get("id"))
                };
                menuOption = "printPage";
            }

            // check if the class is within a group
            if(typeRecord.get("group")) {
                if(!groups[menuOption][typeRecord.get("group")]) {
                    groups[menuOption][typeRecord.get("group")] = {
                        text: Ext.util.Format.htmlEncode(typeRecord.get("translatedGroup")),
                        iconCls: "pimcore_icon_folder",
                        hideOnClick: false,
                        menu: {
                            items: []
                        }
                    };
                    documentMenu[menuOption].push(groups[menuOption][typeRecord.get("group")]);
                }

                groups[menuOption][typeRecord.get("group")]["menu"]["items"].push(docTypeMenu);
            } else {
                documentMenu[menuOption].push(docTypeMenu);
            }

        }.bind(this, documentMenu), documentMenu);

        return documentMenu;
    },

    copy: function (tree, record) {
        pimcore.cachedDocument = record;
        pimcore.cachedDocumentId = record.data.id;
    },

    cut: function (tree, record) {
        pimcore.cutDocument = record;
        pimcore.cutDocumentParentNode = record.parentNode;
    },

    pasteCutDocument: function(document, oldParent, newParent, tree) {
        pimcore.elementservice.updateDocument(document.id, {
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
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error", t(rdata.message));

                }
            } catch(e) {
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
            }

            pimcore.elementservice.refreshNodeAllTrees("document", oldParent.id);
            pimcore.elementservice.refreshNodeAllTrees("document", newParent.id);
            newParent.expand();
            this.tree.loadMask.hide();
            this.updateOpenDocumentPaths(document);

        }.bind(this, document, newParent, oldParent, tree));

    },

    pasteInfo: function (tree, record, type, enableInheritance, language) {
        pimcore.helpers.addTreeNodeLoadingIndicator("document", record.get('id'));

        if (typeof language !== "string") {
            language = false;
        }

        if(enableInheritance !== true) {
            enableInheritance = false;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_copyinfo'),
            params: {
                targetId: record.data.id,
                sourceId: pimcore.cachedDocumentId,
                type: type,
                language: language,
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
                            this.pasteComplete(record);
                        } catch(e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_item"), "error");
                            pimcore.elementservice.refreshNodeAllTrees("document", record.id);
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if(record.pasteProgressBar) {
                            var status = currentStep / steps;
                            record.pasteProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        record.pasteWindow.close();
                        record.pasteProgressBar = null;

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_item"), "error", t(message));
                        pimcore.elementservice.refreshNodeAllTrees("document", record.id);
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
        pimcore.elementservice.refreshNodeAllTrees("document", node.id);
    },

    removeSite: function (tree, record) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_removesite'),
            method: 'DELETE',
            params: {
                id: record.data.id
            },
            success: function () {
                pimcore.globalmanager.get("sites").reload();
                pimcore.elementservice.refreshNode(record.parentNode);
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

        var title = "";

        if(record.data["site"]) {
            data = record.data["site"];
            title = t("site_id") + ": " + data["id"];
        }

        var windowCfg = {
            width: 600,
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
                    fieldLabel: t("additional_domains") + "<br /><br />" + t("wildcards_are_supported") + " (*example.com)",
                    value: data.domains.join("\n")
                }, {
                    xtype: "textfield",
                    name: "errorDocument",
                    fieldCls: "input_drop_target",
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
                                    if (data.records.length === 1 && data.records[0].data.elementType === "document" && in_array(data.records[0].data.type, ["page", "link", "hardlink"])) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }
                                },

                                onNodeDrop : function (target, dd, e, data) {

                                    if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                        return false;
                                    }

                                    data = data.records[0].data;
                                    if (data.elementType === "document" && in_array(data.type, ["page", "link", "hardlink"])) {
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
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    win.close();
                }
            }, {
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    var data = win.getComponent("form").getForm().getFieldValues();
                    data["id"] = record.id;

                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_document_document_updatesite'),
                        method: 'PUT',
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
        };

        if (title) {
            windowCfg.title = title;
        }

        var win = new Ext.Window(windowCfg);

        win.show();
    },

    addDocument : function (tree, record, type, docTypeId) {
        var textKeyTitle;
        var textKeyMessage;

        if(!is_numeric(docTypeId)) {
            docTypeId = null; // avoid sending objects or functions to the controller
        }

        if(type == "page") {

            textKeyTitle = t("add_page");
            textKeyMessage = t("enter_the_name_of_the_new_item");

            //create a custom form
            var pageForm = new Ext.form.FormPanel({
                title: textKeyMessage,
                border: false,
                bodyStyle: "padding: 10px;",
                items: [{
                    xtype: "textfield",
                    itemId: "title",
                    fieldLabel: t('title'),
                    name: 'title',
                    width: "100%",
                    enableKeyEvents: true,
                    listeners: {
                        afterrender: function () {
                            window.setTimeout(function () {
                                this.focus(true);
                            }.bind(this), 100);
                        },
                        keyup: function (el) {
                            pageForm.getComponent("name").setValue(el.getValue());
                            pageForm.getComponent("key").setValue(el.getValue());
                        }.bind(this)
                    }
                },{
                    xtype: "textfield",
                    itemId: "name",
                    fieldLabel: t('navigation'),
                    name: 'name',
                    width: "100%"
                },{
                    xtype: "textfield",
                    width: "100%",
                    fieldLabel: t('key'),
                    itemId: "key",
                    name: 'key'
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
                title: textKeyTitle,
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
                textKeyTitle = t("create_folder");
                textKeyMessage = t("enter_the_name_of_the_new_item");
            } else {
                textKeyTitle = t("add_" + type);
                textKeyMessage = t("enter_the_name_of_the_new_item");
            }

            Ext.MessageBox.prompt(textKeyTitle, textKeyMessage, function (tree, record, type, docTypeId, button, value, object) {
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
            url: '/admin/' + type + '/save?task=' + task,
            method: "PUT",
            params: parameters,
            success: function (task, response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        var options = {
                            elementType: "document",
                                id: record.data.id,
                            published: task != "unpublish"
                        };
                        pimcore.elementservice.setElementPublishedState(options);
                        pimcore.elementservice.setElementToolbarButtons(options);
                        pimcore.elementservice.reloadVersions(options);

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
            if(pimcore.elementservice.isKeyExistingInLevel(record, params["key"])) {
                return;
            }

            if(pimcore.elementservice.isDisallowedDocumentKey(record.id, params["key"])) {
                return;
            }

            params["sourceTree"] = tree;
            params["elementType"] = "document";
            params["key"] = pimcore.helpers.getValidFilename(params["key"], "document");
            params["index"] = record.childNodes.length;
            params["parentId"] = record.id;
            params["url"] = Routing.generate('pimcore_admin_document_document_add');
            pimcore.elementservice.addDocument(params);
        }
    },

    editDocumentKey: function (tree, record) {
        var options = {
            sourceTree: tree,
            elementType: "document",
            elementSubType: record.data.type,
            id: record.data.id,
            default: record.data.text
        };
        pimcore.elementservice.editElementKey(options);
    },

    deleteDocument : function (ids) {
        var options = {
            "elementType" : "document",
            "id": ids
        };
        pimcore.elementservice.deleteElement(options);
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
                        url: Routing.generate('pimcore_admin_document_document_convert'),
                        method: "PUT",
                        params: {
                            id: record.data.id,
                            type: type
                        },
                        success: function () {
                            pimcore.elementservice.refreshNodeAllTrees("document", record.parentNode.id);
                        }.bind(this)
                    });
                }
            }.bind(this, type)
        });
    },

    searchAndMove: function(tree, record) {
        var parentId = record.data.id;
        pimcore.helpers.searchAndMove(parentId, function() {
            pimcore.elementservice.refreshNode(record);
        }.bind(this), "document");
    },


    isKeyValid: function (key) {

        // key must be at least one character, an maximum 30 characters
        if (key.length < 1 && key.length > 30) {
            return false;
        }
    },

    updateOpenDocumentPaths: function(node) {
        try {
            var openTabs = pimcore.helpers.getOpenTab();
            for (var i = 0; i < openTabs.length; i++) {
                if(openTabs[i].indexOf("document_") == 0 && (openTabs[i].indexOf("_page") || openTabs[i].indexOf("_snippet") || openTabs[i].indexOf("_email") || openTabs[i].indexOf("_newsletter"))) {
                    var documentElement = pimcore.globalmanager.get(openTabs[i].replace(/_page|_snippet|_email|_newsletter/gi,''));
                    if(typeof documentElement.data != 'undefined' && documentElement.data.idPath.indexOf("/" + node.data.id) > 0) {
                        documentElement.resetPath();
                    }
                }
            }
        } catch (e) {
            console.log(e);
        }
    }
});
