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

pimcore.registerNS("pimcore.eventDispatcher");
pimcore.eventDispatcher = {

    targets: {},

    initialize: function() {

    },

    registerTarget: function(key, target) {
        var key = Ext.id();
        this.targets[key] = target;
        return key;
    },

    unregisterTarget: function(key) {
        delete this.targets[key];
    },

    executeHandler: function (target, event, params) {
        if (typeof target[event] == "function") {
            params.push(this);
            target[event].apply(target, params);
        }
    },

    fireEvent: function (e) {
        var args = Array.from(arguments);
        args.splice(0, 1);

        var key;
        for (key in this.targets) {
            if ( this.targets.hasOwnProperty(key) )  {
                var target = this.targets[key];
                try {
                    this.executeHandler(target, e, args);
                } catch (e) {
                    console.error(e);
                }
            }
        }
    }
};
