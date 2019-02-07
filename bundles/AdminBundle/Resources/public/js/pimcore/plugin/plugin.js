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
