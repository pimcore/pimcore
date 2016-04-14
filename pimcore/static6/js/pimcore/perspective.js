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
