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