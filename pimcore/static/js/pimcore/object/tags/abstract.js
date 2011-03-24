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

pimcore.registerNS("pimcore.object.tags.abstract");
pimcore.object.tags.abstract = Class.create({


    markMandatory: function () {
        if (this.layout) {
            this.layout.getEl().addClass("object_mendatory_error");
        }
    },

    unmarkMandatory: function () {
        if (this.layout) {
            this.layout.getEl().removeClass("object_mendatory_error");
        }
    },

    isInvalidMandatory: function () {
        if (this.getValue().length < 1) {
            return true;
        }
        return false;
    },

    setObject: function(object){
        this.object = object;
    }
});