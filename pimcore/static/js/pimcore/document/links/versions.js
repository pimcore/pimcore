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

pimcore.registerNS("pimcore.document.links.versions");
pimcore.document.links.versions = Class.create(pimcore.document.versions, {

    compareVersions: function (id1, id2) {
        var path = "/admin/link/diff-versions/from/" + id1 + "/to/" + id2;
        Ext.get("document_version_iframe_" + this.document.id).dom.src = path;
    },

    showVersionPreview: function (id) {
        var path = "/admin/link/show-version/id/" + id;
        Ext.get("document_version_iframe_" + this.document.id).dom.src = path;
    }
});