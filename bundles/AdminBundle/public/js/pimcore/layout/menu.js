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
 * Adding a priority sorting function for menus
 */
Ext.define('pimcore.menu.menu', {
    extend: 'Ext.menu.Menu',

    initComponent: function() {

        let me = this;
        let items = me.items;

        if(items) {
            me.items = Ext.Array.sort(items, pimcore.helpers.priorityCompare);
        }

        me.callParent();
    }
});

