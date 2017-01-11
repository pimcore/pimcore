/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.keyvalue.columnConfigDialog");
pimcore.object.keyvalue.columnConfigDialog = Class.create({

    keysAdded: 0,
    requestIsPending: false,

    getConfigDialog: function(ownerTree, node, selectionPanel) {
        this.ownerTree = ownerTree;
        this.node = node;
        this.selectionPanel = selectionPanel;

        var selectionWindow = new pimcore.object.keyvalue.selectionwindow(this);
        selectionWindow.show();
    },


    handleSelectionWindowClosed: function() {
        if (this.keysAdded == 0 && !this.requestIsPending) {
            // no keys added, remove the node
            this.node.remove();

        }
    },

    requestPending: function() {
        this.requestIsPending = true;
    },

    handleAddKeys: function (response) {
        var data = Ext.decode(response.responseText);

        var originalKey =  this.node.key;

        var store = this.ownerTree.getStore();
        var targetNode = store.getById(this.node.id);

        if(data && data.success) {
            for (var i=0; i < data.data.length; i++) {
                var keyDef = data.data[i];

                var encodedKey = "~keyvalue~" + originalKey + "~" +  keyDef.id;

                if (this.selectionPanel.getRootNode().findChild("key", encodedKey)) {
                    // key already exists, continue
                    continue;
                }

                if (this.keysAdded > 0) {
                    var configEncoded = Ext.encode(this.node);
                    var configDecoded = Ext.decode(configEncoded);

                    var copy = new Ext.tree.TreeNode( // copy it
                        Ext.apply({}, configDecoded)
                    );
                    this.node = copy;
                    delete this.node.layout.options;
                    delete this.node.layout.gridType;
                }


                this.node.set("key", encodedKey);
                this.node.data.layout.gridType = keyDef.type;

                //TODo  implement all subtypes
                if (keyDef.type == "select") {
                    this.node.layout.options = Ext.decode(keyDef.possiblevalues);
                }

                targetNode.set("text", "#" + keyDef.name);

                if (this.keysAdded > 0) {
                    this.selectionPanel.getRootNode().appendChild(this.node);
                }
                this.keysAdded++;
            }
        }

        if (this.keysAdded == 0) {
            this.node.remove();
        }
    }

});