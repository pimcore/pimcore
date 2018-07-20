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

pimcore.registerNS("pimcore.settings.targeting.action.abstract");
pimcore.settings.targeting.action.abstract = Class.create({
    getName: function () {
        console.error('Name is not set for action', this);
    },

    getIconCls: function () {
        return 'pimcore_icon_add';
    },

    getPanel: function () {
        console.error('You have to implement the getPanel() method in action', this);
    }
});
