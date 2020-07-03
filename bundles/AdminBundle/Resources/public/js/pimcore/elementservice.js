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
    var url = Routing.getBaseUrl() + "/admin/"  + elementType + "/delete-info?";
    // check for dependencies
    Ext.Ajax.request({
        url: url,
        params: {id: options.id, type: elementType},
        success: pimcore.elementservice.deleteElementsComplete.bind(window, options)
    });
};

pimcore.elementservice.deleteElementsComplete = function(options, response) {
    try {
        var res = Ext.decode(response.responseText);

        if (res.errors) {
            var message = res.batchDelete ? t('delete_error_batch') : t('delete_error');
            var hasDeleteable = true;

            if (res.itemResults) {
                var reasons = res.itemResults.filter(function (result) {
                    return !result.allowed;
                }).map(function (result) {
                    if (res.batchDelete) {
                        return htmlspecialchars(result.key + ': ' + result.reason);
                    }

                    return htmlspecialchars(result.reason);
                });

                message += "<br /><b style='display: block; text-align: center; padding: 10px 0;'>" + reasons.join('<br/>') + "</b>";

                hasDeleteable = res.itemResults.filter(function (result) {
                    return result.allowed;
                }).length > 0;
            }

            Ext.MessageBox.show({
                title:t('delete'),
                msg: message,
                buttons: hasDeleteable ? Ext.Msg.OKCANCEL : Ext.Msg.CANCEL,
                icon: Ext.MessageBox.INFO,
                fn: function(r, options, button) {
                    if (button === "ok" && hasDeleteable && r.deletejobs && r.batchDelete) {
                        pimcore.elementservice.deleteElementCheckDependencyComplete.call(this, window, r, options);
                    }
                }.bind(window, res, options)
            });
        }
        else {
            pimcore.elementservice.deleteElementCheckDependencyComplete.call(this, window, res, options);
        }
    }
    catch (e) {
        console.log(e);
    }
}

pimcore.elementservice.deleteElementCheckDependencyComplete = function (window, res, options) {

    try {
        var message = res.batchDelete ? t('delete_message_batch') : t('delete_message');
        if (res.elementKey) {
            message += "<br /><b style='display: block; text-align: center; padding: 10px 0;'>\"" + htmlspecialchars(res.elementKey) + "\"</b>";
        }
        if (res.hasDependencies) {
            message += "<br />" + t('delete_message_dependencies');
        }

        if(res["childs"] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
        }

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

        let ids = Ext.isString(id) ? id.split(',') : [id];
        ids.forEach(function (elementId) {
            pimcore.helpers.addTreeNodeLoadingIndicator(elementType, elementId);
        });

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
                width:200,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                items: [this.deleteProgressBar],
                listeners: pimcore.helpers.getProgressWindowListeners()
            });

            this.deleteWindow.show();
        }

        var pj = new pimcore.tool.paralleljobs({
            success: function (id, successHandler) {
                var refreshParentNodes = [];
                for (var index = 0; index < affectedNodes.length; index++) {
                    var node = affectedNodes[index];
                    try {
                        if (node) {
                            refreshParentNodes[node.parentNode.id] = node.parentNode.id;
                        }
                    } catch (e) {
                        console.log(e);
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_item"), "error");
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    }
                }

                for (var parentNodeId in refreshParentNodes) {
                    pimcore.elementservice.refreshNodeAllTrees(elementType, parentNodeId);
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
            update: function (currentStep, steps, percent, response) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }

                if(response && response['deleted']) {
                    var ids = Object.keys(response['deleted']);
                    ids.forEach(function (id) {
                        pimcore.helpers.closeElement(id, elementType);
                    })
                }
            }.bind(this),
            failure: function (id, message) {
                if (this.deleteWindow) {
                    this.deleteWindow.close();
                }

                pimcore.helpers.showNotification(t("error"), t("error_deleting_item"), "error", t(message));
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
        url: Routing.generate('pimcore_admin_asset_update'),
        method: "PUT",
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
        url: Routing.generate('pimcore_admin_document_document_update'),
        method: "PUT",
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
        url: Routing.generate('pimcore_admin_dataobject_dataobject_update'),
        method: "PUT",
        params: {
            id: id,
            values: Ext.encode(values)
        },
        success: callback
    });
};

pimcore.elementservice.getAffectedNodes = function(elementType, id) {

    var ids = Ext.isString(id) ? id.split(',') : [id];
    var treeNames = pimcore.elementservice.getElementTreeNames(elementType);
    var affectedNodes = [];
    for (var index = 0; index < treeNames.length; index++) {
        var treeName = treeNames[index];
        var tree = pimcore.globalmanager.get(treeName);
        if (!tree) {
            continue;
        }
        tree = tree.tree;
        var store = tree.getStore();

        ids.forEach(function (id) {
            var record = store.getNodeById(id);
            if (record) {
                affectedNodes.push(record);
            }
        });
    }

    return affectedNodes;

};


pimcore.elementservice.applyNewKey = function(affectedNodes, elementType, id, value) {
    value = Ext.util.Format.htmlEncode(value);
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

        pimcore.elementservice.updateDocument(id, {
            key: value,
            create_redirects: options['create_redirects']
        }, function (response) {
            var record, index;
            var rdata = Ext.decode(response.responseText);
            if (!rdata || !rdata.success) {
                for (index = 0; index < affectedNodes.length; index++) {
                    record = affectedNodes[index];
                    record.set("text", originalText);
                    record.set("path", originalPath);
                }
                pimcore.helpers.showNotification(t("error"), t("error_renaming_item"), "error",
                    t(rdata.message));
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
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_item"), "error",
                            t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_item"), "error");
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
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_item"), "error",
                            t(rdata.message));
                        for (index = 0; index < affectedNodes.length; index++) {
                            record = affectedNodes[index];
                            pimcore.elementservice.refreshNode(record.parentNode);
                        }
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_renaming_item"), "error");
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
                        Ext.MessageBox.alert(t('rename'), t('name_already_in_use'));
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
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_item"),
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
                                pimcore.helpers.showNotification(t("error"), t("error_renaming_item"),
                                    "error", t(rdata.message));
                            }
                        } catch (e) {
                            pimcore.helpers.showNotification(t("error"), t("error_renaming_item"),
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
    var completeCallback;
    if (options.elementType == "asset") {
        completeCallback = pimcore.elementservice.editAssetKeyComplete.bind(this, options);
    } else if (options.elementType == "document") {
        completeCallback = pimcore.elementservice.editDocumentKeyComplete.bind(this, options);
    } else if (options.elementType == "object") {
        completeCallback = pimcore.elementservice.editObjectKeyComplete.bind(this, options);
    } else {
        throw new Error("type " + options.elementType + " not supported!");
    }

    if(
        options['elementType'] === 'document' &&
        (options['elementSubType'] === 'page' || options['elementSubType'] === 'hardlink') &&
        pimcore.globalmanager.get("user").isAllowed('redirects')
    ) {
        // for document pages & hardlinks we need an additional checkbox for auto-redirects
        var messageBox = null;
        completeCallback = pimcore.elementservice.editDocumentKeyComplete.bind(this);
        var submitFunction = function () {
            options['create_redirects'] = messageBox.getComponent('create_redirects').getValue()
            completeCallback(options, 'ok', messageBox.getComponent('key').getValue());
            messageBox.close();
        };

        messageBox = new Ext.Window({
            modal: true,
            width: 500,
            title: t('rename'),
            items: [{
                xtype: 'container',
                html: t('please_enter_the_new_name')
            }, {
                xtype: "textfield",
                width: "100%",
                name: 'key',
                itemId: 'key',
                value: options.default,
                listeners: {
                    afterrender: function () {
                        window.setTimeout(function () {
                            this.focus(true);
                        }.bind(this), 100);
                    }
                }
            },{
                xtype: "checkbox",
                boxLabel: t('create_redirects'),
                name: 'create_redirects',
                itemId: 'create_redirects',
                checked: true
            }],
            bodyStyle: 'padding: 10px 10px 0px 10px',
            buttonAlign: 'center',
            buttons: [{
                text: t('OK'),
                handler: submitFunction
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
            fn: submitFunction
        });
    } else {
        Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'), completeCallback, window, false, options.default);
    }
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
            Ext.MessageBox.alert(t('error'),
                t('name_already_in_use'));
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
        method: 'POST',
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
        method: 'POST',
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
                pimcore.plugin.broker.fireEvent("postAddDocumentTree", response.id);
            }
        }  else {
            pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error",
                t(response.message));
        }
    } catch(e) {
        pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
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
                    pimcore.plugin.broker.fireEvent("postAddObjectTree", rdata.id);
                }
            }
        }  else {
            pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error", t(rdata.message));
        }
    } catch (e) {
        pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
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
            url: Routing.generate('pimcore_admin_element_unlockpropagate'),
            method: 'PUT',
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

                if(!node.data['cls']) {
                    node.data['cls'] = '';
                }

                if (published) {
                    node.data.cls = node.data.cls.replace(/pimcore_unpublished/g, '');
                } else {
                    node.data.cls += " pimcore_unpublished";
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
    var locateConfigs = pimcore.globalmanager.get("tree_locate_configs");

    if (locateConfigs[elementType]) {
        return true;
    }
    return false;
};

pimcore.elementservice.integrateWorkflowManagement = function(elementType, elementId, elementEditor, buttons) {

    if(elementEditor.data.workflowManagement && elementEditor.data.workflowManagement.hasWorkflowManagement === true) {

        var workflows = elementEditor.data.workflowManagement.workflows;

        if(workflows.length > 0) {

            var button = pimcore.elementservice.getWorkflowActionsButton(workflows, elementType, elementId, elementEditor);

            if(button !== false) {
                buttons.push("-");
                buttons.push(button);
            }
        }

        buttons.push("-");
        buttons.push({
            xtype: 'container',
            html: [
                elementEditor.data.workflowManagement.statusInfo
            ]
        });

    }

};

pimcore.elementservice.getWorkflowActionsButton = function(workflows, elementType, elementId, elementEditor) {
    var workflowsWithTransitions = [];

    workflows.forEach(function(el){

        if(el.allowedTransitions.length) {
            workflowsWithTransitions.push(el);
        } else if(el.globalActions.length) {
            workflowsWithTransitions.push(el);
        }
    }.bind(workflowsWithTransitions));

    if(workflowsWithTransitions.length > 0) {

        var items = [];

        workflowsWithTransitions.forEach(function (workflow) {
            if (workflowsWithTransitions.length > 1) {
                items.push({
                    xtype: 'container',
                    html: '<span class="pimcore-workflow-action-workflow-label">' + t(workflow.label) + '</span>'
                });
            }

            for (i = 0; i < workflow.allowedTransitions.length; i++) {
                var transition = workflow.allowedTransitions[i];

                items.push({
                    text: t(transition.label),
                    iconCls: transition.iconCls,
                    handler: function (workflow, transition) {

                        transition.isGlobalAction = false;
                        if (transition.notes) {
                            new pimcore.workflow.transitionPanel(elementType, elementId, elementEditor, workflow.name, transition);
                        } else {
                            pimcore.workflow.transitions.perform(elementType, elementId, elementEditor, workflow.name, transition);
                        }


                    }.bind(this, workflow, transition)
                });
            }


            for (i = 0; i < workflow.globalActions.length; i++) {
                var transition = workflow.globalActions[i];

                items.push({
                    text: t(transition.label),
                    iconCls: transition.iconCls,
                    handler: function (workflow, transition) {

                        transition.isGlobalAction = true;
                        if (transition.notes) {
                            new pimcore.workflow.transitionPanel(elementType, elementId, elementEditor, workflow.name, transition);
                        } else {
                            pimcore.workflow.transitions.perform(elementType, elementId, elementEditor, workflow.name, transition);
                        }


                    }.bind(this, workflow, transition)
                });
            }
        });

        return {
            text: t('actions'),
            scale: "medium",
            iconCls: 'pimcore_material_icon_workflow pimcore_material_icon',
            cls: 'pimcore_workflow_button',
            menu: {
                xtype: 'menu',
                items: items
            }
        };
    }

    return false;
};


pimcore.elementservice.replaceAsset = function (id, callback) {
    pimcore.helpers.uploadDialog(Routing.generate('pimcore_admin_asset_replaceasset', {id: id}), "Filedata", function() {
        if(typeof callback == "function") {
            callback();
        }
    }.bind(this), function (res) {
        var message = false;
        try {
            var response = Ext.util.JSON.decode(res.response.responseText);
            if(response.message) {
                message = response.message;
            }

        } catch(e) {}

        Ext.MessageBox.alert(t("error"), message || t("error"));
    });
};


pimcore.elementservice.downloadAssetFolderAsZip = function (id, selectedIds) {

    var that = {};

    var idsParam = '';
    if(selectedIds && selectedIds.length) {
        idsParam = selectedIds.join(',');
    }

    Ext.Ajax.request({
        url: Routing.generate('pimcore_admin_asset_downloadaszipjobs'),
        params: {
            id: id,
            selectedIds: idsParam
        },
        success: function(response) {
            var res = Ext.decode(response.responseText);

            that.downloadProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            that.downloadProgressWin = new Ext.Window({
                title: t("download_as_zip"),
                layout:'fit',
                width:200,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                items: [that.downloadProgressBar],
                listeners: pimcore.helpers.getProgressWindowListeners()
            });

            that.downloadProgressWin.show();


            var pj = new pimcore.tool.paralleljobs({
                success: function () {
                    if(that.downloadProgressWin) {
                        that.downloadProgressWin.close();
                    }

                    that.downloadProgressBar = null;
                    that.downloadProgressWin = null;

                    pimcore.helpers.download(Routing.generate('pimcore_admin_asset_downloadaszip', {jobId: res.jobId, id: id}));
                },
                update: function (currentStep, steps, percent) {
                    if(that.downloadProgressBar) {
                        var status = currentStep / steps;
                        that.downloadProgressBar.updateProgress(status, percent + "%");
                    }
                },
                failure: function (message) {
                    that.downloadProgressWin.close();
                    pimcore.helpers.showNotification(t("error"), t("error"),
                        "error", t(message));
                },
                jobs: res.jobs
            });
        }
    });
};
