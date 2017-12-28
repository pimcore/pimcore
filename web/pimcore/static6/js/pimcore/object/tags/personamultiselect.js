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

/**
 * @deprecated Use pimcore.object.tags.targetGroupMultiselect instead. Will be removed in Pimcore 6.
 */
pimcore.registerNS("pimcore.object.tags.personamultiselect");
pimcore.object.tags.personamultiselect = Class.create(pimcore.object.tags.multiselect, {

    type: "personamultiselect",

    getGridColumnFilter: function(field) {
        return null;
    }

});
