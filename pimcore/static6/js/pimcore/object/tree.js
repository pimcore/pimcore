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

pimcore.registerNS("pimcore.object.tree");
pimcore.object.tree = Class.create({

    treeDataUrl: "/admin/object/tree-get-childs-by-id/",

    initialize: function (config) {

        this.position = "left";

        if (!config) {
            this.config = {
                //rootId: 1,
                rootVisible: true,
                allowedClasses: "all",
                loaderBaseParams: {},
                treeId: "pimcore_panel_tree_objects",
                treeIconCls: "pimcore_icon_object",
                treeTitle: t('objects'),
                parentPanel: Ext.getCmp("pimcore_panel_tree_left")
                //,
                //index: 3
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
                id: this.config.rootId
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
                url: "/admin/object/tree-get-childs-by-id/",
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
            //extraParams: this.config.loaderBaseParams
        });


        // objects
        this.tree = Ext.create('pimcore.tree.Panel', {
            store: store,
            border: true,
            region: "center",
            autoLoad: false,
            iconCls: this.config.treeIconCls,
            id: this.config.treeId,
            title: this.config.treeTitle,
            autoScroll: true,
            animate: true,
            rootVisible: true,
            border: false,
            listeners: this.getTreeNodeListeners(),
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    appendOnly: true,
                    ddGroup: "element"
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
            root: rootNodeConfig
        });

        //this.tree.on("render", function () {
        //    this.getRootNode().expand();
        //});
        //this.tree.on("startdrag", this.onDragStart.bind(this));
        //this.tree.on("enddrag", this.onDragEnd.bind(this));
        //this.tree.on("nodedragover", this.onTreeNodeOver.bind(this));

        this.tree.on("afterrender", function () {
            this.tree.loadMask = new Ext.LoadMask(
                {
                    target: Ext.getCmp(this.config.treeId),
                    msg:t("please_wait")
                });
        }.bind(this));

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

    onDragStart : function () {
        console.log("onDragStart");
        pimcore.helpers.treeNodeThumbnailPreviewHide();
    },

    onDragEnd : function () {
        console.log("onDragEnd");
        // nothing to do
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        try {
            if (record.data.expandable && !record.data.expanded) {
                record.expand();
            }
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
        console.log("onTreeNodeMove " + node.data.id);
        var tree = node.getOwnerTree();

        this.updateObject(tree, node, {
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

    onTreeNodeBeforeMove: function (tree, element, oldParent, newParent, index) {

        //TODO prevent move

        //// check for locks
        //if (element.attributes.locked) {
        //    Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
        //    return false;
        //}
        //
        //// check new parent's permission
        //if (!newParent.attributes.permissions.create) {
        //    Ext.MessageBox.alert(t('missing_permission'), t('element_cannot_be_moved'));
        //    return false;
        //}
        //
        //// check permissions
        //if (element.attributes.permissions.settings) {
        //    tree.loadMask.show();
        //    return true;
        //}
        return false;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();


        /**
         * case-insensitive string comparison
         * @param f_string1
         * @param f_string2
         * @returns {number}
         */
        function strcasecmp(f_string1, f_string2) {
            var string1 = (f_string1 + '').toLowerCase();
            var string2 = (f_string2 + '').toLowerCase();

            if (string1 > string2) {
                return 1;
            } else if (string1 == string2) {
                return 0;
            }

            return -1;
        }

        /**
         *
         * @param str1
         * @param str2
         * @returns {number}
         */
        function getEqual(str1, str2) {
            var count = 0;
            for (var c = 0; c < str1.length; c++) {
                if (strcasecmp(str1[c], str2[c]) !== 0)
                    break;

                count++;
            }

            if(count > 0) {
                lastSpace = str1.search(/ [^ ]*$/);

                if((lastSpace > 0) && (lastSpace < count)) {
                    count = lastSpace;
                }
            }


            if (str1[count] == " " || (typeof str1[count] == 'undefined')) {
                return count;
            } else {
                return 0;
            }
        };

        var matchCount = 3;
        var classGroups = {};
        var currentClass = '', nextClass = '', count = 0, group = '', lastGroup = '';

        var object_types = pimcore.globalmanager.get("object_types_store");
        for (var i = 0; i < object_types.getCount(); i++) {
            //
            currentClass = object_types.getAt(i);
            nextClass = object_types.getAt(i + 1);

            // check last group
            count = getEqual(lastGroup, currentClass.get("translatedText"));
            if (count <= matchCount) {
                // check new class to group with
                if (!nextClass) {
                    // this is the last class
                    count = currentClass.get("translatedText").length;
                }
                else {
                    // check next class to group with
                    count = getEqual(currentClass.get("translatedText"), nextClass.get("translatedText"));
                    if (count <= matchCount) {
                        // match is to low, use the complete name
                        count = currentClass.get("translatedText").length;
                    }
                }

                group = currentClass.get("translatedText").substring(0, count);
            }
            else {
                // use previous group
                group = lastGroup;
            }


            // add class to group
            if (!classGroups[ group ]) {
                classGroups[ group ] = [];
            }
            classGroups[ group ].push(currentClass);
            lastGroup = group;
        }
        ;


        var objectMenu = {
            objects: [],
            importer: [],
            ref: this
        };
        var tmpMenuEntry;
        var tmpMenuEntryImport;
        var record, tmp;

        for (var groupName in classGroups) {

            if (classGroups[groupName].length > 1) {
                // handle group

                tmpMenuEntry = {
                    text: groupName,
                    iconCls: "pimcore_icon_folder",
                    hideOnClick: false,
                    menu: {
                        items: []
                    }
                };
                tmpMenuEntryImport = {
                    text: groupName,
                    iconCls: "pimcore_icon_folder",
                    handler: this.importObjects.bind(this, classGroups[groupName][0].get("id"), classGroups[groupName][0].get("text")),
                    menu: {
                        items: []
                    }
                };

                // add items
                for (var i = 0; i < classGroups[groupName].length; i++) {
                    classGroupRecord = classGroups[groupName][i];
                    if (this.config.allowedClasses == "all" || in_array(classGroupRecord.get("id"),  this.config.allowedClasses)) {

                        /* == menu entry: create new object == */

                        // create menu item
                        tmp = {
                            text: classGroupRecord.get("translatedText"),
                            iconCls: "pimcore_icon_object_add",
                            handler: this.addObject.bind(this, classGroupRecord.get("id"), classGroupRecord.get("text"), tree, record)
                        };

                        // add special icon
                        if (classGroupRecord.get("icon")) {
                            tmp.icon = classGroupRecord.get("icon");
                            tmp.iconCls = "";
                        }

                        tmpMenuEntry.menu.items.push(tmp);


                        /* == menu entry: import object == */

                        // create menu item
                        tmp = {
                            text: classGroupRecord.get("translatedText"),
                            iconCls: "pimcore_icon_object_import",
                            handler: this.importObjects.bind(this, classGroupRecord.get("id"), classGroupRecord.get("text"), tree, record)
                        };

                        // add special icon
                        if (classGroupRecord.get("icon")) {
                            tmp.icon = classGroupRecord.get("icon");
                            tmp.iconCls = "";
                        }

                        tmpMenuEntryImport.menu.items.push(tmp);
                    }
                }

                objectMenu.objects.push(tmpMenuEntry);
                objectMenu.importer.push(tmpMenuEntryImport);
            }  else {
                classGroupRecord = classGroups[groupName][0];

                if (this.config.allowedClasses == "all" || in_array(classGroupRecord.get("id"),
                        this.config.allowedClasses)) {

                    /* == menu entry: create new object == */
                    tmpMenuEntry = {
                        text: classGroupRecord.get("translatedText"),
                        iconCls: "pimcore_icon_object_add",
                        handler: this.addObject.bind(this, classGroupRecord.get("id"), classGroupRecord.get("text"), tree, record)
                    };

                    if (classGroupRecord.get("icon")) {
                        tmpMenuEntry.icon = classGroupRecord.get("icon");
                        tmpMenuEntry.iconCls = "";
                    }

                    objectMenu.objects.push(tmpMenuEntry);


                    /* == menu entry: import object == */
                    tmpMenuEntryImport = {
                        text: classGroupRecord.get("translatedText"),
                        iconCls: "pimcore_icon_object_import",
                        handler: this.importObjects.bind(this, classGroupRecord.get("id"), classGroupRecord.get("text"), tree, record)
                    };

                    if (classGroupRecord.get("icon")) {
                        tmpMenuEntryImport.icon = classGroupRecord.get("icon");
                        tmpMenuEntryImport.iconCls = "";
                    }

                    objectMenu.importer.push(tmpMenuEntryImport);
                }
            }
        };

        var isVariant = record.data.type == "variant";

        if (record.data.permissions.create) {
            if (!isVariant) {
                menu.add(new Ext.menu.Item({
                    text: t('add_object'),
                    iconCls: "pimcore_icon_object_add",
                    hideOnClick: false,
                    menu: objectMenu.objects
                }));
            }

            if (record.data.allowVariants) {
                menu.add(new Ext.menu.Item({
                    text: t("add_variant"),
                    iconCls: "pimcore_icon_tree_variant",
                    handler: this.createVariant.bind(this, tree, record)
                }));
            }

            if (!isVariant) {
                //if (this.attributes.type == "folder") {
                menu.add(new Ext.menu.Item({
                    text: t('add_folder'),
                    iconCls: "pimcore_icon_folder_add",
                    handler: this.addFolder.bind(this, tree, record)
                }));
                //}


                menu.add({
                    text: t('import_csv'),
                    hideOnClick: false,
                    iconCls: "pimcore_icon_object_csv_import",
                    menu: objectMenu.importer
                });

                //paste
                var pasteMenu = [];

                if (this.cacheObjectId && record.data.permissions.create) {
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
                if (this.cutObject && record.data.permissions.create) {
                    pasteMenu.push({
                        text: t("paste_cut_element"),
                        iconCls: "pimcore_icon_paste",
                        handler: function () {
                            this.pasteCutObject(this.cutObject,
                                       this.cutParentNode, record, this.tree);
                     this.cutParentNode = null;
                            this.cutObject = null;
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

        if (!isVariant) {
            if (record.data.id != 1 && record.data.permissions.view) {
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
        }

        //publish
        if (record.data.type != "folder" && !record.data.locked) {
            if (record.data.published && record.data.permissions.unpublish) {
                menu.add(new Ext.menu.Item({
                    text: t('unpublish'),
                    iconCls: "pimcore_icon_tree_unpublish",
                    handler: this.publishObject.bind(this, tree, record, 'unpublish')
                }));
            } else if (!record.data.published && record.data.permissions.publish) {
                menu.add(new Ext.menu.Item({
                    text: t('publish'),
                    iconCls: "pimcore_icon_tree_publish",
                    handler: this.publishObject.bind(this, tree, record, 'publish')
                }));
            }
        }


        if (record.data.permissions["delete"] && record.data.id != 1 && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.remove.bind(this, tree, record)
            }));
        }

        if (record.data.permissions.create) {
            menu.add(new Ext.menu.Item({
                text: t('search_and_move'),
                iconCls: "pimcore_icon_search_and_move",
                handler: this.searchAndMove.bind(this, tree, record)
            }));
        }

        if (record.data.permissions.rename && this.id != 1 && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_edit_key",
                handler: this.editKey.bind(this, tree, record)
            }));
        }


        if (this.id != 1) {
            var user = pimcore.globalmanager.get("user");
            if (user.admin) { // only admins are allowed to change locks in frontend

                var lockMenu = [];
                if (record.data.lockOwner) { // add unlock
                    lockMenu.push({
                        text: t('unlock'),
                        iconCls: "pimcore_icon_lock_delete",
                        handler: function () {
                            this.updateObject(tree, record, {locked: null}, function () {
                                this.refresh(this.tree.getRootNode());
                            }.bind(this));
                        }.bind(this)
                    });
                } else {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock_add",
                        handler: function () {
                            try {
                                this.updateObject(tree, record, {locked: "self"}, function () {
                                    this.refresh(this.tree.getRootNode());
                                }.bind(this));
                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this)
                    });

                    lockMenu.push({
                        text: t('lock_and_propagate_to_childs'),
                        iconCls: "pimcore_icon_lock_add_propagate",
                        handler: function () {
                            try {
                                this.updateObject(tree, record, {locked: "propagate"},
                                    function () {
                                        this.refresh(this.tree.getRootNode());
                                    }.bind(this));
                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this)
                    });
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
                                    type: "object"
                                },
                                success: function () {
                                    this.refresh(record.parentNode);
                                }.bind(this)
                            });
                        }.bind(this)
                    });
                }

                menu.add(new Ext.menu.Item({
                    text: t('lock'),
                    iconCls: "pimcore_icon_lock",
                    hideOnClick: false,
                    menu: lockMenu
                }));
            }
        }



        menu.add(new Ext.menu.Item({
            text: t('refresh'),
            iconCls: "pimcore_icon_reload",
            handler: this.reloadNode.bind(this, tree, record)
        }));


        menu.showAt(e.pageX, e.pageY);
    },

    reloadNode: function(tree, record) {
        this.refresh(record);
    },

    copy: function (tree, record) {
        this.cacheObjectId = record.data.id;
    },

    cut: function (tree, record) {
        this.cutObject = record;
        this.cutParentNode = record.parentNode;
    },

    createVariant: function (tree, record) {
        Ext.MessageBox.prompt(t('add_variant'), t('please_enter_the_name_of_the_new_variant'),
            this.addVariantCreate.bind(this, tree, record));
    },

    addVariantCreate: function (tree, record, button, value, object) {

        // check for identical filename in current level
        if (this.isExistingKeyInLevel(record, value)) {
            return;
        }

        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/object/add",
                params: {
                    className: record.data.className,
                    variantViaTree: true,
//                    classId: this.element.data.general.o_classId,
                    parentId: record.data.id,
                    objecttype: "variant",
                    key: pimcore.helpers.getValidFilename(value)
                },
                success: this.addVariantComplete.bind(this, tree, record)
            });

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
        this.refresh(record);
    },


    pasteCutObject: function (record, oldParent, newParent, tree) {
        this.updateObject(tree, record, {
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
            this.refresh(oldParent);
            this.refresh(newParent);

            tree.loadMask.hide();
        }.bind(this, record, newParent, oldParent, tree));
    },

    pasteInfo: function (tree, record, type) {
        //this.attributes.reference.tree.loadMask.show();

        pimcore.helpers.addTreeNodeLoadingIndicator("object", record.data.id);

        Ext.Ajax.request({
            url: "/admin/object/copy-info/",
            params: {
                targetId: record.data.id,
                sourceId: this.cacheObjectId,
                type: type
            },
            success: this.paste.bind(this, tree, record)
        });
    },

    paste: function (tree, record, response) {

        try {
            var res = Ext.decode(response.responseText);

            if (res.pastejobs) {

                this.pasteProgressBar = new Ext.ProgressBar({
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
                    items: [this.pasteProgressBar]
                });

                record.pasteWindow.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function () {

                        try {
                            this.pasteComplete(tree, record);
                        } catch (e) {
                            console.log(e);
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_object"), "error");
                            this.refresh(record);
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if (this.pasteProgressBar) {
                            var status = currentStep / steps;
                            this.pasteProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        record.pasteWindow.close();
                        record.pasteProgressBar = null;

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_object"), "error", t(message));

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
        this.refresh(record);
    },

    importObjects: function (classId, className, tree, record) {
        new pimcore.object.importer(tree, record.parentNode, classId, className);
    },

    addObject: function (classId, className, tree, record) {
        Ext.MessageBox.prompt(t('add_object'), t('please_enter_the_name_of_the_new_object'),
            this.addObjectCreate.bind(this, classId, className, tree, record));
    },

    addObjectCreate: function (classId, className, tree, record, button, value, object) {

        if (button == "ok") {
            // check for identical filename in current level
            if (this.isExistingKeyInLevel(record, value)) {
                return;
            }

            Ext.Ajax.request({
                url: "/admin/object/add",
                params: {
                    className: className,
                    classId: classId,
                    parentId: record.data.id,
                    key: pimcore.helpers.getValidFilename(value)
                },
                success: this.addObjectComplete.bind(this, tree, record)
            });
        }
    },

    addFolder: function (tree, record) {
        Ext.MessageBox.prompt(t('add_folder'), t('please_enter_the_name_of_the_new_folder'),
            this.addFolderCreate.bind(this, tree, record));
    },

    addFolderCreate: function (tree, record, button, value, object) {

        // check for ident filename in current level
        if (this.isExistingKeyInLevel(record, value)) {
            return;
        }

        if (button == "ok") {

            Ext.Ajax.request({
                url: "/admin/object/add-folder",
                params: {
                    parentId: record.data.id,
                    key: pimcore.helpers.getValidFilename(value)
                },
                success: this.addObjectComplete.bind(this, tree, record)
            });
        }
    },

    addObjectComplete: function (tree, record, response) {
        try {
            var rdata = Ext.decode(response.responseText);
            if (rdata && rdata.success) {
                this.leaf = false;
                tree.expand(record);

                if (rdata.id && rdata.type) {
                    if (rdata.type == "object") {
                        pimcore.helpers.openObject(rdata.id, rdata.type);
                    }
                }
            }
            else {
                pimcore.helpers.showNotification(t("error"), t("error_creating_object"), "error", t(rdata.message));
            }
        } catch (e) {
            pimcore.helpers.showNotification(t("error"), t("error_creating_object"), "error");
        }
        this.reloadNode(tree, record);
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

    remove: function (tree, record) {
        pimcore.helpers.deleteObject(record.data.id);
    },

    editKey: function (tree, record) {
        Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'),
            this.editKeyComplete.bind(this, tree, record), window, false, record.data.text);
    },

    editKeyComplete: function (tree, record, button, value, object) {
        if (button == "ok") {

        // check for ident filename in current level
        if (this.isExistingKeyInLevel(record.parentNode, value, record)) {
            return;
        }

            value = pimcore.helpers.getValidFilename(value);

            record.set("text", value);
            record.data.path = record.data.basePath + value;

            var tree = record.getOwnerTree();
            tree.loadMask.show();

            this.updateObject(tree, record, {key: value}, function (response) {

                record.getOwnerTree().loadMask.hide();
                this.refresh(record);

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        if (pimcore.globalmanager.exists("object_" + record.id)) {
                            pimcore.helpers.closeObject(record.data.id);
                            pimcore.helpers.openObject(record.data.id, record.data.type);
                        }
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_object"), "error",
                            t(rdata.message));
                        this.refresh(record.parentNode);
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_object"), "error");
                    this.refresh(record.parentNode);
                }
            }.bind(this));
        }
    },

    publishObject: function (tree, record, task) {

        var parameters = {};
        parameters.id = record.data.id;

        Ext.Ajax.request({
            url: '/admin/object/save/task/' + task,
            method: "post",
            params: parameters,
            success: function (tree, record, task, response) {
                try {
                    var ownerTree = record.getOwnerTree();
                    var view = ownerTree.getView();
                    var nodeEl = Ext.fly(view.getNodeByRecord(record));

                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {

                        if (task == 'unpublish') {
                            nodeEl.addCls('pimcore_unpublished');
                            record.data.published = false;
                            if (pimcore.globalmanager.exists("object_" + record.data.id)) {
                                pimcore.globalmanager.get("object_" + record.data.id).toolbarButtons.unpublish.hide();
                            }

                        } else {
                            nodeEl.removeCls('pimcore_unpublished');

                            record.data.published = true;
                            if (pimcore.globalmanager.exists("object_" + record.data.id)) {
                                pimcore.globalmanager.get("object_" + record.data.id).toolbarButtons.unpublish.show();
                            }
                        }

                        if (pimcore.globalmanager.exists("object_" + record.data.id)) {
                            // reload versions
                            if (pimcore.globalmanager.get("object_" + record.data.id).versions) {
                                if (typeof pimcore.globalmanager.get("object_" + record.data.id).versions.reload
                                    == "function") {
                                    pimcore.globalmanager.get("object_" + record.data.id).versions.reload();
                                }
                            }
                        }

                        pimcore.helpers.showNotification(t("success"), t("successful_" + task + "_object"), "success");
                    }
                    else {
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
        pimcore.helpers.searchAndMove(record.parentId, function() {
            this.refresh(record);
        }.bind(this), "object");
    },

    updateObject: function (tree, record, values, callback) {

        if (!callback) {
            callback = function () {
            };
        }

        Ext.Ajax.request({
            url: "/admin/object/update",
            method: "post",
            params: {
                id: record.data.id,
                values: Ext.encode(values)
            },
            success: callback
        });
    },

    refresh: function (record) {
        var ownerTree = record.getOwnerTree();

        record.data.expanded = true;
        ownerTree.getStore().load({
            node: record
        });
    }
});