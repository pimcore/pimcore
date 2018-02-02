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

pimcore.registerNS("pimcore.elementservice.x");

pimcore.elementservice.deleteElement = function (options) {
    var elementType = options.elementType;
    var url = "/admin/"  + elementType + "/delete-info?";
    // check for dependencies
    Ext.Ajax.request({
        url: url,
        params: {id: options.id},
        success: pimcore.elementservice.deleteElementCheckDependencyComplete.bind(window, options)
    });
};

pimcore.elementservice.deleteElementCheckDependencyComplete = function (options, response) {

    try {
        var res = Ext.decode(response.responseText);
        var message = res.batchDelete ? t('delete_message_batch') : t('delete_message');
        if (res.elementKey) {
            message += "<br /><b style='display: block; text-align: center; padding: 10px 0;'>\"" + res.elementKey + "\"</b>";
        }
        if (res.hasDependencies) {
            message += "<br />" + t('delete_message_dependencies');
        }

        if(res["childs"] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
        }

        var deleteMethod = "delete" + ucfirst(options.elementType) + "FromServer";

        Ext.MessageBox.show({
            title:t('delete'),
            msg: message,
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: pimcore.elementservice.deleteElementFromServer.bind(window, res, options)
        });
    }
    catch (e) {
        console.log(e);
    }
};


pimcore.elementservice.getElementTreeNames = function(elementType) {
    var treeNames = ["layout_" + elementType + "_tree"]
    if (pimcore.settings.customviews.length > 0) {
        for (var cvs = 0; cvs < pimcore.settings.customviews.length; cvs++) {
            var cv = pimcore.settings.customviews[cvs];
            if (!cv.treetype && elementType == "object" || cv.treetype == elementType) {
                treeNames.push("layout_" + elementType + "_tree_" + cv.id);
            }
        }
    }
    return treeNames;
};

pimcore.elementservice.deleteElementFromServer = function (r, options, button) {

    if (button == "ok" && r.deletejobs) {
        var successHandler = options["success"];
        var elementType = options.elementType;
        var id = options.id;

        var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, id);
        for (var index = 0; index < affectedNodes.length; index++) {
            var node = affectedNodes[index];
            if (node) {
                var nodeEl = Ext.fly(node.getOwnerTree().getView().getNodeByRecord(node));
                nodeEl.addCls("pimcore_delete");
            }
        }

        if (pimcore.globalmanager.exists(elementType + "_" + id)) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(elementType + "_" + id);
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
            success: function (id, successHandler) {

                for (var index = 0; index < affectedNodes.length; index++) {
                    var node = affectedNodes[index];
                    try {
                        if (node) {
                            node.remove();
                        }
                    } catch (e) {
                        console.log(e);
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_" + elementType), "error");
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    }
                }

                if(this.deleteWindow) {
                    this.deleteWindow.close();
                }

                this.deleteProgressBar = null;
                this.deleteWindow = null;

                if(typeof successHandler == "function") {
                    successHandler();
                }
            }.bind(this, id, successHandler),
            update: function (currentStep, steps, percent) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (id, message) {
                this.deleteWindow.close();

                pimcore.helpers.showNotification(t("error"), t("error_deleting_" + elementType), "error", t(message));
                for (var index = 0; index < affectedNodes.length; index++) {
                    try {
                        var node = affectedNodes[i];
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            }.bind(this, id),
            jobs: r.deletejobs
        });
    }
};

pimcore.elementservice.updateAsset = function (id, data, callback) {

    if (!callback) {
        callback = function() {
        };
    }

    data.id = id;

    Ext.Ajax.request({
        url: "/admin/asset/update",
        method: "post",
        params: data,
        success: callback
    });
};

pimcore.elementservice.updateDocument = function (id, data, callback) {

    if (!callback) {
        callback = function() {
        };
    }

    data.id = id;

    Ext.Ajax.request({
        url: "/admin/document/update",
        method: "post",
        params: data,
        success: callback
    });
};

pimcore.elementservice.updateObject = function (id, values, callback) {

    if (!callback) {
        callback = function () {
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
};

pimcore.elementservice.getAffectedNodes = function(elementType, id) {
    var treeNames = pimcore.elementservice.getElementTreeNames(elementType);
    var affectedNodes = [];
    for (var index = 0; index < treeNames.length; index++) {
        var treeName = treeNames[index];
        var tree = pimcore.globalmanager.get(treeName);
        if (!tree) {
            continue;
        }
        tree = tree.tree;
        var view = tree.getView();
        var store = tree.getStore();
        var record = store.getNodeById(id);

        if (record) {
            affectedNodes.push(record);
        }
    }
    return affectedNodes;

};


pimcore.elementservice.applyNewKey = function(affectedNodes, elementType, id, value) {

    for (var index = 0; index < affectedNodes.length; index++) {
        var record = affectedNodes[index];
        record.set("text", value);
        record.set("path", record.data.basePath + value);
    }
    pimcore.helpers.addTreeNodeLoadingIndicator(elementType, id);

    return affectedNodes;
};

pimcore.elementservice.editDocumentKeyComplete =  function (options, button, value, object) {
    if (button == "ok") {

        var record;
        var id = options.id;
        var elementType = options.elementType;
        value = pimcore.helpers.getValidFilename(value, "document");

        if (options.sourceTree) {
            var tree = options.sourceTree;
            var store = tree.getStore();
            record = store.getById(id);
            if(pimcore.elementservice.isKeyExistingInLevel(record.parentNode, value, record)) {
                return;
            }
            if(pimcore.elementservice.isDisallowedDocumentKey(record.parentNode.id, value)) {
                return;
            }
        }

        var originalText;
        var originalPath;
        var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, id);
        if (affectedNodes) {
            record = affectedNodes[0];
            if(record) {
                originalText = record.get("text");
                originalPath = record.get("path");
            }
        }
        pimcore.elementservice.applyNewKey(affectedNodes, elementType, id, value);

        pimcore.elementservice.updateDocument(id, {key: value}, function (response) {
            var record, index;
            var rdata = Ext.decode(response.responseText);
            if (!rdata || !rdata.success) {
                for (index = 0; index < affectedNodes.length; index++) {
                    record = affectedNodes[index];
                    record.set("text", originalText);
                    record.set("path", originalPath);
                }
                pimcore.helpers.showNotification(t("error"), t("error_renaming_element"),
                    "error");
                return;
            }

            if(rdata && rdata.success) {
                // removes loading indicator added in the applyNewKey method
                pimcore.helpers.removeTreeNodeLoadingIndicator(elementType, id);
            }

            for (index = 0; index < affectedNodes.length; index++) {
                record = affectedNodes[index];
                pimcore.elementservice.refreshNode(record.parentNode);
            }

            if (pimcore.globalmanager.exists("document_" + id)) {
                try {
                    if (rdata && rdata.success) {
                        pimcore.elementservice.reopenElement(options);
                    }  else {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_document"), "error",
                            t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_document"), "error");
                }
            }
        }.bind(this));
    }
};

pimcore.elementservice.editObjectKeyComplete = function (options, button, value, object) {
    if (button == "ok") {

        var record;
        var id = options.id;
        var elementType = options.elementType;
        value = pimcore.helpers.getValidFilename(value, "object");

        if (options.sourceTree) {
            var tree = options.sourceTree;
            var store = tree.getStore();
            record = store.getById(id);
            if(pimcore.elementservice.isKeyExistingInLevel(record.parentNode, value, record)) {
                return;
            }
        }

        var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, id);
        if (affectedNodes) {
            record = affectedNodes[0];
            if(record) {
                originalText = record.get("text");
                originalPath = record.get("path");
            }
        }
        pimcore.elementservice.applyNewKey(affectedNodes, elementType, id, value);

        pimcore.elementservice.updateObject(id, {key: value},
            function (response) {
                var index, record;
                for (index = 0; index < affectedNodes.length; index++) {
                    record = affectedNodes[index];
                    pimcore.elementservice.refreshNode(record);
                }

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.elementservice.reopenElement(options);
                        // removes loading indicator added in the applyNewKey method
                        pimcore.helpers.removeTreeNodeLoadingIndicator(elementType, id);
                    }  else {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_object"), "error",
                            t(rdata.message));
                        for (index = 0; index < affectedNodes.length; index++) {
                            record = affectedNodes[index];
                            pimcore.elementservice.refreshNode(record.parentNode);
                        }
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_object"), "error");
                    for (index = 0; index < affectedNodes.length; index++) {
                        record = affectedNodes[index];
                        pimcore.elementservice.refreshNode(record.parentNode);
                    }
                }
            }.bind(this))
        ;
    }
};

pimcore.elementservice.reopenElement = function(options) {
    var elementType = options.elementType;
    if (pimcore.globalmanager.exists(elementType + "_" + options.id)) {
        pimcore.helpers["close"  + ucfirst(elementType)](options.id);
        pimcore.helpers["open" + ucfirst(elementType)](options.id, options.elementSubType);
    }

};

pimcore.elementservice.editAssetKeyComplete = function (options, button, value, object) {
    try {
        if (button == "ok") {
            var record;
            var id = options.id;
            var elementType = options.elementType;

            value = pimcore.helpers.getValidFilename(value, "asset");

            if (options.sourceTree) {
                var tree = options.sourceTree;
                var store = tree.getStore();
                record = store.getById(id);
                // check for ident filename in current level

                var parentChilds = record.parentNode.childNodes;
                for (var i = 0; i < parentChilds.length; i++) {
                    if (parentChilds[i].data.text == value && this != parentChilds[i].data.text) {
                        Ext.MessageBox.alert(t('rename'), t('the_filename_is_already_in_use'));
                        return;
                    }
                }
            }

            var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, id);
            if (affectedNodes) {
                record = affectedNodes[0];
                if(record) {
                    originalText = record.get("text");
                    originalPath = record.get("path");
                }
            }
            pimcore.elementservice.applyNewKey(affectedNodes, elementType, id, value);

            pimcore.elementservice.updateAsset(id, {filename: value},
                function (response) {
                    var index, record;
                    var rdata = Ext.decode(response.responseText);
                    if (!rdata || !rdata.success) {
                        for (index = 0; index < affectedNodes.length; index++) {
                            record = affectedNodes[index];
                            record.set("text", originalText);
                            record.set("path", originalPath);
                        }
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_element"),
                            "error");
                        return;
                    }

                    if(rdata && rdata.success) {
                        // removes loading indicator added in the applyNewKey method
                        pimcore.helpers.removeTreeNodeLoadingIndicator(elementType, id);
                    }

                    for (index = 0; index < affectedNodes.length; index++) {
                        record = affectedNodes[index];
                        pimcore.elementservice.refreshNode(record);
                    }

                    if (pimcore.globalmanager.exists("asset_" + id)) {
                        try {
                            if (rdata && rdata.success) {
                                pimcore.elementservice.reopenElement(options);
                            }  else {
                                pimcore.helpers.showNotification(t("error"), t("error_renaming_element"),
                                    "error", t(rdata.message));
                            }
                        } catch (e) {
                            pimcore.helpers.showNotification(t("error"), t("error_renaming_element"),
                                "error");
                        }
                    }
                }.bind(this))
            ;
        }
    } catch (e) {
        console.log(e);
    }
};

pimcore.elementservice.editElementKey = function(options) {
    if (options.elementType == "asset") {
        completeCallback = pimcore.elementservice.editAssetKeyComplete.bind(this, options);
    } else if (options.elementType == "document") {
        completeCallback = pimcore.elementservice.editDocumentKeyComplete.bind(this, options);
    } else if (options.elementType == "object") {
        completeCallback = pimcore.elementservice.editObjectKeyComplete.bind(this, options);
    } else {
        throw new Error("type " + options.elementType + " not supported!");
    }

    Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'), completeCallback, window, false, options.default);

};


pimcore.elementservice.refreshNode = function (node) {
    var ownerTree = node.getOwnerTree();

    node.data.expanded = true;
    ownerTree.getStore().load({
        node: node
    });
};


pimcore.elementservice.isDisallowedDocumentKey = function (parentNodeId, key) {

    if(parentNodeId == 1) {
        var disallowedKeys = ["admin","install","webservice","plugin"];
        if(in_arrayi(key, disallowedKeys)) {
            Ext.MessageBox.alert(t('name_is_not_allowed'),
                t('name_is_not_allowed'));
            return true;
        }
    }
    return false;
};

pimcore.elementservice.isKeyExistingInLevel = function(parentNode, key, node) {

    key = pimcore.helpers.getValidFilename(key, parentNode.data.elementType);
    var parentChilds = parentNode.childNodes;
    for (var i = 0; i < parentChilds.length; i++) {
        if (parentChilds[i].data.text == key && node != parentChilds[i]) {
            Ext.MessageBox.alert(t('edit_key'),
                t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
            return true;
        }
    }
    return false;
};

pimcore.elementservice.nodeMoved = function(elementType, oldParent, newParent) {
    // disabled for now
    /*var oldParentId = oldParent.getId();
    var newParentId = newParent.getId();
    var newParentTreeId = newParent.getOwnerTree().getId();

    var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, newParentId);
    for (var index = 0; index < affectedNodes.length; index++) {
        var node = affectedNodes[index];
        var nodeTreeId = node.getOwnerTree().getId();
        if (nodeTreeId != newParentTreeId) {
            pimcore.elementservice.refreshNode(node);
        }
    }

    if (oldParentId != newParentId) {
        var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, oldParentId);
        for (var index = 0; index < affectedNodes.length; index++) {
            var node = affectedNodes[index];
            var nodeTreeId = node.getOwnerTree().getId();
            if (nodeTreeId != newParentTreeId) {
                pimcore.elementservice.refreshNode(node);
            }
        }
    }*/
};

pimcore.elementservice.addObject = function(options) {

    var url = options.url;
    delete options.url;
    delete options["sourceTree"];

    Ext.Ajax.request({
        url: url,
        params: options,
        success: pimcore.elementservice.addObjectComplete.bind(this, options)
    });
};

pimcore.elementservice.addDocument = function(options) {

    var url = options.url;
    delete options.url;
    delete options["sourceTree"];

    Ext.Ajax.request({
        url: url,
        params: options,
        success: pimcore.elementservice.addDocumentComplete.bind(this, options)
    });
};

pimcore.elementservice.refreshRootNodeAllTrees = function(elementType) {
    var treeNames = pimcore.elementservice.getElementTreeNames(elementType);
    for (var index = 0; index < treeNames.length; index++) {
        try {
            var treeName = treeNames[index];
            var tree = pimcore.globalmanager.get(treeName);
            if (!tree) {
                continue;
            }
            tree = tree.tree;
            var rootNode = tree.getRootNode();
            if (rootNode) {
                pimcore.elementservice.refreshNode(rootNode);
            }
        } catch (e) {
            console.log(e);
        }
    }
};



pimcore.elementservice.refreshNodeAllTrees = function(elementType, id) {
    var treeNames = pimcore.elementservice.getElementTreeNames(elementType);
    for (var index = 0; index < treeNames.length; index++) {
        try {
            var treeName = treeNames[index];
            var tree = pimcore.globalmanager.get(treeName);
            if (!tree) {
                continue;
            }
            tree = tree.tree;
            var store = tree.getStore();
            var parentRecord = store.getById(id);
            if (parentRecord) {
                parentRecord.data.leaf = false;
                parentRecord.expand();
                pimcore.elementservice.refreshNode(parentRecord);
            }
        } catch (e) {
            console.log(e);
        }
    }
};

pimcore.elementservice.addDocumentComplete = function (options, response) {
    try {
        response = Ext.decode(response.responseText);
        if (response && response.success) {
            pimcore.elementservice.refreshNodeAllTrees(options.elementType, options.parentId);

            if(in_array(response["type"], ["page","snippet","email","newsletter","link","hardlink","printpage","printcontainer"])) {
                pimcore.helpers.openDocument(response.id, response.type);
            }
        }  else {
            pimcore.helpers.showNotification(t("error"), t("error_creating_document"), "error",
                t(response.message));
        }
    } catch(e) {
        pimcore.helpers.showNotification(t("error"), t("error_creating_document"), "error");
    }
};

pimcore.elementservice.addObjectComplete = function(options, response) {
    try {
        var rdata = Ext.decode(response.responseText);
        if (rdata && rdata.success) {
            pimcore.elementservice.refreshNodeAllTrees(options.elementType, options.parentId);

            if (rdata.id && rdata.type) {
                if (rdata.type == "object") {
                    pimcore.helpers.openObject(rdata.id, rdata.type);
                }
            }
        }  else {
            pimcore.helpers.showNotification(t("error"), t("error_creating_object"), "error", t(rdata.message));
        }
    } catch (e) {
        pimcore.helpers.showNotification(t("error"), t("error_creating_object"), "error");
    }

};


pimcore.elementservice.lockElement = function(options) {
    try {
        var updateMethod = pimcore.elementservice["update" + ucfirst(options.elementType)];
        updateMethod(options.id,
            {
                locked: options.mode
            },
            function() {
                pimcore.elementservice.refreshRootNodeAllTrees(options.elementType);
            }
        );
    } catch (e) {
        console.log(e);
    }
};

pimcore.elementservice.unlockElement = function(options) {
    try {
        Ext.Ajax.request({
            url: "/admin/element/unlock-propagate",
            params: {
                id: options.id,
                type: options.elementType
            },
            success: function () {
                pimcore.elementservice.refreshRootNodeAllTrees(options.elementType);
            }.bind(this)
        });
    } catch (e) {
        console.log(e);
    }
};

pimcore.elementservice.setElementPublishedState = function(options) {
    var elementType = options.elementType;
    var id = options.id;
    var published = options.published;

    var affectedNodes = pimcore.elementservice.getAffectedNodes(elementType, id);
    for (var index = 0; index < affectedNodes.length; index++) {
        try {
            var node = affectedNodes[index];
            if (node) {
                var tree = node.getOwnerTree();
                var view = tree.getView();
                var nodeEl = Ext.fly(view.getNodeByRecord(node));
                if (nodeEl) {
                    var nodeElInner = nodeEl.down(".x-grid-td");
                    if (nodeElInner) {
                        if (published) {
                            nodeElInner.removeCls("pimcore_unpublished");
                        } else {
                            nodeElInner.addCls("pimcore_unpublished");
                        }
                    }
                }
                if (published) {
                    delete node.data.cls;
                } else {
                    node.data.cls = "pimcore_unpublished";
                }
                node.data.published = published;
            }
        } catch (e) {
            console.log(e);
        }
    }
};

pimcore.elementservice.setElementToolbarButtons = function(options) {
    var elementType = options.elementType;
    var id = options.id;
    var key = elementType + "_" + id;
    if (pimcore.globalmanager.exists(key)) {
        if (options.published) {
            pimcore.globalmanager.get(key).toolbarButtons.unpublish.show();
        } else {
            pimcore.globalmanager.get(key).toolbarButtons.unpublish.hide();
        }
    }
};

pimcore.elementservice.reloadVersions = function(options) {
    var elementType = options.elementType;
    var id = options.id;
    var key = elementType + "_" + id;

    if (pimcore.globalmanager.exists(key)) {
        // reload versions
        if (pimcore.globalmanager.get(key).versions) {
            if (typeof pimcore.globalmanager.get(key).versions.reload  == "function") {
                pimcore.globalmanager.get(key).versions.reload();
            }
        }
    }
};

pimcore.elementservice.showLocateInTreeButton = function(elementType) {
    if (pimcore.globalmanager.get("layout_" + elementType + "s_locateintree_tree")) {
        return true;
    }
    return false;
};

pimcore.elementservice.integrateWorkflowManagement = function(elementType, elementId, elementEditor, buttons) {

    if(elementEditor.data.workflowManagement && elementEditor.data.workflowManagement.hasWorkflowManagement === true) {

        buttons.unshift({
            xtype: 'button',
            text: t('actions'),
            scale: "medium",
            iconCls: 'pimcore_icon_workflow_action',
            handler: function() {
                new pimcore.workflowmanagement.actionPanel(elementType, elementId, elementEditor);
            }
        });

        buttons.push("-");
        buttons.push({
            xtype: 'container',
            html: [
                ts(elementEditor.data.workflowManagement.status.label)
            ].join(''),
            style: 'color: ' + elementEditor.data.workflowManagement.state.color + ';',
            cls: 'wf-status-outer'
        });

    }

};