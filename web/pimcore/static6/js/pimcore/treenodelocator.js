pimcore.registerNS("pimcore.treenodelocator.x");

pimcore.treenodelocator.showInTree = function (id, elementType, button) {

    if (button) {
        button.disable();
    }

    var callback = function () {
        if (button) {
            button.enable();
        }
    }
    pimcore.treenodelocator.startShowInTree(id, elementType, callback, 0);


}

pimcore.treenodelocator.startShowInTree = function(id, elementType, callback, treeIdx) {
    Ext.Ajax.request({
        url: "/admin/element/type-path",
        params: {
            id: id,
            type: elementType
        },
        success: function (response) {
            try {
                var res = Ext.decode(response.responseText);
                if (res.success) {
                    var locateConfigs = pimcore.globalmanager.get("tree_locate_configs");
                    var elementLocateConfigs = locateConfigs[elementType];

                    if (elementLocateConfigs) {
                        var locateConfig = elementLocateConfigs[treeIdx];
                        var side = locateConfig.side;

                        var accordion = Ext.getCmp("pimcore_panel_tree_" + side);
                        if (accordion) {
                            accordion.expand();
                        }
                        var tree = locateConfig.tree;
                        tree.tree.expand();

                        var rootNode = tree.tree.getRootNode();
                        var rootNodeId = rootNode.getId();

                        var idPath = res.idPath;
                        var idParts = idPath.split('/');

                        var idx = idParts.indexOf(rootNodeId);
                        if (idx) {
                            idParts.splice(0, idx);
                            idPath = idParts.join('/');
                        }

                        pimcore.treenodelocator.searchInTree(res, id, elementType, tree.tree, idPath, callback, treeIdx);
                    }
                }
            } catch (e) {
                //TODO nothing to do
                console.log(e);
                pimcore.treenodelocator.showError(null, null);
            }

        }.bind(this)
    });

}


pimcore.treenodelocator.reportDone = function (node, id, elementType, callback) {
    if (node) {
        pimcore.helpers.removeTreeNodeLoadingIndicator(node, node.id);
        var tree = node.getOwnerTree();
        var view = tree.getView();
        view.focusRow(node);
    }
    if (typeof callback == "function") {
        callback();
    }
}

pimcore.treenodelocator.searchInTree = function (element, id, elementType, tree, path, callback, treeIdx) {
    try {

        var initialData = {
            tree: tree,
            path: path,
            callback: callback
        };

        tree.selectPath(path, null, '/', function (success, node) {
            if (!success) {
                try {
                    var lastExpandedNode = pimcore.treenodelocator.getLastExpandedNode(path, tree);
                    if (!lastExpandedNode) {
                        lastExpandedNode = tree.getRootNode();;
                    }
                    if (lastExpandedNode) {
                        lastExpandedNode.expand();
                    }
                    pimcore.treenodelocator.getDirection(lastExpandedNode, element, id, elementType, tree, path, callback, treeIdx, null);
                } catch (e) {
                    console.log(e);
                    pimcore.treenodelocator.showError(lastExpandedNode, id, lastExpandedNode.data.elementType, tree, path, callback, treeIdx);
                }
            } else {
                pimcore.treenodelocator.reportDone(null, null, callback);
                if (typeof initialData["callback"] == "function") {
                    initialData["callback"]();
                }
            }
        }.bind(this));

    } catch (e) {
        console.log(e);
        pimcore.treenodelocator.showError(element, id, elementType, tree, path, callback, treeIdx);
    }
}

pimcore.treenodelocator.getDirection = function (node, element, id, elementType, tree, path, callback, treeIdx, searchData) {
    if (!searchData) {
        // new level
        var pagingData = node.pagingData;
        var pageCount = 1;
        if (pagingData) {
            var page = (pagingData.offset / pagingData.total) + 1;
            pageCount = Math.ceil(pagingData.total / pagingData.limit);
        }

        var searchData = {
            minPage: 1,
            maxPage: pageCount
        }
    }

    var childNodes = node.childNodes;
    var sortBy = node.data.sortBy;
    var childCount = childNodes.length;

    var nodePath = node.getPath();
    var nodeParts = nodePath.split("/");

    if (elementType == "document") {
        fullPath = element.fullpath;
        var elementKey = element.index;
    } else {
        fullPath = element.fullpath;
        var elementParts = fullPath.split("/");
        if (sortBy == "index") {
            var elementKey = element.index;
        } else {
            var elementKey = elementParts[nodeParts.length - 1];
        }
    }


    var typePath = element.typePath;
    var typeParts = typePath.split("/");
    var eType = typeParts[nodeParts.length];

    var idPath = element.idPath;

    if (idPath == nodePath) {
        var tree = node.getOwnerTree();
        tree.selectPath(idPath);
        pimcore.treenodelocator.reportDone(node, id, node.data.elementType, callback);
        return;
    }

    var idParts = idPath.split("/");
    var elementId = idParts[nodeParts.length];

    // check if already a child
    for (i = 0; i < childCount; i++) {
        var childNode = childNodes[i];
        var childId = childNode.id;
        if (childId == elementId) {
            if (nodePath != idPath) {
                childNode.expand();
                var tree = childNode.getOwnerTree();
                tree.getSelectionModel().select(childNode);
                var view = tree.getView();
                view.focusRow(childNode);
                childNode.expand(false, pimcore.treenodelocator.reloadComplete.bind(this, childNode, element, id, elementType, tree, path, callback, treeIdx, null));
            } else {
                var tree = node.getOwnerTree();
                tree.selectPath(idPath);
            }
            return;
        }
    }

    var firstFolderChild = null;
    var lastFolderChild = null;
    var firstelementChild = null;
    var lastelementChild = null;

    for (i = 0; i < childCount; i++) {
        var childNode = childNodes[i];

        if (elementType == "document" || (elementType == "object" && sortBy == "index")) {
            lastelementChild = childNode;
            if (!firstelementChild) {
                firstelementChild = childNode;
            }
        } else {

            if (childNode.data.type == "folder") {
                lastFolderChild = childNode;
                if (!firstFolderChild) {
                    firstFolderChild = childNode;
                }
            }

            if (childNode.data.type != "folder") {
                lastelementChild = childNode;
                if (!firstelementChild) {
                    firstelementChild = childNode;
                }
            }
        }
    }

    // we are looking for type elementType
    var direction = 0;
    var firstKey = null;
    var lastKey = null;

    if (elementType == "document") {
        direction = pimcore.treenodelocator.getDirectionForElementsSortedByIndex(
            elementKey, firstelementChild, lastelementChild, element, id, elementType, tree, path, callback, treeIdx
        );
    } else {
        if (node.data.sortBy == "index") {
            direction = pimcore.treenodelocator.getDirectionForElementsSortedByIndex(
                elementKey, firstelementChild, lastelementChild, element, id, elementType, tree, path, callback, treeIdx
            );
        } else {
            direction = pimcore.treenodelocator.getDirectionForElementsSortedByKey(
                elementKey, eType, firstFolderChild, lastFolderChild, firstelementChild, lastelementChild
            );
        }
    }

    var pagingData = node.pagingData;
    if (!pagingData) {
        pimcore.treenodelocator.showError(node, id, node.data.elementType, tree, path, callback, treeIdx);
        return;
    }

    var activePage = Math.ceil(pagingData.offset / pagingData.limit) + 1;
    var pageCount = Math.ceil(pagingData.total / pagingData.limit);


    if (direction == -1) {
        searchData.maxPage = activePage - 1;
        newPage = (searchData.minPage + searchData.maxPage) / 2;
        pimcore.treenodelocator.switchToPage(node, newPage, element, id, elementType, tree, path, callback, treeIdx, searchData);
    } else if (direction == 1) {

        searchData.minPage = activePage + 1;
        newPage = (searchData.minPage + searchData.maxPage) / 2;
        pimcore.treenodelocator.switchToPage(node, newPage, element, id, elementType, tree, path, callback, treeIdx, searchData);
    } else {
        pimcore.treenodelocator.reportDone(node, id, node.data.elementType, callback);
    }
}

pimcore.treenodelocator.getDirectionForElementsSortedByKey = function (elementKey, eType, firstFolderChild, lastFolderChild, firstElementChild, lastElementChild) {
    var direction = 0;

    if (eType == "folder") {
        if (firstFolderChild && elementKey.toUpperCase() < firstFolderChild.data.text.toUpperCase()) {
            direction = -1;
        } else if (lastFolderChild && elementKey.toUpperCase() > lastFolderChild.data.text.toUpperCase()) {
            direction = 1;
        } else if (firstElementChild) {
            direction = -1;
        }
    } else {
        if (lastFolderChild) {
            direction = 1;
        } else if (firstElementChild && elementKey.toUpperCase() < firstElementChild.data.text.toUpperCase()) {
            direction = -1;
        } else if (lastElementChild && elementKey.toUpperCase() > lastElementChild.data.text.toUpperCase()) {
            direction = 1;
        }
    }

    return direction;
}

pimcore.treenodelocator.getDirectionForElementsSortedByIndex = function (elementKey, firstElementChild, lastElementChild, element, id, elementType, tree, path, callback, treeIdx) {
    var direction = 0;

    if (firstElementChild && elementKey < firstElementChild.data.idx) {
        direction = -1;
    } else if (lastElementChild && elementKey > lastElementChild.data.idx) {
        direction = 1;
    } else {
        pimcore.treenodelocator.showError(node, id, elementType, tree, path, callback, treeIdx);
    }

    return direction;
}

pimcore.treenodelocator.reloadComplete = function (node, element, id, elementType, tree, path, callback, treeIdx, searchData) {
    try {
        pimcore.treenodelocator.getDirection(node, element, id, elementType, tree, path, callback, treeIdx, searchData);
    } catch (e) {
        console.log(e);
        pimcore.treenodelocator.showError(node, id, node.data.elementType, elementType, tree, path, callback, treeIdx);
    }
}

pimcore.treenodelocator.switchToPage = function (node, pageNumber, element, id, elementType, tree, path, callback, treeIdx, searchData) {
    try {
        pageNumber = Math.floor(pageNumber);

        if (pageNumber < 1) {
            pimcore.treenodelocator.reportDone(node, id, node.data.elementType, callback);
            return;
        }

        var pagingData = node.pagingData;

        var offset = pagingData.limit * (pageNumber - 1);
        node.pagingData.offset = offset;

        var store = node.getTreeStore();

        var proxy = store.getProxy();

        proxy.setExtraParam("start", offset);

        pimcore.helpers.addTreeNodeLoadingIndicator(node.data.elementType, node.id);

        store.load({
            node: node,
            callback: pimcore.treenodelocator.reloadComplete.bind(this, node, element, id, elementType, tree, path, callback, treeIdx, searchData)
        });
    } catch (e) {
        console.log(e);
        pimcore.treenodelocator.showError(node, id, node.data.elementType, tree, path, callback, treeIdx);
    }
}


pimcore.treenodelocator.getLastExpandedNode = function (path, tree) {
    var ids = path.split("/");
    var arrayLength = ids.length;
    var store = tree.getStore();
    var lastExpandedId = ids[1];
    var lastExpandedNode = store.getNodeById(lastExpandedId);

    return lastExpandedNode;
}

pimcore.treenodelocator.showError = function (element, id, elementType, tree, path, callback, treeIdx) {



    var locateConfigs = pimcore.globalmanager.get("tree_locate_configs");
    var elementLocateConfigs = locateConfigs[elementType];
    if (elementLocateConfigs && elementLocateConfigs.length > treeIdx + 1) {
        treeIdx++;
        pimcore.treenodelocator.startShowInTree(id, elementType, callback, treeIdx);
        return;
    }
    if (element) {
        pimcore.helpers.removeTreeNodeLoadingIndicator(elementType, element.id, treeIdx, id);
    }


    Ext.MessageBox.alert(t("error"), t("not_possible_with_paging"));
}
