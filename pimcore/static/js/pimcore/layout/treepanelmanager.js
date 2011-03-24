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

pimcore.registerNS("pimcore.layout.treepanelmanager");
pimcore.layout.treepanelmanager = {
    
    items: [],
    finished: [],
    callbacks: {},
    inital: true,

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
            pimcore.plugin.broker.fireEvent("pimcoreReady", pimcore.viewport);
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
            pimcore.plugin.broker.fireEvent("pimcoreReady", pimcore.viewport);
        }
        
        this.inital = false;
    }
};
