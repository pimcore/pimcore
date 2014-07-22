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

pimcore.registerNS("pimcore.object.bulkexport");
pimcore.object.bulkexport = Class.create({

    exportUrl: "/admin/class/bulk-export",

    initialize: function () {
    },

    export: function() {
        url = this.getExportUrl();
        pimcore.settings.showCloseConfirmation = false;
        window.setTimeout(function () {
            pimcore.settings.showCloseConfirmation = true;
        },1000);

        location.href = url;
    },


    getExportUrl: function() {
        return  this.exportUrl;
    }


});