/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.perspective");

pimcore.perspective = Class.create({

    initialize: function(perspective) {
        Object.extend(this, perspective);
    },

    getElementTree: function() {
        return this.elementTree;
    },

    inToolbar: function(key) {
        if (!this.toolbar) {
            return true;

        }
        var parts = key.split(".");
        var menuItems = this.toolbar;

        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];

            if (typeof menuItems[part] == "undefined") {
                break;
            }

            var menuItem = menuItems[part];

            if (typeof menuItem == "object") {

                if (menuItem.hidden) {
                    return false;
                }

                if (!menuItem.items) {
                    break;
                }
                menuItems = menuItem.items;
            } else {
                return menuItem;
            }

        }
        return true;
    }

});
