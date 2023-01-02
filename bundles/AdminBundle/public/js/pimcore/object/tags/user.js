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
 * @internal
 */
pimcore.registerNS("pimcore.object.tags.user");
pimcore.object.tags.user = Class.create(pimcore.object.tags.select, {

    type: "user",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
        this.fieldConfig.width = 300;
    }
});