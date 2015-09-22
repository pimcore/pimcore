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

pimcore.registerNS("pimcore.plugin.broker");
pimcore.plugin.broker = {

    plugins: new Array(),

    initialize: function() {

    },

    registerPlugin: function(plugin) {
        this.plugins.push(plugin);
    },

    getPlugins: function() {
        return this.plugins;
    },

    pluginsAvailable: function () {
        var size;

        if (this.plugins != null && this.plugins.size() > 0) {
            return this.plugins.size();
        }
        return 0;
    },

    executePlugin: function (plugin, event, params) {
        if (typeof plugin[event] == "function") {
            params.push(this);
            plugin[event].apply(plugin, params);
        }
    },

    fireEvent: function (e) {
        var plugin;
        var size = this.pluginsAvailable();
        var args = $A(arguments);
        args.splice(0, 1);

        for (var i = 0; i < size; i++) {
            plugin = this.plugins[i];
            try {
                this.executePlugin(plugin, e, args);
            } catch (e) {
                console.error(e);
            }
        }
    }
};