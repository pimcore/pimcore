pimcore.registerNS("pimcore.treenodelocator");

pimcore.treenodelocator = function()
{

    /**
     * Private vars
     */

    // Holds the state
    var busy = false;

    // The states hold current data for each phase of a tree location task.
    var globalState = null;
    var treeState = null;
    var pagingState = null;

    // Holds the current button.
    var currentButton = null;

    // Holds loading indicators type/ids.
    var loadingIndicators = [];


    /**
     * Private functions
     */
    var self = {

        /**
         * Show tree node of given id and element type (document/asset/object)
         * in the first matching tree.
         */
        showInTree: function (id, elementType, button)
        {
            // don't allow concurrent execution
            if (busy) {
                return;
            }

            busy = true;

            if (button) {
            	button.disable();
                currentButton = button;
            }
            self.startShowInTree(id, elementType);
        },


        /**
         * Report final failure.
         */
        reportFailed: function()
        {
            // @todo: would it be nice to display an error message?
            self.cleanup();
        },


        /**
         * Report tree node location successful.
         */
        reportSuccess: function(node)
        {
            if (node) {
                var tree = node.getOwnerTree();
                var view = tree.getView();
                view.focusRow(node);
            }
            self.cleanup();
        },


        /**
         * Clean up after successful or failed tree node location.
         */
        cleanup: function()
        {
            // re-enable the button
            if (currentButton) {
                currentButton.enable();
                currentButton = null;
            }

            // reset all states
            globalState = null;
            treeState = null;
            pagingState = null;

            // there is a race timing condition when the loading indicator gets
            // cleared before being added - this keeps spinning the indicator
            // forever:
            window.setTimeout(self.clearLoadingIndicators, 200);

            busy = false;
        },


        /**
         * Start tree node location for given id and element type
         * (one of "document", "asset", "object").
         */
        startShowInTree: function(id, elementType) {

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_element_typepath'),
                params: {
                    id: id,
                    type: elementType
                },
                success: function (response) {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {

                        var allLocateConfigs = pimcore.globalmanager.get("tree_locate_configs");
                        var locateConfigs = allLocateConfigs[elementType];
                        if (!locateConfigs || locateConfigs.length === 0) {
                            self.reportFailed();
                            return;
                        }

                        globalState = {
                            idPath: res.idPath,
                            fullPath: res.fullpath,  // mind lower case here!
                            typePath: res.typePath,
                            sortIndexPath: res.sortIndexPath,
                            pathIds: res.idPath.replace(/^\//, "").split("/"),
                            elementType: elementType,
                            locateConfigs: locateConfigs,
                            currentTreeIndex: 0
                        };
                        self.processTree();

                    } else {
                        self.reportFailed();
                    }
                },
                failure: function() {
                    self.reportFailed();
                }
            });
        },


        /**
         * Start tree node location in current tree state.
         */
        processTree: function()
        {
            var locateConfig = globalState.locateConfigs[globalState.currentTreeIndex];
            var tree = locateConfig.tree;
            var rootNode = tree.tree.getRootNode();
            var rootNodeId = rootNode.getId();

            // Tree root may be shifted to a subnode and the item to be shown
            // is out of tree scope - don't continue if this is the case:
            if (globalState.pathIds.indexOf(rootNodeId) == -1) {
                self.reportProcessTreeFailed();
                return;
            }

            // The accordion needs to be rendered and tree expanded
            var accordion = Ext.getCmp("pimcore_panel_tree_" + locateConfig.side);
            if (accordion) {
                 accordion.expand();
            }
            tree.tree.expand();

            // We may have a tree with a shifted root defined by a custom view.
            // Let's create a state for current tree with adjusted values:
            var idPath = globalState.idPath;

            // Create an array copy to not tamper the original:
            var pathIds = globalState.pathIds.slice();

            var rootNodeIndex = pathIds.indexOf(rootNodeId);
            if (rootNodeIndex) {
                pathIds.splice(0, rootNodeIndex);
                idPath = "/" + pathIds.join("/");
            }

            treeState = {
                tree: tree.tree,
                rootNode: rootNode,
                rootNodeId: rootNodeId,
                idPath: idPath,
                pathIds: pathIds,
                reloaded: false
            };

            try {
                self.processFullPath();
            } catch (err) {
                self.reportProcessTreeFailed();
            }

        },


        /**
         * Tree node location has failed in the current tree.
         */
        reportProcessTreeFailed: function()
        {
            globalState.currentTreeIndex++;
            if (globalState.currentTreeIndex < globalState.locateConfigs.length) {
                treeState = null;
                pagingState = null;
                self.processTree();
            } else {
                self.reportFailed();
            }
        },


        /**
         * Try to resolve the full path.
         */
        processFullPath: function()
        {
            treeState.tree.selectPath(treeState.idPath, null, "/", function (success, node) {
                if (success) {
                    self.reportSuccess(node);
                } else {
                    self.reportProcessFullPathFailed();
                }
            });
        },


        /**
         * Resolving the full path has failed. There may be several reasons, for the target
         * node itself or any of its ancestors:
         *
         * - Tree paging is active, and the next child node is on another page
         * - Tree paging is active, and a search filter has blocked the next child node
         * - A custom view filters any of our nodes
         * - Data has changed and our tree store is not up to date anymore
         * - ...
         */
        reportProcessFullPathFailed: function()
        {
            var node = self.getLastExpandedNode(treeState.pathIds, treeState.tree);
            self.addLoadingIndicator(globalState.elementType, node.id);

            // Reload the tree starting from given node once
            // This solves two issues: All subsequent search filters are reset and we know
            // that the tree store is up to date:
            if (treeState.reloaded == false) {
                self.reloadTree(node);
                return;
            }

            // Next, if the last expanded node has tree paging, try to get to our child node.
            var pagingData = node.pagingData;
            if (pagingData) {

                var nodePath = node.getPath();
                var nodePathIds = nodePath.replace(/^\//, "").split("/");
                var childNodeId = treeState.pathIds[nodePathIds.length];
                var total = parseInt(pagingData.total);
                var limit = parseInt(pagingData.limit);
                var offset = parseInt(pagingData.offset);
                var sortBy = node.data.sortBy;

                // We need to figure out the child node's keyname and element type from globalState.
                // There we have for example:
                // - fullPath: "/foo/bar/baz"
                // - typePath: "/folder/folder/object/object"
                // - idPath: [1, 4, 8, 12]
                // Mind that the root node obviously has no keyname in fullPath!
                var pos = globalState.pathIds.indexOf(childNodeId);

                // elementType (from typePath):
                var pathTypes = globalState.typePath.replace(/^\//, "").split("/");
                var elementType = pathTypes[pos];

                // elementKey (from fullPath):
                if (globalState.elementType == "document") {
                    var sortIndexParts = globalState.sortIndexPath.replace(/^\//, "").split("/");
                    let sortIndexPath = sortIndexParts[pos];
                    var elementKey = sortIndexPath;
                } else {
                    if (sortBy == "index") {
                        var sortIndexParts = globalState.sortIndexPath.replace(/^\//, "").split("/");
                        let sortIndexPath = sortIndexParts[pos];
                        var elementKey = sortIndexPath;
                    } else {
                        var pathKeys = globalState.fullPath.replace(/^\//, "").split("/");
                        var elementKey = pathKeys[pos-1];
                    }
                }

                pagingState = {
                    node: node,
                    childNodeId: childNodeId,
                    total: total,
                    limit: limit,
                    offset: offset,
                    activePage: (offset / total) + 1,
                    pageCount: Math.ceil(total / limit),
                    minPage: 1,
                    maxPage: Math.ceil(total / limit),
                    sortBy: sortBy,
                    elementKey: elementKey,
                    elementType: elementType
                };

                self.processPaging();

            } else {

                self.reportProcessTreeFailed();

            }
        },


        /**
         * Check if the next child node in current paging state is present.
         */
        processPaging: function()
        {
            var node = pagingState.node;
            var childNodes = node.childNodes;
            var childCount = childNodes.length;

            // Check if child exists
            for (i = 0; i < childCount; i++) {
                var childNode = childNodes[i];
                if (childNode.id == pagingState.childNodeId) {
                    self.reportProcessPagingSuccess();
                    return;
                }
            }

            // Find out if we have to move forward or backward in paging:
            var direction = 0;
            var firstelementChild = childNodes[0];
            var lastelementChild = childNodes[childCount-1];

            if (globalState.elementType == "document") {
                direction = self.getDirectionForElementsSortedByIndex(
                    pagingState.elementKey,
                    firstelementChild,
                    lastelementChild
                );
            } else {
                if (node.data.sortBy == "index") {
                    direction = self.getDirectionForElementsSortedByIndex(
                        pagingState.elementKey,
                        firstelementChild,
                        lastelementChild
                    );
                } else {
                    direction = self.getDirectionForElementsSortedByKey(
                        pagingState.elementKey,
                        pagingState.elementType,
                        firstelementChild,
                        lastelementChild
                    );
                }
            }

            // switch to page depending on direction:
            if (direction == -1) {
                pagingState.maxPage = pagingState.activePage - 1;
                newPage = (pagingState.minPage + pagingState.maxPage) / 2;
                self.switchToPage(newPage);
            } else if (direction == 1) {
                pagingState.minPage = pagingState.activePage + 1;
                newPage = (pagingState.minPage + pagingState.maxPage) / 2;
                self.switchToPage(newPage);
            } else {
                // Child node was supposed to be on current page, but obviously isn't.
                // It may be filtered using a custom view or not being displayed for other
                // reason - anyway there is nothing more to do here:
                self.reportProcessPagingFailed();
            }
        },


        /**
         * Resolving the child node in current paging state failed.
         */
        reportProcessPagingFailed: function()
        {
            pagingState = null;
            self.reportProcessTreeFailed();
        },


        /**
         * Resolving the child node in current paging state was succesful.
         */
        reportProcessPagingSuccess: function()
        {
            pagingState = null;
            self.processFullPath();
        },


        /**
         * Switch to given page in current paging state.
         */
        switchToPage: function(pageNumber) {
            pageNumber = Math.floor(pageNumber);
            if ((pageNumber > pagingState.maxPage || pageNumber < pagingState.minPage)) {
                self.reportProcessPagingFailed();
                return;
            }

            var node = pagingState.node;
            var store = node.getTreeStore();
            var proxy = store.getProxy();

            pagingState.offset = pagingState.limit * (pageNumber - 1);
            pagingState.activePage = pageNumber;

            proxy.setExtraParam("start", pagingState.offset);
            node.pagingData.offset = pagingState.offset;

            store.load({
                node: node,
                callback: self.processPaging
            });
        },


        /**
         * Reload the tree starting from given node.
         */
        reloadTree: function(node)
        {
            treeState.reloaded = true;
            var store = node.getTreeStore();
            store.load({
                node: node,
                callback: self.processFullPath
            });
        },


        /**
         * Returns the last expanded node of given tree.
         */
        getLastExpandedNode: function (pathIds, tree) {
            var lastNode = tree.getRootNode();
            var store = tree.getStore();
            for (var i=0; i<pathIds.length; i++) {
                var testNode = store.getNodeById(pathIds[i]);
                if (testNode) {
                    lastNode = testNode;
                } else {
                    return lastNode;
                }
            }
            return lastNode;
        },


        /**
         * Returns the direction (-1/+1/0) for elements sorted by key.
         */
        getDirectionForElementsSortedByKey: function (elementKey, elementType, firstElementChild, lastElementChild) {
            if(elementType === 'asset' && lastElementChild && lastElementChild.data.type === 'folder') {
                return 1;
            }

            if(elementType === 'folder' && firstElementChild && firstElementChild.data.type === 'asset') {
                return -1;
            }

            if (firstElementChild && elementKey.toUpperCase() < firstElementChild.data.text.toUpperCase()) {
                return -1;
            }

            if (lastElementChild && elementKey.toUpperCase() > lastElementChild.data.text.toUpperCase()) {
                return 1;
            }

            return 0;
        },


        /**
         * Returns the direction (-1/+1/0) for elements sorted by index.
         */
        getDirectionForElementsSortedByIndex: function(elementKey, firstElementChild, lastElementChild) {
            var direction = 0;
            if (firstElementChild && elementKey < firstElementChild.data.idx) {
                direction = -1;
            } else if (lastElementChild && elementKey > lastElementChild.data.idx) {
                direction = 1;
            }
            return direction;
        },



        /**
         * Add a loading indicator for given type (document/asset/object) and id.
         */
        addLoadingIndicator: function (type, id) {
            loadingIndicators.push({type:type, id:id});
            pimcore.helpers.addTreeNodeLoadingIndicator(type, id);
        },


        /**
         * Clear all loading indicators.
         */
        clearLoadingIndicators: function () {
            for (var i=0; i<loadingIndicators.length; i++) {
                pimcore.helpers.removeTreeNodeLoadingIndicator(loadingIndicators[i].type, loadingIndicators[i].id);
            }
            loadingIndicators = [];
        }

    };


    /**
     * Expose public functions
     */
    return {
        showInTree: self.showInTree
    };

}();


