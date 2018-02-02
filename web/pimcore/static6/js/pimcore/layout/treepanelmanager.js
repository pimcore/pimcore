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

pimcore.registerNS("pimcore.layout.treepanelmanager");
pimcore.layout.treepanelmanager = {
    
    items: [],
    finished: [],
    callbacks: {},
    inital: true,
    onReadyCallback: [],

    /**
     * This method is called in the tree classes of the elements (document, asset, object, custom views, ...)
     */
    register: function (id) {
        this.items.push({
            id: id,
            processed: false
        });
    },

    /**
     * This method is called in /pimcore/static6/js/pimcore/startup.js
     */
    startup: function () {
        if(this.items.length < 1) {
            // fire pimcoreReady because there is no treepanel
            this.onReady();
        }
    },

    /**
     * This method is called in the tree classes of the elements (document, asset, object, custom views, ...)
     */
    initPanel: function (id, callback) {

        this.finished.push(id);
        this.callbacks[id] = callback;
        
        for (var i=0; i<this.items.length; i++) {
            if(!this.items[i].processed) {
                if(in_array(this.items[i].id,this.finished)) {
                    this.items[i].processed = true;
                    this.callbacks[this.items[i].id]();
                } else {
                    return;
                }
            }
        }
        
        if(this.inital) {
            // all processed fire the pimcoreReady event
            this.onReady();
        }
        
        this.inital = false;
    },

    onReady: function () {
        for (var i=0; i<this.onReadyCallback.length; i++) {
            if(typeof this.onReadyCallback[i] == "function") {
                this.onReadyCallback[i]();
            }
        }
        pimcore.plugin.broker.fireEvent("pimcoreReady", pimcore.viewport);
    },

    addOnReadyCallback: function (event) {
        this.onReadyCallback.push(event);
    },

    toLeft: function () {
        pimcore.layout.treepanelmanager.move(this.tree, Ext.getCmp("pimcore_panel_tree_right"),
                                                                        Ext.getCmp("pimcore_panel_tree_left"));
        this.tree.tools.left.hide();
        this.tree.tools.right.show();

        this.position = "left";
    },

    toRight: function () {
        pimcore.layout.treepanelmanager.move(this.tree, Ext.getCmp("pimcore_panel_tree_left"),
                                                                        Ext.getCmp("pimcore_panel_tree_right"));
        this.tree.tools.right.hide();
        this.tree.tools.left.show();

        this.position = "right";
    },

    move: function (tree, source, target) {
        if(target.hidden) {
            target.show();
            target.expand();
        }

        target.suspendLayouts();
        source.remove(tree, false);
        target.add(tree);
        tree.expand(false);
        target.resumeLayouts();

        if(source.items.getCount() < 1) {
            source.collapse();
            source.hide();
        }
        source.updateLayout();

        pimcore.layout.refresh();
    }
};
