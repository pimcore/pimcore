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

pimcore.registerNS("pimcore.object.tree");
pimcore.object.tree = Class.create({

    treeDataUrl: "/admin/object/tree-get-childs-by-id/",

    initialize: function(config) {
        
        if (!config) {
            this.config = {
                rootId: 1,
                rootVisible: true,
                allowedClasses: "all",
                loaderBaseParams: {},
                treeId: "pimcore_panel_tree_objects",
                treeIconCls: "pimcore_icon_object",
                treeTitle: t('objects'),
                parentPanel: Ext.getCmp("pimcore_panel_tree"),
                index: 3
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
                pimcore.layout.treepanelmanager.initPanel(this.config.treeId, this.init.bind(this, response));
            }.bind(this)
        });
    },

    init: function(rootNodeRaw) {
        
        // get root-node config & define special values
        var rootNodeConfig = Ext.decode(rootNodeRaw.responseText);

        rootNodeConfig.nodeType = "async";
        rootNodeConfig.text = "home";
        rootNodeConfig.draggable = true;
        rootNodeConfig.iconCls = "pimcore_icon_home";

        // documents
        this.tree = new Ext.tree.TreePanel({
            region: "center",
            useArrows:true,
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            autoScroll:true,
            animate:true,
            enableDD:true,
            ddAppendOnly: true,
            ddGroup: "element",
            containerScroll: true,
            rootVisible: this.config.rootVisible,
            border: false,
            root: rootNodeConfig,
            plugins: new Ext.ux.tree.TreeNodeMouseoverPlugin(),
            loader: new Ext.ux.tree.PagingTreeLoader({
                dataUrl:this.treeDataUrl,
                pageSize:30,
                enableTextPaging:false,
                pagingModel:'remote',
                requestMethod: "GET",
                baseAttrs: {
                    listeners: this.getTreeNodeListeners(),
                    reference: this,
                    nodeType: "async"
                },
                baseParams: this.config.loaderBaseParams
            })
        });

        this.tree.on("render", function () {
            this.getRootNode().expand();
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

    onDragStart : function () {
        pimcore.helpers.dndMaskFrames();
    },

    onDragEnd : function () {
        pimcore.helpers.dndUnmaskFrames();
    },

    onTreeNodeClick: function () {
        pimcore.helpers.openObject(this.id, this.attributes.type);
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

        this.attributes.reference.updateObject(this.id, {
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
                    pimcore.helpers.showNotification(t("error"), t("error_moving_object"), "error", t(rdata.message));
                    oldParent.reload();
                    newParent.reload();
                }
            } catch(e) {
                tree.loadMask.hide();
                pimcore.helpers.showNotification(t("error"), t("error_moving_object"), "error");
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

        // check permissions
        if (element.attributes.permissions.settings) {
            tree.loadMask.show();
            return true;
        }
        return false;
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var menu = new Ext.menu.Menu();


        var object_types = pimcore.globalmanager.get("object_types_store");

        var objectMenu = {
            objects: [],
            importer: [],
            ref: this
        };
        var tmpMenuEntry;
        var tmpMenuEntryImport;

        object_types.each(function(record) {

            if (this.ref.attributes.reference.config.allowedClasses == "all" || in_array(record.get("id"), this.ref.attributes.reference.config.allowedClasses)) {
                // for create new object
                tmpMenuEntry = {
                    text: record.get("translatedText"),
                    iconCls: "pimcore_icon_object_add",
                    handler: this.ref.attributes.reference.addObject.bind(this.ref, record.get("id"), record.get("text"))
                };
                if (record.get("icon")) {
                    tmpMenuEntry.icon = record.get("icon");
                    tmpMenuEntry.iconCls = "";
                }
                this.objects.push(tmpMenuEntry);

                // for import objects
                tmpMenuEntryImport = {
                    text: record.get("translatedText"),
                    iconCls: "pimcore_icon_object_import",
                    handler: this.ref.attributes.reference.importObjects.bind(this.ref, record.get("id"), record.get("text"))
                };
                if (record.get("icon")) {
                    tmpMenuEntryImport.icon = record.get("icon");
                    tmpMenuEntryImport.iconCls = "";
                }
                this.importer.push(tmpMenuEntryImport);
            }

        }, objectMenu);


        if (this.attributes.permissions.create) {
            menu.add(new Ext.menu.Item({
                text: t('add_object'),
                iconCls: "pimcore_icon_object_add",
                hideOnClick: false,
                menu: objectMenu.objects
            }));


            //if (this.attributes.type == "folder") {
                menu.add(new Ext.menu.Item({
                    text: t('add_folder'),
                    iconCls: "pimcore_icon_folder_add",
                    handler: this.attributes.reference.addFolder.bind(this)
                }));
            //}


            menu.add(new Ext.menu.Item({
                text: t('import'),
                iconCls: "pimcore_icon_object_import",
                hideOnClick: false,
                menu: [{
                    text: t('import_archive'),
                    iconCls: "pimcore_icon_archive_import",
                    handler: function(){
                        new pimcore.element.importer("object",this.id);
                    }.bind(this)
                },{
                    text: t('import_csv'),
                    hideOnClick: false,
                    iconCls: "pimcore_icon_object_csv_import",
                    menu:objectMenu.importer
                }]
            }));

            menu.add(new Ext.menu.Item({
                text: t('export_archive'),
                iconCls: "pimcore_icon_archive_export",
                handler: function(){
                       new pimcore.element.exporter("object",this.id);
                    }.bind(this)

            }));


            //paste

            var pasteMenu = [
                {
                    text: t("paste_recursive_as_childs"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "recursive")
                },
                {
                    text: t("paste_as_child"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "child")
                }
            ];

            if (this.attributes.type != "folder") {
                pasteMenu.push({
                    text: t("paste_contents"),
                    iconCls: "pimcore_icon_paste",
                    handler: this.attributes.reference.pasteInfo.bind(this, "replace")
                });
            }

            if (this.attributes.reference.cacheObjectId && this.attributes.permissions.create) {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: "pimcore_icon_paste",
                    hideOnClick: false,
                    menu: pasteMenu
                }));
            }
        }

        if (this.id != 1) {
            menu.add(new Ext.menu.Item({
                text: t('copy'),
                iconCls: "pimcore_icon_copy",
                handler: this.attributes.reference.copy.bind(this)
            }));
        }

        //publish
        if (this.attributes.permissions.publish && this.attributes.type != "folder" && !this.attributes.locked) {
            if (this.attributes.published) {
                menu.add(new Ext.menu.Item({
                    text: t('unpublish'),
                    iconCls: "pimcore_icon_tree_unpublish",
                    handler: this.attributes.reference.publishObject.bind(this, this.attributes.id, 'unpublish')
                }));
            } else {
                menu.add(new Ext.menu.Item({
                    text: t('publish'),
                    iconCls: "pimcore_icon_tree_publish",
                    handler: this.attributes.reference.publishObject.bind(this, this.attributes.id, 'publish')
                }));
            }

        }


        if (this.attributes.permissions["delete"] && this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.attributes.reference.remove.bind(this)
            }));
        }

        if (this.attributes.permissions.rename && this.id != 1 && !this.attributes.locked) {
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_edit_key",
                handler: this.attributes.reference.editKey.bind(this)
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
                            this.attributes.reference.updateObject(this.attributes.id, {locked: null}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
                        }.bind(this)
                    });
                } else {
                    lockMenu.push({
                        text: t('lock'),
                        iconCls: "pimcore_icon_lock_add",
                        handler: function () {
                            this.attributes.reference.updateObject(this.attributes.id, {locked: "self"}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
                        }.bind(this)
                    });
                    
                    lockMenu.push({
                        text: t('lock_and_propagate_to_childs'),
                        iconCls: "pimcore_icon_lock_add_propagate",
                        handler: function () {
                            this.attributes.reference.updateObject(this.attributes.id, {locked: "propagate"}, function () {
                                this.attributes.reference.tree.getRootNode().reload();
                            }.bind(this))
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
        this.attributes.reference.cacheObjectId = this.id;
    },

    pasteInfo: function (type) {
        //this.attributes.reference.tree.loadMask.show();

        pimcore.helpers.addTreeNodeLoadingIndicator("object", this.id);

        Ext.Ajax.request({
            url: "/admin/object/copy-info/",
            params: {
                targetId: this.id,
                sourceId: this.attributes.reference.cacheObjectId,
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
                            pimcore.helpers.showNotification(t("error"), t("error_pasting_object"), "error");
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

                        pimcore.helpers.showNotification(t("error"), t("error_pasting_object"), "error", t(message));
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
        pimcore.helpers.removeTreeNodeLoadingIndicator("object", node.id);
        node.reload();
    },

    importObjects: function (classId, className) {
        new pimcore.object.importer(this, classId, className);
    },

    addObject : function (classId, className) {
        Ext.MessageBox.prompt(t('add_object'), t('please_enter_the_name_of_the_new_object'), this.attributes.reference.addObjectCreate.bind(this, classId, className));
    },

    addObjectCreate: function (classId, className, button, value, object) {

        // check for ident filename in current level
        if(this.attributes.reference.isExistingKeyInLevel(this, value)) {
            return;
        }

        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/object/add",
                params: {
                    className: className,
                    classId: classId,
                    parentId: this.id,
                    key: pimcore.helpers.getValidFilename(value)
                },
                success: this.attributes.reference.addObjectComplete.bind(this)
            });
        }
    },

    addFolder : function (classId, className) {
        Ext.MessageBox.prompt(t('add_object'), t('please_enter_the_name_of_the_new_object'), this.attributes.reference.addFolderCreate.bind(this));
    },

    addFolderCreate: function (button, value, object) {

        // check for ident filename in current level
        if(this.attributes.reference.isExistingKeyInLevel(this, value)) {
            return;
        }

        if (button == "ok") {

            Ext.Ajax.request({
                url: "/admin/object/add-folder",
                params: {
                    parentId: this.id,
                    key: pimcore.helpers.getValidFilename(value)
                },
                success: this.attributes.reference.addObjectComplete.bind(this)
            });
        }
    },

    addObjectComplete: function (response) {
        try {
            var rdata = Ext.decode(response.responseText);
            if (rdata && rdata.success) {
                this.leaf = false;
                this.expand();

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
        this.reload();
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

    remove : function () {

        // check for dependencies
        Ext.Ajax.request({
            url: "/admin/object/delete-info/",
            params: {id: this.id},
            success: this.attributes.reference.deleteCheckDependencyComplete.bind(this)
        });
    },

    deleteCheckDependencyComplete: function (response) {

        try {
            var res = Ext.decode(response.responseText);
            var rm = this.attributes.reference.deleteObjectFromServer.bind(this,res);
            var message = t('delete_message');
            if (res.hasDependencies) {
                var message = t('delete_message_dependencies');
            }
            Ext.MessageBox.show({
                    title:t('delete'),
                    msg: message,
                    buttons: Ext.Msg.OKCANCEL ,
                    icon: Ext.MessageBox.INFO ,
                    fn: function(buttonId){
                        if(buttonId == "ok"){
                            rm();
                        }
                    }
                });
        }
        catch (e) {
        }
    },

    deleteObjectFromServer: function (r) {

        if (r.deletejobs) {
            
            pimcore.helpers.addTreeNodeLoadingIndicator("object", this.id);
            this.getUI().addClass("pimcore_delete");
            /*this.originalClass = Ext.get(this.getUI().getIconEl()).getAttribute("class");
             Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", "x-tree-node-icon pimcore_icon_loading");*/


            if (pimcore.globalmanager.exists("object_" + this.id)) {
                var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                tabPanel.remove("object_" + this.id);
            }

            if(r.deletejobs.length > 2) {
                this.deleteProgressBar = new Ext.ProgressBar({
                    text: t('initializing')
                });

                this.deleteWindow = new Ext.Window({
                    title: t("delete"),
                    layout:'fit',
                    width:500,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    modal: true,
                    items: [this.deleteProgressBar]
                });

                this.deleteWindow.show();
            }


            var pj = new pimcore.tool.paralleljobs({
                success: function () {

                    try {
                        this.getUI().removeClass("pimcore_delete");
                        //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                        pimcore.helpers.removeTreeNodeLoadingIndicator("object", this.id);
                        this.remove();
                    } catch(e) {
                        console.log(e);
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_object"), "error");
                        this.parentNode.reload();
                    }

                    if(this.deleteWindow) {
                        this.deleteWindow.close();
                    }
                    
                    this.deleteProgressBar = null;
                    this.deleteWindow = null;
                }.bind(this),
                update: function (currentStep, steps, percent) {
                    if(this.deleteProgressBar) {
                        var status = currentStep / steps;
                        this.deleteProgressBar.updateProgress(status, percent + "%");
                    }
                }.bind(this),
                failure: function (message) {
                    this.deleteWindow.close();

                    pimcore.helpers.showNotification(t("error"), t("error_deleting_object"), "error", t(message));
                    this.parentNode.reload();
                }.bind(this),
                jobs: r.deletejobs
            });
        }
    },


    editKey: function () {
        Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'), this.attributes.reference.editKeyComplete.bind(this), null, null, this.text);
    },

    editKeyComplete: function (button, value, object) {

        // check for ident filename in current level
        if(this.attributes.reference.isExistingKeyInLevel(this.parentNode, value, this)) {
            return;
        }

        if (button == "ok") {

            // check for ident filename in current level
            var parentChilds = this.parentNode.childNodes;
            for (var i = 0; i < parentChilds.length; i++) {
                if (parentChilds[i].text == value && this != parentChilds[i]) {
                    Ext.MessageBox.alert(t('rename'), t('the_filename_is_already_in_use'));
                    return;
                }
            }

            // validate filename
            /*if(pimcore.helpers.isValidFilename(value) == false) {
             Ext.MessageBox.alert(t('rename'), t('filename_not_valid'));
             return;
             }*/

            value = pimcore.helpers.getValidFilename(value);

            this.setText(value);
            this.attributes.path = this.attributes.basePath + value;

            this.getOwnerTree().loadMask.show();

            this.attributes.reference.updateObject(this.id, {key: value}, function (response) {

                this.getOwnerTree().loadMask.hide();
                this.reload();

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        if (pimcore.globalmanager.exists("object_" + this.id)) {
                            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                            var tabId = "object_" + this.id;
                            tabPanel.remove(tabId);
                            pimcore.globalmanager.remove("object_" + this.id);

                            pimcore.helpers.openObject(this.id, this.attributes.type);
                        }
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_object"), "error", t(rdata.message));
                        this.parentNode.reload();
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_object"), "error");
                    this.parentNode.reload();
                }
            }.bind(this));
        }
    },

    publishObject: function (id, task) {

        var parameters = {};
        parameters.id = id;

        Ext.Ajax.request({
            url: '/admin/object/save/task/' + task,
            method: "post",
            params: parameters,
            success: function (task, response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {

                        if (task == 'unpublish') {
                            this.setCls('pimcore_unpublished');
                            this.attributes.published = false;
                            if (pimcore.globalmanager.exists("object_" + this.id)) {
                                pimcore.globalmanager.get("object_" + this.id).toolbarButtons.unpublish.hide();
                            }

                        } else {
                            this.setCls('');
                            this.attributes.published = true;
                            if (pimcore.globalmanager.exists("object_" + this.id)) {
                                pimcore.globalmanager.get("object_" + this.id).toolbarButtons.unpublish.show();
                            }
                        }

                        if (pimcore.globalmanager.exists("object_" + this.id)) {
                            // reload versions
                            if (pimcore.globalmanager.get("object_" + this.id).versions) {
                                if (typeof pimcore.globalmanager.get("object_" + this.id).versions.reload == "function") {
                                    pimcore.globalmanager.get("object_" + this.id).versions.reload();
                                }
                            }
                        }

                        pimcore.helpers.showNotification(t("success"), t("successful_" + task + "_object"), "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_" + task + "_object"), "error", t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_" + task + "_object"), "error");
                }

                //todo if open reload

            }.bind(this, task)
        });

    },

    updateObject: function (id, values, callback) {

        if (!callback) {
            callback = function() {
            };
        }

        Ext.Ajax.request({
            url: "/admin/object/update",
            method: "post",
            params: {
                id: id,
                values: Ext.encode(values)
            },
            success: callback
        });
    }
});