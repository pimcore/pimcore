/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

/**
 * @private
 */
pimcore.registerNS('pimcore.bundle.search.element.service');

pimcore.bundle.search.element.service = Class.create({
    openItemSelector: function (multiselect, callback, restrictions, config) {
        new pimcore.bundle.search.element.selector.selector(multiselect, callback, restrictions, config);
    }
});