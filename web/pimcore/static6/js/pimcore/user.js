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

pimcore.registerNS("pimcore.user");

pimcore.user = Class.create({

    initialize: function(object) {
        Object.extend(this, object);
    },

    isAllowed: function (type) {

        // @TODO: Should be removed when refactoring is finished
        if(this.admin) {
            return true;
        }

        if (typeof this.permissions == "object") {
            if(in_array(type,this.permissions)) {
                return true;
            }
        }
        return false;
    }
});
