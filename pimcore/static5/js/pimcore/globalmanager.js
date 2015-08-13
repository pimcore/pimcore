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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.globalmanager");
pimcore.globalmanager = {
    store: {},

    add: function (key, value) {
        this.store[key] = value;
    },

    remove: function (key) {
        try {
            if (this.store[key]) {
                delete this.store[key];
            }
        }
        catch (e) {
            console.log("failed to remove " + key + " from cache");
        }

    },

    exists: function (key) {
        if (this.store[key]) {
            return true;
        }
        return false;
    },

    get: function (key) {
        if (this.store[key]) {
            return this.store[key];
        }
        return false;
    }
};