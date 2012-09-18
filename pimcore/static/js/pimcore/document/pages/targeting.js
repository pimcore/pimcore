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

pimcore.registerNS("pimcore.document.pages.targeting");
pimcore.document.pages.targeting = Class.create(pimcore.settings.targeting.panel, {

    initialize: function(page) {
        this.page = page;
        this.treeDataUrl = '/admin/reports/targeting/list/documentId/' + this.page.id;
    }

});