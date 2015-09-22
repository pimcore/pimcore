/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
     * This method is called in /pimcore/static/js/pimcore/startup.js
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
                    this.callbacks[this.items[i].id]();
                    this.items[i].processed = true;
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

        tree.collapse();

        target.items.each(function (item, index, length) {
            item.collapse();
        });

        target.add(tree);
        target.doLayout();
        tree.expand();

        if(source.items.getCount() < 1) {
            source.collapse();
            source.hide();
        } else if(!source.getLayout().activeItem) {
            source.items.first().expand();
        }
        source.doLayout();

        pimcore.layout.refresh();
    }
};
