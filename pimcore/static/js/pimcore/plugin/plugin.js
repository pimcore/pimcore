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

pimcore.registerNS("pimcore.plugin.admin");
pimcore.plugin.admin = Class.create({

    initialize: function() {
    },
    getClassName: function () {
    },

    /* is called after plugin is uninstalled - can be used to do deactivate plugin UI features. */
    uninstall: function() {
    },


    /* events */

    preOpenObject: function (object, type) {
    },
    postOpenObject: function (object, type) {
    },


    preOpenAsset: function (asset, type) {
    },
    postOpenAsset: function (asset, type) {
    },

    preOpenDocument: function (document, type) {
    },
    postOpenDocument: function (document, type) {
    },

    pimcoreReady: function (viewport) {
    }

});