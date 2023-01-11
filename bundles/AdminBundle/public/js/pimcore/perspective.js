/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.perspective");

/**
 * @private
 */
pimcore.perspective = Class.create({

    initialize: function(perspective) {
        Object.assign(this, perspective);
        this.cache = {};
    },

    getElementTree: function() {
        return this.elementTree;
    },


    inToolbar: function(key) {
        return this.inPerspectiveConfig(key, "toolbar");
    },

    inTreeContextMenu: function(key) {
        return this.inPerspectiveConfig(key, "treeContextMenu");
    },

    inPerspectiveConfig: function(key, context) {

        var eventData =  {
            key: key,
            context: context
        }

        const preCreateMenuOption = new CustomEvent(pimcore.events.preCreateMenuOption, {
            detail: {
                eventData: eventData
            },
            cancelable: true
        });

        const isAllowed = document.dispatchEvent(preCreateMenuOption);
        if (!isAllowed) {
            return false;
        }

        if (typeof eventData.isAllowed !== "undefined") {
            return eventData.isAllowed;
        }

        if (!this[context]) {
            return true;
        }

        var cacheKey = context + "." + key;

        if (typeof this.cache[cacheKey] !== "undefined") {
            return this.cache[cacheKey];
        }

        var parts = key.split(".");
        var menuItems = this[context];

        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];

            if (typeof menuItems[part] == "undefined") {
                break;
            }

            var menuItem = menuItems[part];

            if (typeof menuItem == "object") {

                if (menuItem.hidden) {
                    this.cache[cacheKey] = false;
                    return false;
                }

                if (!menuItem.items) {
                    break;
                }
                menuItems = menuItem.items;
            } else {
                this.cache[cacheKey] = menuItem;
                return menuItem;
            }
        }
        this.cache[cacheKey] = true;
        return true;
    }

});
