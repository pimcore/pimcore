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