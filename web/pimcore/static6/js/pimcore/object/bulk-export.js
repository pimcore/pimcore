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