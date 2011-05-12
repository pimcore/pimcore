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