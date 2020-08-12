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

pimcore.registerNS("pimcore.object.classificationstore.columnConfigDialog");
pimcore.object.classificationstore.columnConfigDialog = Class.create({

    keysAdded: 0,
    requestIsPending: false,

    getConfigDialog: function(ownerTree, node, selectionPanel) {
        this.ownerTree = ownerTree;
        this.node = node;
        this.selectionPanel = selectionPanel;

        var selectionWindow = new pimcore.object.classificationstore.relationSelectionWindow(this, node.data.layout.storeId);
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

        var originalKey =  this.node.data.key;

        if(data && data.success) {
            for (var i=0; i < data.data.length; i++) {
                var keyDef = data.data[i];

                var encodedKey = "~classificationstore~" + originalKey + "~" +  keyDef.groupId + "-" + keyDef.keyId;

                if (this.selectionPanel.getRootNode().findChild("key", encodedKey)) {
                    // key already exists, continue
                    continue;
                }

                if (this.keysAdded > 0) {
                    var configEncoded = Ext.encode(this.node.data);
                    var configDecoded = Ext.decode(configEncoded);
                    delete configDecoded.id;
                    delete configDecoded.options;
                    delete configDecoded.layout.gridType;

                    var copy = Ext.apply({}, configDecoded); // copy it

                    this.node = this.selectionPanel.getRootNode().createNode(copy);
                }


                this.node.set("key", encodedKey);
                this.node.data.layout.gridType = keyDef.type;

                if (keyDef.type == "select") {
                    this.node.data.layout.options = Ext.decode(keyDef.possiblevalues);
                }

                this.node.set("text", "#" + keyDef.keyName);
                this.node.set("layout", keyDef.layout);
                this.node.set("dataType", keyDef.layout.fieldtype);

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
