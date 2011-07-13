/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.user");

pimcore.user = Class.create({

    initialize: function(object) {
        Object.extend(this, object);
    },

    isAllowed: function (type) {
       
        if (this.permissionInfo[type]) {
            return this.permissionInfo[type].granted;
        }
        return false;
    }
});