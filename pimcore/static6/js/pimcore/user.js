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
