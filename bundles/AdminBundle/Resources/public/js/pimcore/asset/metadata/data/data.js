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

pimcore.registerNS("pimcore.asset.metadata.data.data");
pimcore.asset.metadata.data.data = Class.create({

    allowIn: {
        predefined: true,
        custom: true
    },

    getType: function () {
        return this.type;
    },

    getIconClass: function () {
        return "pimcore_icon_" + this.getType();
    },

    getTypeName: function () {
        return t(this.getType());
    }
});
