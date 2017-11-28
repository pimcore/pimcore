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

pimcore.registerNS("pimcore.object.tree");
pimcore.object.tree = Class.create({

    treeDataUrl: "/admin/object/tree-get-childs-by-id",

    initialize: function (config, perspectiveCfg) {

        this.perspectiveCfg = perspectiveCfg;
        if (!perspectiveCfg) {
            this.perspectiveCfg = {
                position: "left"
            };
        }

        this.perspectiveCfg = new pimcore.perspective(this.perspectiveCfg);
        this.position = this.perspectiveCfg.position ? this.perspectiveCfg.position : "left";

        var parentPanel = Ext.getCmp("pimcore_panel_tree_" + this.position);

        if (!config) {
            this.config = {
                rootVisible: true,
                allowedClasses: null,
                loaderBaseParams: {},
                treeId: "pimcore_panel_tree_objects",
                treeIconCls: "pimcore_icon_object",
                treeTitle: t('data_objects'),
                parentPanel: parentPanel
            };
        }
        else {
            this.config = config;
        }

        pimcore.layout.treepanelmanager.register(this.config.treeId);

        // get root node config
        Ext.Ajax.request({
            url: "/admin/object/tree-get-root",
            params: {
                id: this.config.rootId,
                view: this.config.customViewId,
                elementType: "object"
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var callback = function () {
                };
                if (res["id"]) {
                    callback = this.init.bind(this, res);
                }
                pimcore.layout.treepanelmanager.initPanel(this.config.treeId, callback);
            }.bind(this)
        });
    },

    init: function (rootNodeConfig) {

        var itemsPerPage = 30;

        rootNodeConfig.text = t("home");
        rootNodeConfig.id = "" +  rootNodeConfig.id;
        rootNodeConfig.allowDrag = true;
        rootNodeConfig.iconCls = "pimcore_icon_home";
        rootNodeConfig.expanded = true;

        var store = Ext.create('pimcore.data.PagingTreeStore', {
            autoLoad: true,
            autoSync: false,
            //model: 'pimcore.data.PagingTreeModel',
            proxy: {
                type: 'ajax',
                url: "/admin/object/tree-get-childs-by-id",
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


        // objects
        this.tree = Ext.create('pimcore.tree.Panel', {
            store: store,
            region: "center",
            autoLoad: false,
            iconCls: this.config.treeIconCls,
            id: this.config.treeId,
            title: this.config.treeTitle,
            autoScroll: true,
            animate: false,
            rootVisible: true,
            bufferedRenderer: false,
            border: false,
            listeners: this.getTreeNodeListeners(),
            scrollable: true,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    appendOnly: false,
                    ddGroup: "element",
                    scrollable: true
                },
                listeners: {
                    nodedragover: this.onTreeNodeOver.bind(this)
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
            root: rootNodeConfig
        });

        store.on("nodebeforeexpand", function (node) {
            pimcore.helpers.addTreeNodeLoadingIndicator("object", node.data.id);
        });

        store.on("nodeexpand", function (node, index, item, eOpts) {
            pimcore.helpers.removeTreeNodeLoadingIndicator("object", node.data.id);
        });


        this.tree.on("afterrender", function () {
            this.tree.loadMask = new Ext.LoadMask(
                {
                    target: Ext.getCmp(this.config.treeId),
                    msg:t("please_wait")
                });
        }.bind(this));

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

                if (record.data.qtipCfg) {
                    var text = "<b>" + record.data.qtipCfg.title + "</b> | ";

                    if (record.data.qtipCfg.text) {
                        text += record.data.qtipCfg.text;
                    } else {
                        text += (t("type") + ": "+ t(record.data.type));
                    }


                    $("#pimcore_tooltip").show();
                    $("#pimcore_tooltip").html(text);

                    var offsetTabPanel = $("#pimcore_panel_tabs").offset();
                    var offsetTreeNode = $(item).offset();

                    $("#pimcore_tooltip").css({top: offsetTreeNode.top + 8, left: offsetTabPanel.left});
                }
            },
            "itemmouseleave": function () {
                $("#pimcore_tooltip").hide();
            }
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        try {
            if (record.data.permissions.view) {
                pimcore.helpers.openObject(record.data.id, record.data.type);
            }
        } catch (e) {
            console.log(e);
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
        var tree = oldParent.getOwnerTree();

        pimcore.elementservice.updateObject(node.data.id, {
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
                    pimcore.elementservice.nodeMoved("object", oldParent, newParent);
                }  else {
                    tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                        "error",t(rdata.message));
                    pimcore.elementservice.refreshNode(oldParent);
                    pimcore.elementservice.refreshNode(newParent);
                }
            } catch(e){
                tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                pimcore.elementservice.refreshNode(oldParent);
                pimcore.elementservice.refreshNode(newParent);
            }
            tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree));
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
            Ext.MessageBox.alert(t('missing_permission'), t('element_cannot_be_moved'));
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

        var menu = new Ext.menu.Menu();

        var perspectiveCfg = this.perspectiveCfg;

        var object_types = pimcore.globalmanager.get("object_types_store_create");

        var objectMenu = {
            objects: [],
            importer: [],
            ref: this
        };

        var groups = {
            importer: {},
            objects: {}
        };

        var tmpMenuEntry;
        var tmpMenuEntryImport;
        var $this = this;

        object_types.each(function (classRecord) {

            if ($this.config.allowedClasses && !in_array(classRecord.get("id"), $this.config.allowedClasses)) {
                return;
            }

            tmpMenuEntry = {
                text: classRecord.get("translatedText"),
                iconCls: "pimcore_icon_object pimcore_icon_overlay_add",
                handler: $this.addObject.bind($this, classRecord.get("id"), classRecord.get("text"), tree, record)
            };

            // add special icon
            if (classRecord.get("icon") != "/pimcore/static6/img/flat-color-icons/timeline.svg") {
                tmpMenuEntry.icon = classRecord.get("icon");
                tmpMenuEntry.iconCls = "";
            }

            tmpMenuEntryImport = {
                text: classRecord.get("translatedText"),
                iconCls: "pimcore_icon_object pimcore_icon_overlay_add",
                handler: $this.importObjects.bind($this, classRecord.get("id"), classRecord.get("text"), tree, record)
            };

            // add special icon
            if (classRecord.get("icon") != "/pimcore/static6/img/flat-color-icons/timeline.svg") {
                tmpMenuEntryImport.icon = classRecord.get("icon");
                tmpMenuEntryImport.iconCls = "";
            }


            // check if the class is within a group
            if(classRecord.get("group")) {
                if(!groups["objects"][classRecord.get("group")]) {
                    groups["objects"][classRecord.get("group")] = {
                        text: classRecord.get("group"),
                        iconCls: "pimcore_icon_folder",
                        hideOnClick: false,
                        menu: {
                            items: []
                        }
                    };
                    groups["importer"][classRecord.get("group")] = {
                        text: classRecord.get("group"),
                        iconCls: "pimcore_icon_folder",
                        hideOnClick: false,
                        menu: {
                            items: []
                        }
                    };
                    objectMenu["objects"].push(groups["objects"][classRecord.get("group")]);
                    objectMenu["importer"].push(groups["importer"][classRecord.get("group")]);
                }

                groups["objects"][classRecord.get("group")]["menu"]["items"].push(tmpMenuEntry);
                groups["importer"][classRecord.get("group")]["menu"]["items"].push(tmpMenuEntryImport);
            } else {
                objectMenu["objects"].push(tmpMenuEntry);
                objectMenu["importer"].push(tmpMenuEntryImport);
            }
        });


        var isVariant = record.data.type == "variant";

        if (record.data.permissions.create) {
            if (!isVariant) {
                if (perspectiveCfg.inTreeContextMenu("object.add")) {
                    menu.add(new Ext.menu.Item({
                        text: t('add_object'),
                        iconCls: "pimcore_icon_object pimcore_icon_overlay_add",
                        hideOnClick: false,
                        menu: objectMenu.objects
                    }));
                }
            }

            if (record.data.allowVariants && perspectiveCfg.inTreeContextMenu("object.add")) {
                menu.add(new Ext.menu.Item({
                    text: t("add_variant"),
                    iconCls: "pimcore_icon_variant",
                    handler: this.createVariant.bind(this, tree, record)
                }));
            }

            if (!isVariant) {

                if (perspectiveCfg.inTreeContextMenu("object.addFolder")) {
                    menu.add(new Ext.menu.Item({
                        text: t('add_folder'),
                        iconCls: "pimcore_icon_folder pimcore_icon_overlay_add",
                        handler: this.addFolder.bind(this, tree, record)
                    }));
                }

                if (perspectiveCfg.inTreeContextMenu("object.importCsv")) {
                    menu.add({
                        text: t('import_csv'),
                        hideOnClick: false,
                        iconCls: "pimcore_icon_object pimcore_icon_overlay_upload",
                        menu: objectMenu.importer
                    });
                }

                menu.add("-");

                //paste
                var pasteMenu = [];

                if (perspectiveCfg.inTreeContextMenu("object.paste")) {
                    if (pimcore.cachedObjectId && record.data.permissions.create) {
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


                        if (record.data.type != "folder") {
                            pasteMenu.push({
                                text: t("paste_contents"),
                                iconCls: "pimcore_icon_paste",
                                handler: this.pasteInfo.bind(this, tree, record, "replace")
                            });
                        }
                    }
                }

                if (!isVariant) {
                    if (pimcore.cutObject && record.data.permissions.create) {
                        pasteMenu.push({
                            text: t("paste_cut_element"),
                            iconCls: "pimcore_icon_paste",
                            handler: function () {
                                this.pasteCutObject(pimcore.cutObject,
                                    pimcore.cutObjectParentNode, record, this.tree);
                                pimcore.cutObjectParentNode = null;
                                pimcore.cutObject = null;
                            }.bind(this)
                        });
                    }

                    if (pasteMenu.length > 0) {
                        menu.add(new Ext.menu.Item({
                            text: t('paste'),
                            iconCls: "pimcore_icon_paste",
                            hideOnClick: false,
                            menu: pasteMenu
                        }));
                    }
                }
            }
        }

        if (!isVariant) {
            if (record.data.id != 1 && record.data.permissions.view && perspectiveCfg.inTreeContextMenu("object.copy")) {
                menu.add(new Ext.menu.Item({
                    text: t('copy'),
                    iconCls: "pimcore_icon_copy",
                    handler: this.copy.bind(this, tree, record)
                }));
            }

            //cut
            if (record.data.id != 1 && !record.data.locked && record.data.permissions.rename && perspectiveCfg.inTreeContextMenu("object.cut")) {
                menu.add(new Ext.menu.Item({
                    text: t('cut'),
                    iconCls: "pimcore_icon_cut",
                    handler: this.cut.bind(this, tree, record)
                }));
            }
        }

        //publish
        if (record.data.type != "folder" && !record.data.locked) {
            if (record.data.published && record.data.permissions.unpublish && perspectiveCfg.inTreeContextMenu("object.unpublish")) {
                menu.add(new Ext.menu.Item({
                    text: t('unpublish'),
                    iconCls: "pimcore_icon_unpublish",
                    handler: this.publishObject.bind(this, tree, record, 'unpublish')
                }));
            } else if (!record.data.published && record.data.permissions.publish && perspectiveCfg.inTreeContextMenu("object.publish")) {
                menu.add(new Ext.menu.Item({
                    text: t('publish'),
                    iconCls: "pimcore_icon_publish",
                    handler: this.publishObject.bind(this, tree, record, 'publish')
                }));
            }
        }


        if (record.data.permissions["delete"] && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("object.delete")) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.remove.bind(this, tree, record)
            }));
        }

        if (record.data.permissions.rename && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("object.rename")) {
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                handler: this.editObjectKey.bind(this, tree, record)
            }));
        }


        // advanced menu
        var advancedMenuItems = [];
        var user = pimcore.globalmanager.get("user");

        if (record.data.permissions.create && perspectiveCfg.inTreeContextMenu("object.searchAndMove")) {
            advancedMenuItems.push({
                text: t('search_and_move'),
                iconCls: "pimcore_icon_search pimcore_icon_overlay_go",
                handler: this.searchAndMove.bind(this, tree, record)
            });
        }

        if (record.data.id != 1 && user.admin) {
            var lockMenu = [];
            if (record.data.lockOwner && perspectiveCfg.inTreeContextMenu("object.unlock")) { // add unlock
                lockMenu.push({
                    text: t('unlock'),
                    iconCls: "pimcore_icon_lock pimcore_icon_overlay_delete",
                    handler: function () {
                        pimcore.elementservice.lockElement({
                            elementType: "object",
                            id: record.data.id,
                            mode: "null"
                        });
                    }.bind(this)
                });
            } else {
                if (perspectiveCfg.inTreeContextMenu("object.lock")) {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_add",
                        handler: function () {
                            pimcore.elementservice.lockElement({
                                elementType: "object",
                                id: record.data.id,
                                mode: "self"
                            });
                        }.bind(this)
                    });
                }

                if (perspectiveCfg.inTreeContextMenu("object.lockAndPropagate")) {
                    lockMenu.push({
                        text: t('lock_and_propagate_to_childs'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_go",
                        handler: function () {
                            pimcore.elementservice.lockElement({
                                elementType: "object",
                                id: record.data.id,
                                mode: "propagate"
                            });
                        }.bind(this)
                    });
                }
            }

            if(record.data.locked) {
                // add unlock and propagate to children functionality
                if (perspectiveCfg.inTreeContextMenu("object.unlockAndPropagate")) {
                    lockMenu.push({
                        text: t('unlock_and_propagate_to_children'),
                        iconCls: "pimcore_icon_lock pimcore_icon_overlay_delete",
                        handler: function () {
                            pimcore.elementservice.unlockElement({
                                elementType: "object",
                                id: record.data.id
                            });
                        }.bind(this)
                    });
                }
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

        if(advancedMenuItems.length) {
            menu.add({
                text: t('advanced'),
                iconCls: "pimcore_icon_more",
                hideOnClick: false,
                menu: advancedMenuItems
            });
        }

        if (perspectiveCfg.inTreeContextMenu("object.reload")) {
            menu.add({
                text: t('refresh'),
                iconCls: "pimcore_icon_reload",
                handler: this.reloadNode.bind(this, tree, record)
            });
        }

        pimcore.helpers.hideRedundantSeparators(menu);

        pimcore.plugin.broker.fireEvent("prepareObjectTreeContextMenu", menu, this, record);

        menu.showAt(e.pageX+1, e.pageY+1);
    },

    reloadNode: function(tree, record) {
        pimcore.elementservice.refreshNode(record);
    },

    copy: function (tree, record) {
        pimcore.cachedObjectId = record.data.id;
    },

    cut: function (tree, record) {
        pimcore.cutObject = record;
        pimcore.cutObjectParentNode = record.parentNode;
    },

    createVariant: function (tree, record) {
        Ext.MessageBox.prompt(t('add_variant'), t('please_enter_the_name_of_the_new_variant'),
            this.addVariantCreate.bind(this, tree, record));
    },

    addFolderCreate: function (tree, record, button, value, object) {

        // check for ident filename in current level
        if (pimcore.elementservice.isKeyExistingInLevel(record, value)) {
            return;
        }

        if (button == "ok") {
            var options =  {
                url: "/admin/object/add-folder",
                elementType : "object",
                sourceTree: tree,
                parentId: record.data.id,
                key: pimcore.helpers.getValidFilename(value, "object")
            };
            pimcore.elementservice.addObject(options);
        }
    },

    addObjectCreate: function (classId, className, tree, record, button, value, object) {

        if (button == "ok") {
            // check for identical filename in current level
            if (pimcore.elementservice.isKeyExistingInLevel(record, value)) {
                return;
            }

            var options = {
                url: "/admin/object/add",
                elementType: "object",
                sourceTree: tree,
                parentId: record.data.id,
                className: className,
                classId: classId,
                key: pimcore.helpers.getValidFilename(value, "object")
            };
            pimcore.elementservice.addObject(options);
        }

    },

    addVariantCreate: function (tree, record, button, value, object) {

        if (button == "ok") {
            // check for identical filename in current level

            if (pimcore.elementservice.isKeyExistingInLevel(record, value)) {
                return;
            }

            var options = {
                url: "/admin/object/add",
                elementType: "object",
                sourceTree: tree,
                className: record.data.className,
                parentId: record.data.id,
                variantViaTree: true,
                objecttype: "variant",
                key: pimcore.helpers.getValidFilename(value, "object")
            };
            pimcore.elementservice.addObject(options);
        }
    },

    addVariantComplete: function (tree, record, response) {
        try {
            var rdata = Ext.decode(response.responseText);
            if (rdata && rdata.success) {
                record.data.leaf = false;
                record.expand();

                if (rdata.id && rdata.type) {
                    if (rdata.type == "variant") {
                        pimcore.helpers.openObject(rdata.id, rdata.type);
                    }
                }
            }
            else {
                pimcore.helpers.showNotification(t("error"), t("error_creating_variant"), "error", t(rdata.message));
            }
        } catch (e) {
            pimcore.helpers.showNotification(t("error"), t("error_creating_variant"), "error");
        }
        pimcore.elementservice.refreshNode(record);
    },


    pasteCutObject: function (record, oldParent, newParent, tree) {
        pimcore.elementservice.updateObject(record.data.id, {
            parentId: newParent.id
        }, function (record, newParent, oldParent, tree, response) {
            try {
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.data.path;
                    if (newBasePath == "/") {
                        newBasePath = "";
                    }
                    record.basePath = newBasePath;
                    record.path = record.data.basePath + "/" + record.data.text;
                }
                else {
                    tree.loadMask.hide();
                    pimcore.helpers.showNotification(t("error"), t("error_moving_object"), "error", t(rdata.message));
                }
            } catch (e) {
                tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("error_moving_object"), "error");
            }
            pimcore.elementservice.refreshNodeAllTrees("object", oldParent.id);
            pimcore.elementservice.refreshNodeAllTrees("object", newParent.id);
            newParent.expand();
            tree.loadMask.hide();
        }.bind(this, record, newParent, oldParent, tree));
    },

    pasteInfo: function (tree, record, type) {
        //this.attributes.reference.tree.loadMask.show();

        pimcore.helpers.addTreeNodeLoadingIndicator("object", record.data.id);

        Ext.Ajax.request({
            url: "/admin/object/copy-info",
            params: {
                targetId: record.data.id,
                sourceId: pimcore.cachedObjectId,
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
                    layout: 'fit',
                    width: 500,
                    bodyStyle: "padding: 10px;",
                    closable: false,
                    plain: true,
                    modal: true,
                    items: [record.pasteProgressBar]
                });

                record.pasteWindow.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function () {

                        try {
                            this.pasteComplete(tree, record);
                        } catch (e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_object"), "error");
                            pimcore.elementservice.refreshNodeAllTrees("object", record.id);
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if (record.pasteProgressBar) {
                            var status = currentStep / steps;
                            record.pasteProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        record.pasteWindow.close();
                        record.pasteProgressBar = null;

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_object"), "error", t(message));

                        pimcore.elementservice.refreshNodeAllTrees("object", record.parentNode.id);
                    }.bind(this),
                    jobs: res.pastejobs
                });
            } else {
                throw "There are no pasting jobs";
            }
        } catch (e) {
            console.log(e);
            Ext.MessageBox.alert(t('error'), e);
            this.pasteComplete(tree, record);
        }
    },

    pasteComplete: function (tree, record) {
        if (record.pasteWindow) {
            record.pasteWindow.close();
        }

        record.pasteProgressBar = null;
        record.pasteWindow = null;

        //this.tree.loadMask.hide();
        pimcore.helpers.removeTreeNodeLoadingIndicator("object", record.id);
        pimcore.elementservice.refreshNodeAllTrees("object", record.id);
    },

    importObjects: function (classId, className, tree, record) {
        var importer = new pimcore.object.helpers.import.configDialog(
            {
                tree: tree,
                classId: classId,
                className: className,
                parentNode: record
            });

    },

    addObject: function (classId, className, tree, record) {
        Ext.MessageBox.prompt(sprintf(t('add_object_mbx_title'), ts(className)), t('please_enter_the_name_of_the_new_object'),
            this.addObjectCreate.bind(this, classId, className, tree, record));
    },


    addFolder: function (tree, record) {
        Ext.MessageBox.prompt(t('add_folder'), t('please_enter_the_name_of_the_new_folder'),
            this.addFolderCreate.bind(this, tree, record));
    },

    remove: function (tree, record) {
        var options = {
            "elementType" : "object",
            "id": record.data.id
        };
        pimcore.elementservice.deleteElement(options);
    },

    editObjectKey: function (tree, record) {
        var options = {
            sourceTree: tree,
            elementType: "object",
            elementSubType: record.data.type,
            id: record.data.id,
            default: record.data.text
        };
        pimcore.elementservice.editElementKey(options);
    },

    publishObject: function (tree, record, task) {

        var parameters = {};
        parameters.id = record.data.id;

        Ext.Ajax.request({
            url: '/admin/object/save?task=' + task,
            method: "post",
            params: parameters,
            success: function (tree, record, task, response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    var id = record.data.id;

                    if (rdata && rdata.success) {
                        var options = {
                            elementType: "object",
                            id: record.data.id,
                            published: task != "unpublish"
                        };

                        pimcore.elementservice.setElementPublishedState(options);
                        pimcore.elementservice.setElementToolbarButtons(options);
                        pimcore.elementservice.reloadVersions(options);

                        pimcore.helpers.showNotification(t("success"), t("successful_" + task + "_object"), "success");
                    }  else {
                        pimcore.helpers.showNotification(t("error"), t("error_" + task + "_object"), "error",
                            t(rdata.message));
                    }
                } catch (e) {
                    console.log(e);
                    pimcore.helpers.showNotification(t("error"), t("error_" + task + "_object"), "error");
                }

                //todo if open reload

            }.bind(this, tree, record, task)
        });

    },

    searchAndMove: function(tree, record) {
        pimcore.helpers.searchAndMove(record.data.id, function() {
            pimcore.elementservice.refreshNode(record);
        }.bind(this), "object");
    }
});
